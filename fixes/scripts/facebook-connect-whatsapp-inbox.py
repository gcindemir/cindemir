#!/usr/bin/env python3
"""Approve Instagram Message Access and connect WhatsApp to Meta inbox if available."""
from __future__ import annotations

import json
import os
import re
import shutil
import tempfile
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

PROFILE_SRC = Path(os.environ.get("FB_PROFILE_DIR") or "/tmp/fb-good-session")
FALLBACK_PROFILE = Path.home() / ".config/google-chrome"
ASSET = "336218871992"
BIZ = "1161971660662915"
SHOT = Path("/workspace/fixes")
OUT = SHOT / "fb-whatsapp-inbox-status.json"

URLS = [
    f"https://business.facebook.com/latest/home?asset_id={ASSET}&business_id={BIZ}",
    f"https://business.facebook.com/latest/inbox/all?asset_id={ASSET}&business_id={BIZ}",
    f"https://business.facebook.com/latest/settings?asset_id={ASSET}&business_id={BIZ}",
    f"https://business.facebook.com/latest/settings/instagram_account?asset_id={ASSET}&business_id={BIZ}",
    f"https://business.facebook.com/latest/whatsapp_manager/?business_id={BIZ}",
    f"https://business.facebook.com/latest/whatsapp_manager/phone_numbers/?business_id={BIZ}",
    f"https://business.facebook.com/wa/manage/?business_id={BIZ}",
    f"https://business.facebook.com/settings/whatsapp-business-accounts?business_id={BIZ}",
]


def copy_profile(tmp: Path):
    src = PROFILE_SRC if PROFILE_SRC.exists() else FALLBACK_PROFILE
    print("copy_from", src, flush=True)
    for item in ["Default", "Local State"]:
        s = src / item
        if not s.exists():
            continue
        d = tmp / item
        if s.is_dir():
            shutil.copytree(
                s,
                d,
                ignore=shutil.ignore_patterns(
                    "Cache", "Code Cache", "GPUCache", "Service Worker", "Blobstore", "DawnCache"
                ),
            )
        else:
            shutil.copy2(s, d)


def dismiss(page):
    for label in [
        "Belki daha sonra",
        "Maybe later",
        "Şimdi değil",
        "Not now",
        "Tamam",
        "OK",
        "Close",
        "Kapat",
        "Anladım",
        "Got it",
    ]:
        try:
            loc = page.get_by_role("button", name=re.compile(re.escape(label), re.I))
            if loc.count():
                loc.first.click(timeout=1200)
                page.wait_for_timeout(300)
        except Exception:
            pass


def ensure_session(page, ctx) -> bool:
    page.goto("https://www.facebook.com/", wait_until="domcontentloaded", timeout=120000)
    page.wait_for_timeout(3000)
    dismiss(page)
    cookies = {c["name"]: c.get("value", "") for c in ctx.cookies("https://www.facebook.com")}
    if not (cookies.get("c_user") and cookies.get("xs")):
        for sel in [
            page.get_by_role("button", name=re.compile(r"^Devam$", re.I)),
            page.locator("div[role='button']:has-text('Devam')"),
        ]:
            try:
                if sel.count():
                    sel.first.click(timeout=3000)
                    page.wait_for_timeout(5000)
                    break
            except Exception:
                pass
        for _ in range(90):
            cookies = {c["name"]: c.get("value", "") for c in ctx.cookies("https://www.facebook.com")}
            if cookies.get("c_user") and cookies.get("xs") and "two_factor" not in page.url:
                break
            if "two_factor" in page.url or "two_step" in page.url:
                print("NEED_2FA waiting…", flush=True)
            page.wait_for_timeout(5000)
            dismiss(page)
    page.goto(f"https://business.facebook.com/latest/home?asset_id={ASSET}&business_id={BIZ}", timeout=120000)
    page.wait_for_timeout(5000)
    dismiss(page)
    if "login" in page.url.lower():
        try:
            btn = page.get_by_role("button", name=re.compile(r"Facebook ile devam", re.I))
            if btn.count():
                btn.first.click(timeout=3000)
                page.wait_for_timeout(6000)
        except Exception:
            pass
    page.screenshot(path=str(SHOT / "fb-wa-0-home.png"))
    ok = "login" not in page.url.lower()
    print("session", ok, page.url[:140], flush=True)
    return ok


def click_matching(page, patterns, force=False) -> str | None:
    for pat in patterns:
        try:
            # buttons
            btn = page.get_by_role("button", name=re.compile(pat, re.I))
            if btn.count():
                btn.first.click(timeout=2500, force=force)
                page.wait_for_timeout(1500)
                return f"button:{pat}"
        except Exception:
            pass
        try:
            link = page.get_by_role("link", name=re.compile(pat, re.I))
            if link.count():
                link.first.click(timeout=2500, force=force)
                page.wait_for_timeout(1500)
                return f"link:{pat}"
        except Exception:
            pass
        try:
            loc = page.locator(f"div[role='button']:has-text(/{pat}/i), span:has-text(/{pat}/i), a:has-text(/{pat}/i)")
            if loc.count():
                loc.first.click(timeout=2500, force=force)
                page.wait_for_timeout(1500)
                return f"loc:{pat}"
        except Exception:
            pass
    return None


