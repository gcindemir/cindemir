#!/usr/bin/env python3
"""Finish Instagram access approval + WhatsApp inbox 'Başla' setup with dialog handling."""
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
OUT = SHOT / "fb-finish-ig-wa-status.json"


def dismiss(page):
    for label in ["Belki daha sonra", "Maybe later", "Şimdi değil", "Not now", "Kapat", "Close"]:
        try:
            loc = page.get_by_role("button", name=re.compile(re.escape(label), re.I))
            if loc.count():
                loc.first.click(timeout=800)
        except Exception:
            pass


def shot(page, name):
    path = SHOT / name
    page.screenshot(path=str(path))
    return str(path)


def main():
    os.system("ps -C chrome -o pid= 2>/dev/null | while read p; do kill -9 $p; done")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="fb-finish-"))
    shutil.copytree(PROFILE_SRC, tmp, dirs_exist_ok=True)
    for n in ["SingletonLock", "SingletonCookie", "SingletonSocket"]:
        try:
            (tmp / n).unlink()
        except Exception:
            pass

    status = {"steps": [], "updated": time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime())}

    with sync_playwright() as p:
        ctx = p.chromium.launch_persistent_context(
            str(tmp),
            headless=False,
            channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage"],
            env={**os.environ, "DISPLAY": ":1"},
            viewport={"width": 1500, "height": 950},
            slow_mo=50,
        )
        page = ctx.pages[0] if ctx.pages else ctx.new_page()

        page.goto(
            f"https://business.facebook.com/latest/inbox/all?asset_id={ASSET}&business_id={BIZ}",
            timeout=120000,
        )
        page.wait_for_timeout(6000)
        dismiss(page)
        status["steps"].append({"home_inbox": shot(page, "fb-finish-0-inbox.png"), "url": page.url})

        # --- Instagram access ---
        # Click the blue "Erişimi onayla" near Instagram tab (role=button preferred)
        ig = {"actions": []}
        try:
            # Prefer exact button near Instagram
            candidates = page.locator("div[role='button']:has-text('Erişimi onayla'), button:has-text('Erişimi onayla')")
            print("ig_candidates", candidates.count(), flush=True)
            if candidates.count():
                with ctx.expect_page(timeout=8000) as pop_info:
                    candidates.first.click(timeout=4000)
                try:
                    popup = pop_info.value
                    popup.wait_for_load_state("domcontentloaded", timeout=30000)
                    ig["popup_url"] = popup.url
                    shot(popup, "fb-finish-ig-popup.png")
                    # approve in popup
                    for pat in [r"İzin ver", r"Allow", r"Onayla", r"Confirm", r"Devam", r"Continue", r"Tamam"]:
                        try:
                            b = popup.get_by_role("button", name=re.compile(pat, re.I))
                            if b.count():
                                b.first.click(timeout=2500)
                                ig["actions"].append(f"popup:{pat}")
                                popup.wait_for_timeout(2500)
                        except Exception:
                            pass
                    try:
                        popup.close()
                    except Exception:
                        pass
                except Exception as e:
                    ig["popup_err"] = str(e)[:160]
                    # maybe same-page dialog
                    page.wait_for_timeout(2500)
            else:
                # fallback: click Instagram tab then approve
                page.get_by_role("tab", name=re.compile(r"Instagram", re.I)).first.click(timeout=3000)
                page.wait_for_timeout(2000)
                page.locator("text=Erişimi onayla").first.click(timeout=3000)
                ig["actions"].append("fallback_text_click")
        except Exception as e:
            # expect_page may timeout if no popup — handle same page
            ig["click_err"] = str(e)[:200]
            page.wait_for_timeout(2000)

        # Same-page modal confirmations
        for pat in [
            r"^İzin ver$",
            r"^Allow$",
            r"^Onayla$",
            r"^Confirm$",
            r"^Devam et$",
            r"^Devam$",
            r"^Continue$",
            r"Hesabı bağla",
            r"Connect account",
            r"Yeniden bağla",
            r"Reconnect",
            r"^Tamam$",
            r"^Done$",
        ]:
            try:
                b = page.get_by_role("button", name=re.compile(pat, re.I))
                if b.count():
                    b.first.click(timeout=2000, force=True)
                    ig["actions"].append(pat)
                    page.wait_for_timeout(2000)
            except Exception:
                pass

        page.wait_for_timeout(3000)
        ig["shot"] = shot(page, "fb-finish-1-ig.png")
        ig["url"] = page.url
        ig["body"] = re.sub(r"\s+", " ", page.inner_text("body")[:2000])
        ig["still_needs_approve"] = "Erişimi onayla" in ig["body"]
        ig["disconnected"] = "Bağlantı kesildi" in ig["body"] or "Disconnected" in ig["body"]
        status["instagram"] = ig
        print("IG", json.dumps({k: ig[k] for k in ig if k != "body"}, ensure_ascii=False), flush=True)

        # --- WhatsApp Başla ---
        wa = {"actions": []}
        page.goto(
            f"https://business.facebook.com/latest/inbox/wec?asset_id={ASSET}&business_id={BIZ}",
            timeout=120000,
        )
        page.wait_for_timeout(5000)
        dismiss(page)
        wa["shot1"] = shot(page, "fb-finish-2-wa.png")
        # Click primary "Başla" CTA in WhatsApp empty state
        basla = page.get_by_role("button", name=re.compile(r"^Başla$", re.I))
        if not basla.count():
            basla = page.locator("div[role='button']:has-text('Başla'), a:has-text('Başla'), button:has-text('Başla')")
        print("basla_count", basla.count(), flush=True)
        if basla.count():
            basla.first.click(timeout=4000)
            wa["actions"].append("Başla")
            page.wait_for_timeout(5000)
            dismiss(page)
            # follow-up choices: select existing WABA / phone
            for pat in [
                r"Cindemir Hukuk",
                r"WhatsApp Business",
                r"Devam",
                r"Continue",
                r"Bağla",
                r"Connect",
                r"Seç",
                r"Select",
                r"Onayla",
                r"Confirm",
                r"\+90 216",
                r"216 550",
                r"Kullan",
                r"Use",
                r"İleri",
                r"Next",
            ]:
                try:
                    b = page.get_by_role("button", name=re.compile(pat, re.I))
                    if b.count():
                        b.first.click(timeout=2500)
                        wa["actions"].append(pat)
                        page.wait_for_timeout(2500)
                        continue
                    t = page.locator(f"text=/{pat}/i")
                    if t.count():
                        t.first.click(timeout=2500)
                        wa["actions"].append(f"text:{pat}")
                        page.wait_for_timeout(2500)
                except Exception:
                    pass
        wa["shot2"] = shot(page, "fb-finish-3-wa-after.png")
        wa["url"] = page.url
        wa["body"] = re.sub(r"\s+", " ", page.inner_text("body")[:2200])
        wa["still_shows_basla"] = bool(re.search(r"\bBaşla\b", wa["body"]))
        status["whatsapp"] = wa
        print("WA", json.dumps({k: wa[k] for k in wa if k != "body"}, ensure_ascii=False), flush=True)

        # --- Phone number gear carefully ---
        phone = {"actions": []}
        page.goto(
            f"https://business.facebook.com/latest/whatsapp_manager/phone_numbers/?business_id={BIZ}&asset_id={WABA}",
            timeout=120000,
        )
        page.wait_for_timeout(5000)
        dismiss(page)
        phone["shot1"] = shot(page, "fb-finish-4-phones.png")
        # click settings gear in the row (last button in table row)
        try:
            row = page.locator("tr:has-text('216'), div[role='row']:has-text('216')").first
            if row.count() or True:
                gears = page.locator("div[role='button'][aria-label*='Ayar'], div[role='button'][aria-label*='Setting'], [aria-label='Ayarlar'], [aria-label='Settings']")
                print("gears", gears.count(), flush=True)
                if gears.count():
                    # pick last gear (usually row action)
                    gears.nth(gears.count() - 1).click(timeout=3000)
                    phone["actions"].append("gear")
                    page.wait_for_timeout(3000)
        except Exception as e:
            phone["gear_err"] = str(e)[:160]
        phone["shot2"] = shot(page, "fb-finish-5-phone-menu.png")
        for pat in [
            r"Telefon numarası ayarları",
            r"Phone number settings",
            r"Profil",
            r"Profile",
            r"Çevrimiçi",
            r"Online",
            r"Cloud API",
            r"Bulut API",
            r"Bağlantı",
            r"Connection",
        ]:
            try:
                t = page.locator(f"text=/{pat}/i")
                if t.count():
                    t.first.click(timeout=2000)
                    phone["actions"].append(pat)
                    page.wait_for_timeout(2000)
            except Exception:
                pass
        phone["shot3"] = shot(page, "fb-finish-6-phone-detail.png")
        phone["url"] = page.url
        phone["body"] = re.sub(r"\s+", " ", page.inner_text("body")[:2000])
        phone["offline"] = "Çevrimdışı" in phone["body"] or "Offline" in phone["body"]
        status["phone"] = phone

        # Final inbox state
        page.goto(
            f"https://business.facebook.com/latest/inbox/all?asset_id={ASSET}&business_id={BIZ}",
            timeout=120000,
        )
        page.wait_for_timeout(5000)
        dismiss(page)
        final_body = re.sub(r"\s+", " ", page.inner_text("body")[:2500])
        status["final"] = {
            "shot": shot(page, "fb-finish-7-final.png"),
            "ig_approve_button": "Erişimi onayla" in final_body,
            "ig_disconnected": "Bağlantı kesildi" in final_body,
            "wa_basla": bool(re.search(r"\bBaşla\b", final_body)) and "WhatsApp" in final_body,
            "url": page.url,
            "snip": final_body[:1200],
        }
        OUT.write_text(json.dumps(status, ensure_ascii=False, indent=2))
        print(json.dumps(status["final"], ensure_ascii=False, indent=2), flush=True)
        print("wrote", OUT, flush=True)
        # persist cookies back into good session
        try:
            for name in ["Cookies", "Cookies-journal"]:
                s = tmp / "Default" / name
                if s.exists():
                    shutil.copy2(s, PROFILE_SRC / "Default" / name)
                    shutil.copy2(s, Path.home() / ".config/google-chrome/Default" / name)
            print("cookies_synced", flush=True)
        except Exception as e:
            print("cookie_sync_err", e, flush=True)
        ctx.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
