#!/usr/bin/env python3
"""
Schedule queued posts on Cindemir Law Office page.
Composer → İleri → Şimdi yayınla → set date/time → Sonrası için planla → Planla
"""
import argparse
import json
import os
import time
from datetime import datetime
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
CAL = ROOT / "fixes/facebook-content-calendar.json"
COVER = ROOT / "fixes/assets/fb-cover-cindemir.png"
PROFILE = Path("/tmp/fb-jifkln9c")
LOG = ROOT / "fixes/facebook-schedule.log"
PAGE_HOME = (
    "https://www.facebook.com/p/Cindemir-Hukuk-B%C3%BCrosu-Cindemir-Law-Office-100066585793269/"
)

TR_MONTHS = {
    1: "Oca", 2: "Şub", 3: "Mar", 4: "Nis", 5: "May", 6: "Haz",
    7: "Tem", 8: "Ağu", 9: "Eyl", 10: "Eki", 11: "Kas", 12: "Ara",
}


def log(msg):
    print(msg, flush=True)
    with LOG.open("a") as f:
        f.write(msg + "\n")


def snap(page, name):
    page.screenshot(path=str(ROOT / f"fixes/fb-sched-{name}.png"), full_page=True)


def close_popups(page):
    for _ in range(3):
        try:
            page.keyboard.press("Escape")
            time.sleep(0.3)
        except Exception:
            pass
    for sel in ['[aria-label="Kapat"]', '[aria-label="Close"]', '[aria-label="Dismiss"]']:
        try:
            loc = page.locator(sel)
            if loc.count():
                loc.first.click(timeout=1000, force=True)
        except Exception:
            pass


def upload_cover(page):
    page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
    time.sleep(5)
    close_popups(page)
    for sel in [
        'div[role="button"]:has-text("Kapak Fotoğrafını Düzenle")',
        '[aria-label="Kapak Fotoğrafını Düzenle"]',
        '[aria-label="Edit cover photo"]',
    ]:
        if page.locator(sel).count():
            page.locator(sel).first.click(timeout=6000)
            break
    time.sleep(2)
    for t in ["Fotoğraf Yükle", "Upload Photo", "Fotoğraf yükle"]:
        loc = page.get_by_text(t, exact=False)
        if loc.count():
            with page.expect_file_chooser(timeout=10000) as fc:
                loc.first.click(timeout=5000)
            fc.value.set_files(str(COVER))
            break
    else:
        page.locator('input[type="file"]').first.set_input_files(str(COVER))
    time.sleep(10)
    for t in ["Kaydet", "Save"]:
        b = page.get_by_role("button", name=t)
        if b.count():
            b.last.click(timeout=8000)
            time.sleep(5)
            log("cover uploaded")
            return True
    return False


def tr_date(when: datetime) -> str:
    return f"{when.day} {TR_MONTHS[when.month]} {when.year}"


def set_datetime_fields(page, when: datetime):
    date_val = tr_date(when)
    time_val = when.strftime("%H:%M")

    # Click the date chip (e.g. "18 Tem 2026") then type
    # Match Tem/Ağu etc. or any day-month-year pattern in dialog
    dlg = page.locator('div[role="dialog"]').filter(has_text="Sonrası için planla")
    if not dlg.count():
        dlg = page.locator('div[role="dialog"]').last

    # Date/time are custom chips, NOT file inputs
    inputs = dlg.locator('input:not([type="file"]):visible')
    if inputs.count() >= 2:
        inputs.nth(0).click(timeout=3000)
        page.keyboard.press("Control+a")
        page.keyboard.type(date_val, delay=20)
        inputs.nth(1).click(timeout=3000)
        page.keyboard.press("Control+a")
        page.keyboard.type(time_val, delay=20)
        return date_val, time_val

    # Click date chip containing month abbrev (e.g. "18 Tem 2026")
    date_clicked = False
    for month in TR_MONTHS.values():
        loc = dlg.locator(f'span:has-text("{month}")')
        if loc.count():
            loc.first.click(timeout=4000)
            time.sleep(0.5)
            # After click, a real input/textbox may appear
            editable = page.locator(
                'div[role="dialog"] input:not([type="file"]):visible, '
                'div[role="dialog"] [role="textbox"]:visible, '
                'div[role="dialog"] [contenteditable="true"]:visible'
            )
            if editable.count():
                editable.first.click(timeout=3000)
                page.keyboard.press("Control+a")
                page.keyboard.type(date_val, delay=20)
                page.keyboard.press("Enter")
            else:
                page.keyboard.press("Control+a")
                page.keyboard.type(date_val, delay=20)
                page.keyboard.press("Enter")
            date_clicked = True
            break
    if not date_clicked:
        log("warn: date chip not found")

    time.sleep(0.5)

    # Time chip like "12:04"
    time_clicked = False
    candidates = dlg.locator("span")
    for i in range(min(candidates.count(), 40)):
        try:
            txt = candidates.nth(i).inner_text().strip()
        except Exception:
            continue
        if len(txt) in (4, 5) and ":" in txt and txt.replace(":", "").isdigit():
            candidates.nth(i).click(timeout=3000)
            time.sleep(0.4)
            editable = page.locator(
                'div[role="dialog"] input:not([type="file"]):visible, '
                'div[role="dialog"] [role="textbox"]:visible'
            )
            if editable.count():
                editable.last.click(timeout=3000)
                page.keyboard.press("Control+a")
                page.keyboard.type(time_val, delay=20)
                page.keyboard.press("Enter")
            else:
                page.keyboard.press("Control+a")
                page.keyboard.type(time_val, delay=20)
                page.keyboard.press("Enter")
            time_clicked = True
            break
    if not time_clicked:
        log("warn: time chip not found")

    return date_val, time_val


