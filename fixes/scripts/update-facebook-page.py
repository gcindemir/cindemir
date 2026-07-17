#!/usr/bin/env python3
"""
Update ONLY Cindemir Law Office Facebook Page (id 100066585793269).
Does NOT open personal profile/settings.
"""
import os
import re
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

PROFILE = Path("/tmp/fb-jifkln9c")
ROOT = Path("/workspace")
LOG = ROOT / "fixes/facebook-page-update.log"

PAGE_ID = "100066585793269"
PAGE_NAME = "Cindemir Hukuk Bürosu"
PAGE_HOME = (
    "https://www.facebook.com/p/Cindemir-Hukuk-B%C3%BCrosu-Cindemir-Law-Office-100066585793269/"
)
PAGE_ABOUT = f"https://www.facebook.com/profile.php?id={PAGE_ID}&sk=about"
PAGE_MSG = f"https://www.facebook.com/{PAGE_ID}/settings/messaging/"
MBS_HOME = f"https://business.facebook.com/latest/home?asset_id={PAGE_ID}"

WHATSAPP = "+905325680647"
WHATSAPP_WA = "https://wa.me/902165506775"

PINNED = """Foreign clients in Türkiye — practical English guides from Cindemir Law Office:

🏢 Company formation: https://cindemirlaw.com/opening-a-company-in-turkey-for-foreigners/
✈️ Deportation: https://cindemirlaw.com/deportation-law-in-turkey/
💼 Debt recovery: https://cindemirlaw.com/debt-recovery-in-turkey/
📋 Criminal record: https://cindemirlaw.com/getting-criminal-record-in-turkey/

Istanbul counsel | English & Russian
https://cindemirlaw.com/about-us/"""


def log(msg):
    print(msg, flush=True)
    with LOG.open("a") as f:
        f.write(msg + "\n")


def snap(page, name):
    path = ROOT / f"fixes/fb-page-{name}.png"
    page.screenshot(path=str(path), full_page=True)
    log(f"screenshot {name}")


def close_popups(page):
    for sel in ['[aria-label="Kapat"]', '[aria-label="Close"]', '[aria-label="Dismiss"]']:
        try:
            loc = page.locator(sel)
            if loc.count():
                loc.first.click(timeout=1500)
                time.sleep(0.4)
        except Exception:
            pass


def assert_page_context(page):
    """Abort if we drifted to personal account."""
    url = page.url.lower()
    if re.search(r"facebook\.com/settings(?!/.*100066585793269)", url):
        raise RuntimeError(f"personal settings blocked: {page.url}")
    if "facebook.com/me" in url or "/profile.php?" in url and PAGE_ID not in url:
        raise RuntimeError(f"personal profile blocked: {page.url}")
    body = page.locator("body").inner_text(timeout=10000)
    if PAGE_NAME not in body and "Cindemir Law Office" not in body:
        log(f"warn: page name not visible on {page.url}")


def save_btn(page):
    for label in ["Kaydet", "Save", "Bitti", "Done", "Güncelle"]:
        btn = page.get_by_role("button", name=label)
        if btn.count():
            try:
                btn.last.click(timeout=5000)
                time.sleep(2)
                log(f"saved ({label})")
                return True
            except Exception:
                pass
    return False


def open_about_section(page, section):
    page.goto(PAGE_ABOUT, wait_until="domcontentloaded", timeout=120000)
    time.sleep(5)
    close_popups(page)
    assert_page_context(page)
    page.get_by_text(section, exact=True).first.click(timeout=8000)
    time.sleep(3)
    snap(page, f"about-{section[:8]}")


def update_whatsapp_contact(page):
    open_about_section(page, "İletişim bilgileri")

    for label in [
        "WhatsApp numarasını düzenle",
        "Edit WhatsApp number",
        "WhatsApp numarasını ekle",
        "Add WhatsApp number",
    ]:
        btn = page.get_by_label(label, exact=False)
        if btn.count():
            btn.first.click(timeout=5000)
            break
    else:
        row = page.locator("span").filter(has_text=re.compile(r"WhatsApp", re.I))
        if row.count():
            row.first.click(timeout=5000)
            pencil = page.get_by_label(re.compile(r"WhatsApp.*düzenle", re.I))
            if pencil.count():
                pencil.first.click(timeout=5000)

    time.sleep(2)
    snap(page, "wa-contact-dialog")
    inp = page.locator('input[type="tel"], input[type="text"]:visible, input:visible')
    if inp.count():
        inp.first.fill(WHATSAPP)
        save_btn(page)
        log("whatsapp number set")
    snap(page, "wa-contact-done")


def update_address(page):
    open_about_section(page, "İletişim bilgileri")

    for label in [
        "Konumu düzenle",
        "Edit location",
        "Adresi düzenle",
        "Edit address",
        "Konum bilgisini düzenle",
    ]:
        btn = page.get_by_label(label, exact=False)
        if btn.count():
            btn.first.click(timeout=6000)
            log(f"address via {label}")
            break
    else:
        loc = page.get_by_text("Konum", exact=True)
        if loc.count():
            loc.first.click(timeout=5000)
            edit = page.get_by_label(re.compile(r"(Konum|Adres).*düzenle", re.I))
            if edit.count():
                edit.first.click(timeout=5000)

    time.sleep(2)
    snap(page, "address-dialog")
    inputs = page.locator('input[type="text"]:visible')
    vals = ["Ritim İstanbul 44/18", "Maltepe", "Istanbul", "34840"]
    for i, v in enumerate(vals):
        if i < inputs.count():
            inputs.nth(i).fill(v)
    save_btn(page)
    snap(page, "address-done")
    log("address updated")