def body_snip(page, n=2500) -> str:
    try:
        return page.inner_text("body")[:n]
    except Exception:
        return ""


def approve_instagram_message_access(page) -> dict:
    result = {"attempted": True, "clicked": None, "status": "unknown"}
    # From home alerts / todo
    page.goto(f"https://business.facebook.com/latest/home?asset_id={ASSET}&business_id={BIZ}", timeout=120000)
    page.wait_for_timeout(5000)
    dismiss(page)
    page.screenshot(path=str(SHOT / "fb-wa-1-home-alerts.png"))
    body = body_snip(page, 4000)
    result["home_has_ig_alert"] = bool(
        re.search(r"Instagram.*(Mesaj|Message)|Mesaj Erişim|Message Access|Confirm Instagram", body, re.I)
    )
    clicked = click_matching(
        page,
        [
            r"Instagram Mesaj Erişimini Onayla",
            r"Confirm Instagram Message Access",
            r"Mesaj Erişimini Onayla",
            r"Message Access",
            r"Onayla",
            r"Confirm",
            r"İzin ver",
            r"Allow",
            r"Gözden geçir",
            r"Review",
        ],
    )
    if clicked:
        result["clicked"] = clicked
        page.wait_for_timeout(3000)
        dismiss(page)
        # secondary confirm
        clicked2 = click_matching(
            page,
            [
                r"^Onayla$",
                r"^Confirm$",
                r"^İzin ver$",
                r"^Allow$",
                r"Devam",
                r"Continue",
                r"Kaydet",
                r"Save",
                r"Tamam",
                r"Done",
            ],
            force=True,
        )
        result["clicked2"] = clicked2
        page.wait_for_timeout(2500)
        page.screenshot(path=str(SHOT / "fb-wa-2-ig-access.png"))
        body2 = body_snip(page, 3000).lower()
        if any(x in body2 for x in ["onaylandı", "confirmed", "success", "izin verildi", "bağlandı", "connected"]):
            result["status"] = "approved"
        else:
            result["status"] = "clicked_pending_verify"
    else:
        # try dedicated settings pages
        for url in [
            f"https://business.facebook.com/latest/settings/instagram_account?asset_id={ASSET}&business_id={BIZ}",
            f"https://business.facebook.com/latest/inbox/settings?asset_id={ASSET}&business_id={BIZ}",
            f"https://www.facebook.com/{ASSET}/settings/?tab=messenger_platform",
        ]:
            try:
                page.goto(url, timeout=90000)
                page.wait_for_timeout(4000)
                dismiss(page)
                c = click_matching(
                    page,
                    [
                        r"Instagram Mesaj Erişimini Onayla",
                        r"Confirm Instagram Message Access",
                        r"Onayla",
                        r"Confirm",
                        r"İzin ver",
                        r"Allow",
                    ],
                )
                if c:
                    result["clicked"] = c
                    result["via"] = url
                    page.screenshot(path=str(SHOT / "fb-wa-2-ig-access.png"))
                    result["status"] = "clicked_via_settings"
                    break
            except Exception as e:
                result.setdefault("errors", []).append(str(e)[:120])
        if result["status"] == "unknown":
            result["status"] = "alert_not_found"
    result["url"] = page.url
    return result