def wait_dialogs_gone(page, timeout=15000):
    deadline = time.time() + timeout / 1000
    while time.time() < deadline:
        # composer/settings dialogs should close after successful Planla
        n = page.locator('div[role="dialog"]').count()
        if n == 0:
            return True
        close_popups(page)
        time.sleep(0.5)
    return False


def schedule_one(page, post, idx):
    when = datetime.fromisoformat(post["scheduled_at"])
    page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
    time.sleep(4)
    close_popups(page)
    time.sleep(1)

    opened = False
    for sel in [
        'span:has-text("Ne düşünüyorsun")',
        'span:has-text("Güncelleme yaz")',
        'div[role="button"]:has-text("Gönderi oluştur")',
    ]:
        loc = page.locator(sel)
        if loc.count():
            loc.first.click(timeout=8000, force=True)
            opened = True
            break
    if not opened:
        raise RuntimeError("composer not found")
    time.sleep(2)

    editor = page.locator('div[contenteditable="true"][role="textbox"]').last
    editor.click(timeout=10000, force=True)
    page.keyboard.press("Control+a")
    page.keyboard.type(post["summary"], delay=1)
    time.sleep(0.5)

    page.get_by_role("button", name="İleri").first.click(timeout=8000, force=True)
    time.sleep(2.5)

    page.get_by_text("Şimdi yayınla", exact=False).first.click(timeout=6000, force=True)
    time.sleep(1.5)

    date_val, time_val = set_datetime_fields(page, when)
    time.sleep(0.5)
    if idx <= 2 or idx % 25 == 0:
        snap(page, f"{idx}-plan")

    page.get_by_role("button", name="Sonrası için planla").first.click(timeout=10000, force=True)
    time.sleep(2.5)

    planla = page.get_by_role("button", name="Planla", exact=True)
    if not planla.count():
        log(f"FAIL {post['id']} no Planla")
        snap(page, f"{idx}-fail")
        close_popups(page)
        return False
    planla.click(timeout=10000, force=True)
    time.sleep(4)
    wait_dialogs_gone(page, 12000)
    # hard reset page state
    page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=90000)
    time.sleep(2)
    close_popups(page)
    log(f"OK {post['id']} -> {date_val} {time_val}")
    return True


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--limit", type=int, default=0)
    ap.add_argument("--start-day", type=int, default=2)
    ap.add_argument("--cover", action="store_true")
    ap.add_argument("--reset-failed", action="store_true")
    args = ap.parse_args()

    with LOG.open("a") as f:
        f.write(f"\n--- run {datetime.now().isoformat()} ---\n")

    cal = json.loads(CAL.read_text())
    if args.reset_failed:
        for d in cal["days"]:
            for p in d["posts"]:
                if p.get("status") == "failed":
                    p["status"] = "queued"

    posts = []
    for d in cal["days"]:
        if d["day"] < args.start_day:
            continue
        for p in d["posts"]:
            if p.get("status") == "queued":
                posts.append(p)
    if args.limit:
        posts = posts[: args.limit]

    log(f"to schedule: {len(posts)}")
    os.system("pkill -9 chrome 2>/dev/null")
    time.sleep(3)

    ok = fail = 0
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

        if args.cover:
            try:
                upload_cover(page)
            except Exception as e:
                log(f"cover err: {e}")

        for i, post in enumerate(posts, 1):
            success = False
            for attempt in range(2):
                try:
                    close_popups(page)
                    success = schedule_one(page, post, i)
                    if success:
                        break
                except Exception as e:
                    log(f"err {post['id']} try{attempt+1}: {e}")
                    snap(page, f"{i}-exc")
                    close_popups(page)
                    time.sleep(2)
            if success:
                post["status"] = "scheduled"
                ok += 1
            else:
                post["status"] = "failed"
                fail += 1
            CAL.write_text(json.dumps(cal, indent=2, ensure_ascii=False) + "\n")
            if i % 5 == 0:
                log(f"progress {i}/{len(posts)} ok={ok} fail={fail}")
            time.sleep(1)

        ctx.close()

    for d in cal["days"]:
        if d["day"] < args.start_day:
            continue
        st = {p["status"] for p in d["posts"]}
        if st <= {"scheduled"}:
            d["status"] = "scheduled"
        elif "scheduled" in st:
            d["status"] = "partial"
    CAL.write_text(json.dumps(cal, indent=2, ensure_ascii=False) + "\n")
    log(f"DONE ok={ok} fail={fail}")
    return 0 if fail == 0 else 1


if __name__ == "__main__":
    raise SystemExit(main())