def set_whatsapp_cta(page):
    page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
    time.sleep(6)
    close_popups(page)
    assert_page_context(page)
    snap(page, "cta-home")

    # Try add/edit action button on page header
    for text in [
        "Düğme ekle",
        "Add Button",
        "Düğmeyi düzenle",
        "Edit Button",
        "Eylem düğmesi",
    ]:
        loc = page.get_by_role("button", name=re.compile(text, re.I))
        if not loc.count():
            loc = page.locator(f'div[role="button"]:has-text("{text}")')
        if loc.count():
            loc.first.click(timeout=6000)
            log(f"cta open: {text}")
            break

    time.sleep(3)
    snap(page, "cta-picker")

    for text in [
        "WhatsApp",
        "WhatsApp mesajı gönder",
        "Send WhatsApp message",
        "Send WhatsApp Message",
    ]:
        loc = page.get_by_text(text, exact=False)
        if loc.count():
            loc.first.click(timeout=6000)
            log(f"cta type: {text}")
            break

    time.sleep(2)
    tel = page.locator('input[type="tel"], input[type="text"]:visible')
    if tel.count():
        tel.first.fill(WHATSAPP)
    save_btn(page)
    snap(page, "cta-wa-done")
    log("whatsapp cta set")


def enable_page_messaging(page):
    page.goto(PAGE_MSG, wait_until="domcontentloaded", timeout=120000)
    time.sleep(6)
    close_popups(page)
    assert_page_context(page)
    snap(page, "messaging")

    for text in ["WhatsApp", "Bağla", "Connect", "Entegre"]:
        loc = page.get_by_text(text, exact=False)
        if loc.count():
            try:
                loc.first.click(timeout=4000)
                time.sleep(2)
            except Exception:
                pass

    save_btn(page)
    snap(page, "messaging-done")


def publish_and_pin(page):
    page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
    time.sleep(6)
    close_popups(page)
    assert_page_context(page)

    existing = page.locator('div[role="article"]').filter(
        has_text="opening-a-company-in-turkey-for-foreigners"
    )
    if not existing.count():
        triggers = [
            'span:has-text("Güncelleme yaz")',
            'div[role="button"]:has-text("Gönderi oluştur")',
            'div[aria-label*="Gönderi oluştur" i]',
        ]
        for sel in triggers:
            loc = page.locator(sel)
            if loc.count():
                loc.first.click(timeout=6000)
                break
        time.sleep(3)
        editor = page.locator('div[contenteditable="true"][role="textbox"]').last
        editor.click(timeout=8000)
        page.keyboard.type(PINNED, delay=2)
        time.sleep(2)
        snap(page, "post-draft")
        dlg = page.locator('div[role="dialog"]')
        post_btn = dlg.get_by_role("button", name="Paylaş")
        if post_btn.count():
            post_btn.click(timeout=10000)
        else:
            page.locator('div[role="dialog"] div[role="button"]:has-text("Paylaş")').last.click(timeout=10000)
        time.sleep(12)
        log("post published")
    else:
        log("post already exists")

    snap(page, "post-check")
    post = page.locator('div[role="article"]').filter(has_text="Company formation").first
    if not post.count():
        post = page.locator('div[role="article"]').filter(
            has_text="opening-a-company-in-turkey"
        ).first
    if post.count():
        post.scroll_into_view_if_needed()
        post.locator('[aria-label="Bu gönderi için eylemler"]').click(timeout=6000)
        time.sleep(2)
        page.get_by_text("Gönderiyi sabitle", exact=False).first.click(timeout=5000)
        time.sleep(2)
        page.get_by_role("button", name="Sabitle").click(timeout=5000)
        log("post pinned")
        snap(page, "post-pinned")


def main():
    LOG.write_text("")
    os.system("pkill -9 -f 'google-chrome.*fb-jifkln9c' 2>/dev/null")
    time.sleep(2)

    results = {}
    with sync_playwright() as p:
        ctx = p.chromium.launch_persistent_context(
            str(PROFILE),
            headless=False,
            channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage"],
            env={**os.environ, "DISPLAY": ":1"},
            viewport={"width": 1500, "height": 950},
        )
        page = ctx.pages[0] if ctx.pages else ctx.new_page()

        page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
        time.sleep(8)
        close_popups(page)
        snap(page, "start")
        log(f"start url={page.url}")

        steps = [
            ("whatsapp_contact", update_whatsapp_contact),
            ("address", update_address),
            ("whatsapp_cta", set_whatsapp_cta),
            ("messaging", enable_page_messaging),
            ("post_pin", publish_and_pin),
        ]
        for name, fn in steps:
            try:
                fn(page)
                results[name] = "ok"
            except Exception as e:
                results[name] = str(e)
                log(f"{name} err: {e}")
                snap(page, f"err-{name}")

        page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
        time.sleep(5)
        snap(page, "final")
        ctx.close()

    log(f"results={results}")
    log("DONE")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