def connect_whatsapp(page) -> dict:
    result = {"attempted": True, "steps": [], "status": "unknown"}
    targets = [
        f"https://business.facebook.com/latest/whatsapp_manager/?business_id={BIZ}",
        f"https://business.facebook.com/latest/whatsapp_manager/phone_numbers/?business_id={BIZ}",
        f"https://business.facebook.com/wa/manage/?business_id={BIZ}",
        f"https://business.facebook.com/settings/whatsapp-business-accounts?business_id={BIZ}",
        f"https://business.facebook.com/latest/inbox/all?asset_id={ASSET}&business_id={BIZ}",
        f"https://business.facebook.com/latest/settings?asset_id={ASSET}&business_id={BIZ}",
    ]
    for i, url in enumerate(targets):
        try:
            print("goto", url[:100], flush=True)
            page.goto(url, timeout=120000)
            page.wait_for_timeout(5000)
            dismiss(page)
            shot = SHOT / f"fb-wa-page-{i}.png"
            page.screenshot(path=str(shot))
            body = body_snip(page, 4500)
            entry = {
                "url": page.url,
                "shot": str(shot),
                "has_whatsapp": bool(re.search(r"WhatsApp", body, re.I)),
                "has_connect": bool(
                    re.search(
                        r"WhatsApp.*(bağla|connect|ekle|add)|bağla.*WhatsApp|Connect WhatsApp|Get started|Başla",
                        body,
                        re.I,
                    )
                ),
                "snippets": [],
            }
            for m in re.finditer(r".{0,40}WhatsApp.{0,60}", body, re.I):
                entry["snippets"].append(re.sub(r"\s+", " ", m.group(0))[:120])
                if len(entry["snippets"]) >= 8:
                    break
            # Try common CTA buttons
            clicked = click_matching(
                page,
                [
                    r"WhatsApp'ı bağla",
                    r"WhatsApp’ı bağla",
                    r"Connect WhatsApp",
                    r"Add WhatsApp",
                    r"WhatsApp ekle",
                    r"Get started",
                    r"Başlayın",
                    r"Başla",
                    r"Create account",
                    r"Hesap oluştur",
                    r"Add phone number",
                    r"Telefon numarası ekle",
                    r"Link WhatsApp",
                    r"Continue",
                    r"Devam",
                ],
            )
            entry["clicked"] = clicked
            if clicked:
                page.wait_for_timeout(4000)
                page.screenshot(path=str(SHOT / f"fb-wa-page-{i}-after.png"))
                entry["after_url"] = page.url
                entry["after_body"] = body_snip(page, 2000)[:1500]
            result["steps"].append(entry)
        except Exception as e:
            result["steps"].append({"url": url, "error": str(e)[:200]})

    # Inbox channels / connected apps
    try:
        page.goto(
            f"https://business.facebook.com/latest/inbox/settings?asset_id={ASSET}&business_id={BIZ}",
            timeout=120000,
        )
        page.wait_for_timeout(5000)
        dismiss(page)
        page.screenshot(path=str(SHOT / "fb-wa-inbox-settings.png"))
        body = body_snip(page, 4000)
        clicked = click_matching(
            page,
            [
                r"WhatsApp",
                r"Kanallar",
                r"Channels",
                r"Bağlantılar",
                r"Connections",
                r"Bağla",
                r"Connect",
            ],
        )
        result["inbox_settings"] = {
            "url": page.url,
            "clicked": clicked,
            "has_whatsapp": "whatsapp" in body.lower(),
            "snip": body[:1200],
        }
        page.screenshot(path=str(SHOT / "fb-wa-inbox-settings-2.png"))
    except Exception as e:
        result["inbox_settings_error"] = str(e)[:200]

    # Summarize status
    bodies = " ".join(
        json.dumps(s, ensure_ascii=False) for s in result["steps"]
    ).lower()
    if "already connected" in bodies or "bağlı" in bodies or "connected" in bodies:
        result["status"] = "possibly_connected"
    elif any(s.get("clicked") for s in result["steps"]):
        result["status"] = "cta_clicked_needs_phone_verify"
    elif any(s.get("has_whatsapp") for s in result["steps"]):
        result["status"] = "whatsapp_ui_found_no_auto_connect"
    else:
        result["status"] = "whatsapp_entry_not_found"
    return result


def main():
    os.system("pkill -f 'google-chrome|chromium' >/dev/null 2>&1 || true")
    time.sleep(2)
    for p in [
        Path.home() / ".config/google-chrome/SingletonLock",
        Path.home() / ".config/google-chrome/SingletonCookie",
        Path.home() / ".config/google-chrome/SingletonSocket",
    ]:
        try:
            p.unlink()
        except Exception:
            pass

    tmp = Path(tempfile.mkdtemp(prefix="fb-wa-"))
    # Prefer good session directly if available
    if PROFILE_SRC.exists() and (PROFILE_SRC / "Default" / "Cookies").exists():
        # Use a working copy of good session
        shutil.copytree(PROFILE_SRC, tmp, dirs_exist_ok=True)
        for name in ["SingletonLock", "SingletonCookie", "SingletonSocket"]:
            try:
                (tmp / name).unlink()
            except Exception:
                pass
        print("using", PROFILE_SRC, "->", tmp, flush=True)
    else:
        copy_profile(tmp)

    status = {"ig_access": {}, "whatsapp": {}, "updated": time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime())}

    with sync_playwright() as p:
        ctx = p.chromium.launch_persistent_context(
            str(tmp),
            headless=False,
            channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage", "--disable-blink-features=AutomationControlled"],
            env={**os.environ, "DISPLAY": ":1"},
            viewport={"width": 1500, "height": 950},
            slow_mo=40,
        )
        page = ctx.pages[0] if ctx.pages else ctx.new_page()
        if not ensure_session(page, ctx):
            status["error"] = "session_failed"
            OUT.write_text(json.dumps(status, ensure_ascii=False, indent=2))
            print(json.dumps(status, indent=2, ensure_ascii=False), flush=True)
            ctx.close()
            return 1

        print("=== Instagram Message Access ===", flush=True)
        status["ig_access"] = approve_instagram_message_access(page)
        print(json.dumps(status["ig_access"], ensure_ascii=False), flush=True)

        print("=== WhatsApp connect ===", flush=True)
        status["whatsapp"] = connect_whatsapp(page)
        print(json.dumps({"status": status["whatsapp"].get("status"), "steps": len(status["whatsapp"].get("steps", []))}, ensure_ascii=False), flush=True)

        page.screenshot(path=str(SHOT / "fb-wa-final.png"))
        OUT.write_text(json.dumps(status, ensure_ascii=False, indent=2))
        print("wrote", OUT, flush=True)
        ctx.close()

    print(json.dumps(status, indent=2, ensure_ascii=False)[:4000], flush=True)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
