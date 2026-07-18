#!/usr/bin/env python3
"""Approve Instagram inbox access and advance WhatsApp inbox/phone activation."""
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
ASSET = "336218871992"
BIZ = "1161971660662915"
WABA = "1262355825974540"
SHOT = Path("/workspace/fixes")
OUT = SHOT / "fb-ig-wa-approve-status.json"
INBOX = f"https://business.facebook.com/latest/inbox/all?asset_id={ASSET}&business_id={BIZ}"
PHONES = f"https://business.facebook.com/latest/whatsapp_manager/phone_numbers/?business_id={BIZ}&asset_id={WABA}"
IG_SETTINGS = f"https://business.facebook.com/latest/settings/instagram_account/?business_id={BIZ}&asset_id={ASSET}"


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
                loc.first.click(timeout=1000)
                page.wait_for_timeout(250)
        except Exception:
            pass
    # close profile switcher overlays
    try:
        x = page.locator("[aria-label='Close'], [aria-label='Kapat']")
        if x.count():
            x.first.click(timeout=800)
    except Exception:
        pass


def click_text(page, patterns, force=False) -> str | None:
    for pat in patterns:
        try:
            btn = page.get_by_role("button", name=re.compile(pat, re.I))
            if btn.count():
                btn.first.click(timeout=3000, force=force)
                page.wait_for_timeout(2000)
                return f"button:{pat}"
        except Exception:
            pass
        try:
            link = page.get_by_role("link", name=re.compile(pat, re.I))
            if link.count():
                link.first.click(timeout=3000, force=force)
                page.wait_for_timeout(2000)
                return f"link:{pat}"
        except Exception:
            pass
        try:
            loc = page.locator(f"div[role='button']:has-text(/{pat}/i)")
            if loc.count():
                loc.first.click(timeout=3000, force=force)
                page.wait_for_timeout(2000)
                return f"divbtn:{pat}"
        except Exception:
            pass
        try:
            loc = page.locator(f"span:text-matches('{pat}', 'i'), div:text-matches('{pat}', 'i')").first
            if loc.count() or loc.is_visible():
                loc.click(timeout=3000, force=force)
                page.wait_for_timeout(2000)
                return f"text:{pat}"
        except Exception:
            pass
    return None


def body(page, n=3500) -> str:
    try:
        return page.inner_text("body")[:n]
    except Exception:
        return ""


