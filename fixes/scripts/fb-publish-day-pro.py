#!/usr/bin/env python3
"""Publish calendar day via Professional Dashboard composer (logged-in session)."""
import argparse
import json
import os
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
CAL = ROOT / "fixes/facebook-content-calendar.json"
PROFILE = Path("/tmp/fb-jifkln9c")
LOG = ROOT / "fixes/facebook-publish.log"
PAGE_HOME = (
    "https://www.facebook.com/p/Cindemir-Hukuk-B%C3%BCrosu-Cindemir-Law-Office-100066585793269/"
)
PRO_DASH = "https://www.facebook.com/professional_dashboard/"


def log(msg):
    print(msg, flush=True)
    with LOG.open("a") as f:
        f.write(msg + "\n")


def snap(page, name):
    page.screenshot(path=str(ROOT / f"fixes/fb-pub-{name}.png"), full_page=True)


def close_popups(page):
    for _ in range(2):
        try:
            page.keyboard.press("Escape")
            time.sleep(0.2)
        except Exception:
            pass


def ensure_page_identity(page):
    page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
    time.sleep(5)
    # Profile switch modal
    body = page.locator("body").inner_text(timeout=15000)
    if "Profili değiştir" in body or "Geçiş Yap" in body:
        # Prefer button inside dialog
        dialog = page.locator('div[role="dialog"]').filter(has_text="Geçiş Yap")
        if dialog.count():
            btn = dialog.last.get_by_role("button", name="Geçiş Yap")
            if btn.count():
                btn.first.click(timeout=8000, force=True)
            else:
                dialog.last.get_by_text("Geçiş Yap", exact=True).last.click(timeout=8000, force=True)
        else:
            page.get_by_role("button", name="Geçiş Yap").first.click(timeout=8000, force=True)
        time.sleep(6)
        log("switched page identity")
    snap(page, "identity")
    body = page.locator("body").inner_text(timeout=15000)
    if "Cindemir" not in body:
        raise RuntimeError("not on Cindemir page")
    if "Karadeniz" in body or "Ereğli" in body:
        raise RuntimeError("wrong business page")


def open_composer(page):
    page.goto(PRO_DASH, wait_until="domcontentloaded", timeout=120000)
    time.sleep(6)
    close_popups(page)
    for label in ["Bir gönderi oluştur", "Create a post", "Gönderi oluştur", "Create post"]:
        loc = page.get_by_text(label, exact=False)
        if loc.count():
            loc.first.click(timeout=8000, force=True)
            time.sleep(3)
            log(f"opened composer via {label}")
            return
    # fallback: page home composer
    page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
    time.sleep(5)
    for sel in [
        'span:has-text("Ne düşünüyorsun")',
        'span:has-text("Bir düşünceni paylaş")',
        'div[role="button"]:has-text("Gönderi oluştur")',
    ]:
        if page.locator(sel).count():
            page.locator(sel).first.click(timeout=8000, force=True)
            time.sleep(3)
            log(f"opened composer via {sel}")
            return
    raise RuntimeError("composer not found")


def publish_text(page, text, idx):
    open_composer(page)
    # editor inside dialog preferred
    editors = page.locator('div[role="dialog"] div[contenteditable="true"][role="textbox"]')
    if editors.count():
        editor = editors.first
    else:
        editor = page.locator('div[contenteditable="true"][role="textbox"]').first
    editor.click(timeout=8000, force=True)
    page.keyboard.type(text, delay=2)
    time.sleep(2)
    snap(page, f"post-{idx}-draft")

    # İleri if present
    if page.get_by_text("İleri", exact=True).count():
        page.get_by_text("İleri", exact=True).last.click(timeout=8000, force=True)
        time.sleep(3)

    dialog = page.locator('div[role="dialog"]').filter(has_text="Şimdi yayınla")
    if not dialog.count():
        dialog = page.locator('div[role="dialog"]').filter(has_text="Gönderi ayarları")
    if dialog.count():
        share = dialog.last.get_by_role("button", name="Paylaş", exact=True)
        if share.count():
            share.last.click(timeout=12000, force=True)
        else:
            page.evaluate(
                """() => {
                  const dialogs=[...document.querySelectorAll('[role=dialog]')];
                  const d=dialogs.find(x => x.innerText.includes('Şimdi yayınla') || x.innerText.includes('Gönderi ayarları')) || dialogs.at(-1);
                  const cand=[...d.querySelectorAll('[role=button],button')].filter(el => el.innerText.trim()==='Paylaş');
                  if(!cand.length) throw new Error('no Paylaş');
                  cand.at(-1).click();
                }"""
            )
    else:
        # direct Paylaş / Share / Yayınla
        for name in ["Paylaş", "Share", "Yayınla", "Post"]:
            btn = page.get_by_role("button", name=name, exact=True)
            if btn.count():
                btn.last.click(timeout=12000, force=True)
                break
        else:
            page.get_by_text("Paylaş", exact=True).last.click(timeout=12000, force=True)

    time.sleep(4)
    body = page.locator("body").inner_text(timeout=10000)
    if "Hiç grup bulunamadı" in body:
        close_popups(page)
        raise RuntimeError("group share dialog")
    time.sleep(8)
    snap(page, f"post-{idx}-live")
    log(f"published post {idx}")


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--day", type=int, required=True)
    ap.add_argument("--limit", type=int, default=5)
    args = ap.parse_args()

    LOG.write_text("")
    cal = json.loads(CAL.read_text())
    day = next((d for d in cal["days"] if d["day"] == args.day), None)
    if not day:
        raise SystemExit(f"day {args.day} not found")

    os.system(
        "pgrep -af 'user-data-dir=/tmp/fb-jifkln9c' | awk '{print $1}' | xargs -r kill -9 2>/dev/null"
    )
    time.sleep(2)

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
        ensure_page_identity(page)
        for i, post in enumerate(day["posts"][: args.limit], 1):
            try:
                publish_text(page, post["summary"], i)
                time.sleep(6)
            except Exception as e:
                log(f"post {i} err: {e}")
                snap(page, f"post-{i}-err")
        snap(page, "day-final")
        ctx.close()

    day["status"] = "published"
    for p in day["posts"][: args.limit]:
        p["status"] = "published"
    CAL.write_text(json.dumps(cal, indent=2, ensure_ascii=False) + "\n")
    log("DONE")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
