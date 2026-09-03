#!/usr/bin/env python3
"""Publish a single calendar post by id, e.g. d10-3."""
import argparse
import json
import os
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
CAL = ROOT / "fixes/facebook-content-calendar.json"
PROFILE = Path("/home/ubuntu/.chrome-agent")
PAGE_HOME = (
    "https://www.facebook.com/p/Cindemir-Hukuk-B%C3%BCrosu-Cindemir-Law-Office-100066585793269/"
)


def close_noise(page):
    for _ in range(4):
        try:
            page.keyboard.press("Escape")
            time.sleep(0.25)
        except Exception:
            pass
    for label in ["Bitti", "Done", "Kapat", "Close", "Discard", "Vazgeç"]:
        try:
            loc = page.get_by_role("button", name=label)
            if loc.count():
                loc.first.click(timeout=1200, force=True)
                time.sleep(0.4)
        except Exception:
            pass
    # click overlay dismiss if group dialog text present
    try:
        body = page.locator("body").inner_text(timeout=5000)
        if "Gruplarda paylaş" in body or "Hiç grup bulunamadı" in body:
            b = page.get_by_text("Bitti", exact=True)
            if b.count():
                b.first.click(timeout=2000, force=True)
                time.sleep(0.5)
    except Exception:
        pass


def click_text(page, text, timeout=8000):
    loc = page.get_by_text(text, exact=True)
    if not loc.count():
        loc = page.get_by_text(text, exact=False)
    loc.last.click(timeout=timeout, force=True)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--id", required=True)
    args = ap.parse_args()

    cal = json.loads(CAL.read_text())
    post = None
    for day in cal["days"]:
        for p in day["posts"]:
            if p["id"] == args.id:
                post = p
                break
    if not post:
        raise SystemExit(f"post {args.id} not found")

    print(f"publishing {post['id']}: {post['title'][:70]}", flush=True)

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
        time.sleep(5)
        close_noise(page)
        time.sleep(1)

        opened = False
        for sel in [
            'span:has-text("Ne düşünüyorsun")',
            'span:has-text("Güncelleme yaz")',
            'div[role="button"]:has-text("Gönderi oluştur")',
        ]:
            if page.locator(sel).count():
                page.locator(sel).first.click(timeout=8000, force=True)
                opened = True
                break
        if not opened:
            raise RuntimeError("composer not found")
        time.sleep(3)

        # Prefer composer dialog editor (not comment box)
        editors = page.locator('div[role="dialog"] div[contenteditable="true"][role="textbox"]')
        if editors.count():
            editor = editors.first
        else:
            editor = page.locator(
                'div[contenteditable="true"][aria-placeholder*="ne düşünüyorsun" i], '
                'div[contenteditable="true"][aria-label*="Gönderi" i]'
            ).first
            if not editor.count():
                # last resort: first dialog textbox
                editor = page.locator('div[contenteditable="true"][role="textbox"]').first
        editor.click(timeout=8000, force=True)
        page.keyboard.type(post["summary"], delay=2)
        time.sleep(2)
        page.screenshot(path=str(ROOT / f"fixes/fb-pub-{args.id}-draft.png"), full_page=True)

        # İleri
        click_text(page, "İleri")
        print("clicked İleri", flush=True)
        time.sleep(3)
        page.screenshot(path=str(ROOT / f"fixes/fb-pub-{args.id}-settings.png"), full_page=True)

        body = page.locator("body").inner_text(timeout=10000)
        if "Gönderi ayarları" not in body and "Şimdi yayınla" not in body:
            raise RuntimeError("post settings dialog not reached after İleri")

        # Paylaş on settings step — exact role name only (avoid "Gruplarda paylaş" / boost copy)
        dialog = page.locator('div[role="dialog"]').filter(has_text="Şimdi yayınla")
        if not dialog.count():
            dialog = page.locator('div[role="dialog"]').filter(has_text="Gönderi ayarları")
        share = dialog.last.get_by_role("button", name="Paylaş", exact=True)
        if share.count():
            share.last.click(timeout=12000, force=True)
        else:
            page.evaluate(
                """() => {
                  const dialogs=[...document.querySelectorAll('[role=dialog]')];
                  const d=dialogs.find(x => x.innerText.includes('Şimdi yayınla')) || dialogs.at(-1);
                  const cand=[...d.querySelectorAll('[role=button],button')]
                    .filter(el => el.innerText.trim()==='Paylaş');
                  if(!cand.length) throw new Error('no Paylaş');
                  cand.at(-1).click();
                }"""
            )
        print("clicked Paylaş", flush=True)
        time.sleep(3)
        body = page.locator("body").inner_text(timeout=10000)
        if "Hiç grup bulunamadı" in body:
            page.screenshot(path=str(ROOT / f"fixes/fb-pub-{args.id}-groupfail.png"), full_page=True)
            close_noise(page)
            raise RuntimeError("opened group share dialog instead of publishing")

        for _ in range(15):
            t = page.locator("body").inner_text(timeout=8000)
            if "Paylaşılıyor" in t:
                time.sleep(2)
                continue
            break
        time.sleep(8)
        page.screenshot(path=str(ROOT / f"fixes/fb-pub-{args.id}-live.png"), full_page=True)
        ctx.close()
    print("DONE", flush=True)


if __name__ == "__main__":
    main()