def main():
    os.system("ps -C chrome -o pid= 2>/dev/null | while read p; do kill -9 $p; done")
    time.sleep(2)

    tmp = Path(tempfile.mkdtemp(prefix="fb-igwa-"))
    shutil.copytree(PROFILE_SRC, tmp, dirs_exist_ok=True)
    for name in ["SingletonLock", "SingletonCookie", "SingletonSocket"]:
        try:
            (tmp / name).unlink()
        except Exception:
            pass
    print("profile", tmp, flush=True)

    status = {
        "ig_approve": {},
        "whatsapp_tab": {},
        "phone_activate": {},
        "updated": time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime()),
    }

    with sync_playwright() as p:
        ctx = p.chromium.launch_persistent_context(
            str(tmp),
            headless=False,
            channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage", "--disable-blink-features=AutomationControlled"],
            env={**os.environ, "DISPLAY": ":1"},
            viewport={"width": 1500, "height": 950},
            slow_mo=45,
        )
        page = ctx.pages[0] if ctx.pages else ctx.new_page()

        # Warm
        page.goto(f"https://business.facebook.com/latest/home?asset_id={ASSET}&business_id={BIZ}", timeout=120000)
        page.wait_for_timeout(4000)
        dismiss(page)
        if "login" in page.url.lower():
            status["error"] = "login_required"
            OUT.write_text(json.dumps(status, ensure_ascii=False, indent=2))
            ctx.close()
            return 1

        # === Instagram access approve in Inbox ===
        print("=== IG approve ===", flush=True)
        page.goto(INBOX, timeout=120000)
        page.wait_for_timeout(6000)
        dismiss(page)
        page.screenshot(path=str(SHOT / "fb-igwa-inbox.png"))
        b = body(page)
        status["ig_approve"]["inbox_has_erisimi_onayla"] = "Erişimi onayla" in b or "Confirm access" in b
        clicked = click_text(
            page,
            [
                r"Erişimi onayla",
                r"Confirm access",
                r"Instagram.*onay",
                r"Approve",
            ],
        )
        status["ig_approve"]["clicked"] = clicked
        page.wait_for_timeout(3500)
        dismiss(page)
        # confirmation dialogs
        clicked2 = click_text(
            page,
            [
                r"^Onayla$",
                r"^Confirm$",
                r"^İzin ver$",
                r"^Allow$",
                r"^Devam$",
                r"^Continue$",
                r"^Tamam$",
                r"^Done$",
                r"Anladım",
                r"Got it",
                r"Kaydet",
                r"Save",
            ],
            force=True,
        )
        status["ig_approve"]["clicked2"] = clicked2
        page.wait_for_timeout(3000)
        page.screenshot(path=str(SHOT / "fb-igwa-after-approve.png"))
        b2 = body(page)
        status["ig_approve"]["still_shows_button"] = "Erişimi onayla" in b2 or "Confirm access" in b2
        status["ig_approve"]["instagram_tab_visible"] = bool(re.search(r"\bInstagram\b", b2))
        status["ig_approve"]["url"] = page.url
        if clicked and not status["ig_approve"]["still_shows_button"]:
            status["ig_approve"]["status"] = "approved"
        elif clicked:
            status["ig_approve"]["status"] = "clicked_maybe_partial"
        else:
            # try settings Instagram accounts
            page.goto(IG_SETTINGS, timeout=120000)
            page.wait_for_timeout(4000)
            dismiss(page)
            page.screenshot(path=str(SHOT / "fb-igwa-ig-settings.png"))
            c3 = click_text(
                page,
                [
                    r"Erişimi onayla",
                    r"Confirm access",
                    r"Mesaj.*izin",
                    r"Message.*access",
                    r"Onayla",
                    r"Confirm",
                ],
            )
            status["ig_approve"]["settings_clicked"] = c3
            page.screenshot(path=str(SHOT / "fb-igwa-ig-settings-2.png"))
            status["ig_approve"]["status"] = "settings_attempt" if c3 else "button_not_found"

        print(json.dumps(status["ig_approve"], ensure_ascii=False), flush=True)

        # === WhatsApp tab in Inbox ===
        print("=== WhatsApp inbox tab ===", flush=True)
        page.goto(INBOX, timeout=120000)
        page.wait_for_timeout(5000)
        dismiss(page)
        wa_click = click_text(page, [r"^WhatsApp", r"WhatsApp \(Yeni\)", r"WhatsApp"])
        status["whatsapp_tab"]["clicked"] = wa_click
        page.wait_for_timeout(4000)
        page.screenshot(path=str(SHOT / "fb-igwa-whatsapp-tab.png"))
        wb = body(page, 4000)
        status["whatsapp_tab"]["body_snip"] = re.sub(r"\s+", " ", wb)[:1500]
        status["whatsapp_tab"]["url"] = page.url
        # try setup CTAs on WhatsApp channel
        wa_cta = click_text(
            page,
            [
                r"WhatsApp'ı bağla",
                r"Connect WhatsApp",
                r"Başlayın",
                r"Get started",
                r"Kur",
                r"Set up",
                r"Telefon numarası ekle",
                r"Add phone number",
                r"Devam",
                r"Continue",
            ],
        )
        status["whatsapp_tab"]["cta"] = wa_cta
        page.wait_for_timeout(3000)
        page.screenshot(path=str(SHOT / "fb-igwa-whatsapp-tab-2.png"))
        if wa_cta:
            status["whatsapp_tab"]["status"] = "cta_clicked"
        elif wa_click:
            status["whatsapp_tab"]["status"] = "tab_opened"
        else:
            status["whatsapp_tab"]["status"] = "tab_not_clicked"

        # === Phone number activation (offline -> try settings) ===
        print("=== Phone activate ===", flush=True)
        page.goto(PHONES, timeout=120000)
        page.wait_for_timeout(5000)
        dismiss(page)
        page.screenshot(path=str(SHOT / "fb-igwa-phones.png"))
        pb = body(page, 3000)
        status["phone_activate"]["shows_offline"] = "Çevrimdışı" in pb or "Offline" in pb
        status["phone_activate"]["shows_number"] = "+90 216 550 67 75" in pb or "216 550" in pb or "902165506775" in pb.replace(" ", "")
        # open gear / settings for the number
        gear = None
        for sel in [
            "[aria-label='Ayarlar']",
            "[aria-label='Settings']",
            "div[role='button'][aria-label*='Ayar']",
            "div[role='button'][aria-label*='Setting']",
        ]:
            try:
                loc = page.locator(sel)
                if loc.count():
                    loc.first.click(timeout=2000)
                    gear = sel
                    page.wait_for_timeout(2000)
                    break
            except Exception:
                pass
        if not gear:
            # click the phone row
            try:
                row = page.locator("text=/\\+90\\s*216\\s*550\\s*67\\s*75/")
                if row.count():
                    row.first.click(timeout=2000)
                    gear = "phone_row"
                    page.wait_for_timeout(2500)
            except Exception:
                pass
        status["phone_activate"]["opened"] = gear
        page.screenshot(path=str(SHOT / "fb-igwa-phone-settings.png"))
        # try activate / migrate to cloud / connect API
        act = click_text(
            page,
            [
                r"Çevrimiçi yap",
                r"Go online",
                r"Activate",
                r"Etkinleştir",
                r"Connect",
                r"Bağla",
                r"Cloud API",
                r"Bulut API",
                r"Migrate",
                r"Taşı",
                r"Verify",
                r"Doğrula",
                r"Yeniden bağla",
                r"Reconnect",
            ],
        )
        status["phone_activate"]["action"] = act
        page.wait_for_timeout(3000)
        # payment method warning CTA (cannot complete without user card)
        pay = click_text(page, [r"Ödeme yöntemi ekleyin", r"Add payment method"])
        status["phone_activate"]["payment_cta"] = pay
        if pay:
            page.wait_for_timeout(3000)
            page.screenshot(path=str(SHOT / "fb-igwa-payment.png"))
            # Don't enter payment details; capture state
            status["phone_activate"]["payment_note"] = "opened_payment_ui_needs_user_card"
            # go back
            try:
                page.go_back(timeout=30000)
            except Exception:
                pass
        page.screenshot(path=str(SHOT / "fb-igwa-phone-final.png"))
        pb2 = body(page, 2500)
        status["phone_activate"]["still_offline"] = "Çevrimdışı" in pb2 or "Offline" in pb2
        status["phone_activate"]["url"] = page.url
        if act and not status["phone_activate"]["still_offline"]:
            status["phone_activate"]["status"] = "activated"
        elif status["phone_activate"]["shows_offline"]:
            status["phone_activate"]["status"] = "still_offline_needs_payment_or_app"
        else:
            status["phone_activate"]["status"] = "checked"

        # Final inbox verify
        page.goto(INBOX, timeout=120000)
        page.wait_for_timeout(5000)
        dismiss(page)
        page.screenshot(path=str(SHOT / "fb-igwa-final-inbox.png"))
        fb = body(page, 2500)
        status["final"] = {
            "ig_confirm_button_present": "Erişimi onayla" in fb or "Confirm access" in fb,
            "has_whatsapp_tab": "WhatsApp" in fb,
            "url": page.url,
        }

        OUT.write_text(json.dumps(status, ensure_ascii=False, indent=2))
        print(json.dumps(status, ensure_ascii=False, indent=2), flush=True)
        ctx.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
