#!/usr/bin/env python3
"""
Publish one day's English article summaries to Cindemir Law Office FB page ONLY.
Usage: python3 fb-publish-day.py --day 1 [--design] [--no-post]
"""
import argparse
import json
import os
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
CAL = ROOT / "fixes/facebook-content-calendar.json"
COVER = ROOT / "fixes/assets/fb-cover-cindemir.png"
LOGO = ROOT / "fixes/assets/fb-logo.jpg"
PROFILE = Path("/tmp/fb-jifkln9c")
LOG = ROOT / "fixes/facebook-publish.log"

PAGE_ID = "100066585793269"
PAGE_HOME = (
    "https://www.facebook.com/p/Cindemir-Hukuk-B%C3%BCrosu-Cindemir-Law-Office-100066585793269/"
)


def log(msg):
    print(msg, flush=True)
    with LOG.open("a") as f:
        f.write(msg + "\n")


def snap(page, name):
    page.screenshot(path=str(ROOT / f"fixes/fb-pub-{name}.png"), full_page=True)


def close_popups(page):
    for sel in ['[aria-label="Kapat"]', '[aria-label="Close"]']:
        try:
            if page.locator(sel).count():
                page.locator(sel).first.click(timeout=1500)
        except Exception:
            pass


def assert_page(page):
    body = page.locator("body").inner_text(timeout=15000)
    if "Cindemir" not in body:
        raise RuntimeError(f"not on Cindemir page: {page.url}")
    if "Karadeniz" in body or "Ereğli" in body:
        raise RuntimeError("wrong business page")


def upload_cover(page):
    if not COVER.exists():
        log("cover missing")
        return False
    page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
    time.sleep(6)
    close_popups(page)
    assert_page(page)

    # Edit cover
    for sel in [
        'div[role="button"]:has-text("Kapak Fotoğrafını Düzenle")',
        'div[role="button"]:has-text("Edit Cover Photo")',
        '[aria-label="Kapak Fotoğrafını Düzenle"]',
        '[aria-label="Edit cover photo"]',
    ]:
        if page.locator(sel).count():
            page.locator(sel).first.click(timeout=6000)
            break
    else:
        # hover cover area
        page.locator('img[data-imgperflogname="profileCoverPhoto"]').click(timeout=5000)
        time.sleep(1)
        page.get_by_text("Kapak Fotoğrafını Düzenle", exact=False).first.click(timeout=6000)

    time.sleep(2)
    for t in ["Fotoğraf Yükle", "Upload Photo", "Fotoğraf yükle", "Upload photo"]:
        loc = page.get_by_text(t, exact=False)
        if loc.count():
            with page.expect_file_chooser() as fc:
                loc.first.click(timeout=5000)
            fc.value.set_files(str(COVER))
            log("cover file selected")
            break
    else:
        # file input fallback
        inp = page.locator('input[type="file"]')
        if inp.count():
            inp.first.set_input_files(str(COVER))
            log("cover via input")
        else:
            log("cover upload UI not found")
            snap(page, "cover-fail")
            return False

    time.sleep(8)
    snap(page, "cover-upload")
    for t in ["Kaydet", "Save", "Bitti", "Done"]:
        b = page.get_by_role("button", name=t)
        if b.count():
            try:
                b.last.click(timeout=8000)
                log(f"cover saved ({t})")
                time.sleep(6)
                return True
            except Exception:
                pass
    return False


def update_bio(page, bio):
    about = f"https://www.facebook.com/profile.php?id={PAGE_ID}&sk=about"
    page.goto(about, wait_until="domcontentloaded", timeout=120000)
    time.sleep(5)
    close_popups(page)
    page.get_by_text("Künye", exact=True).first.click(timeout=5000)
    time.sleep(2)
    btn = page.get_by_label("Biyografiyi düzenle")
    if btn.count():
        btn.first.click(timeout=6000)
        time.sleep(2)
        ta = page.locator("textarea:visible")
        if ta.count():
            ta.first.fill(bio[:255])
            time.sleep(1)
            page.get_by_role("button", name="Kaydet").click(timeout=6000)
            log("bio updated")
            time.sleep(3)
            return True
    log("bio skip")
    return False


def publish_post(page, text, idx):
    page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
    time.sleep(5)
    close_popups(page)
    assert_page(page)

    # open composer
    for sel in [
        'span:has-text("Ne düşünüyorsun")',
        'span:has-text("Güncelleme yaz")',
        'div[role="button"]:has-text("Gönderi oluştur")',
    ]:
        if page.locator(sel).count():
            page.locator(sel).first.click(timeout=8000)
            break
    time.sleep(3)

    editor = page.locator('div[contenteditable="true"][role="textbox"]').last
    editor.click(timeout=8000)
    page.keyboard.type(text, delay=2)
    time.sleep(2)
    snap(page, f"post-{idx}-draft")

    # Next then Share (new Pages UI) OR direct Paylaş
    page.get_by_text("İleri", exact=True).last.click(timeout=8000, force=True)
    time.sleep(3)

    dialog = page.locator('div[role="dialog"]').filter(has_text="Şimdi yayınla")
    if not dialog.count():
        dialog = page.locator('div[role="dialog"]').filter(has_text="Gönderi ayarları")
    share = dialog.last.get_by_role("button", name="Paylaş", exact=True) if dialog.count() else page.get_by_role("button", name="Paylaş", exact=True)
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
    time.sleep(4)
    # dismiss accidental group-share dialog
    body = page.locator("body").inner_text(timeout=10000)
    if "Hiç grup bulunamadı" in body:
        close_popups(page)
        for t in ["Bitti", "Done", "Kapat"]:
            b = page.get_by_role("button", name=t)
            if b.count():
                try:
                    b.first.click(timeout=2000, force=True)
                except Exception:
                    pass
        raise RuntimeError("opened group share dialog instead of publishing")
    time.sleep(8)
    snap(page, f"post-{idx}-live")
    log(f"published post {idx}")
    return True


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--day", type=int, default=1)
    ap.add_argument("--design", action="store_true", help="upload cover + refresh bio")
    ap.add_argument("--no-post", action="store_true")
    ap.add_argument("--limit", type=int, default=5)
    args = ap.parse_args()

    LOG.write_text("")
    cal = json.loads(CAL.read_text())
    day = next((d for d in cal["days"] if d["day"] == args.day), None)
    if not day:
        log(f"day {args.day} not found")
        return 1

    # ensure logo cached
    if not LOGO.exists():
        os.system(
            'curl -sL -A "Mozilla/5.0" '
            '"https://cindemirlaw.com/wp-content/uploads/2020/06/cropped-logoicon-1-1-300x300.jpg" '
            f"-o {LOGO}"
        )

    os.system("pkill -9 -f 'google-chrome.*fb-jifkln9c' 2>/dev/null")
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

        if args.design:
            try:
                upload_cover(page)
            except Exception as e:
                log(f"cover err: {e}")
                snap(page, "cover-err")
            try:
                update_bio(page, cal["brand"]["bio"])
            except Exception as e:
                log(f"bio err: {e}")

        if not args.no_post:
            for i, post in enumerate(day["posts"][: args.limit], 1):
                try:
                    publish_post(page, post["summary"], i)
                    time.sleep(8)
                except Exception as e:
                    log(f"post {i} err: {e}")
                    snap(page, f"post-{i}-err")

        page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
        time.sleep(5)
        snap(page, "day-final")
        ctx.close()

    day["status"] = "published"
    CAL.write_text(json.dumps(cal, indent=2, ensure_ascii=False) + "\n")
    log("DONE")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
