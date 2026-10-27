#!/usr/bin/env python3
"""
Publish one day's English article summaries to Cindemir Law Office FB page ONLY.
Usage: python3 fb-publish-day.py --day 1 [--design] [--no-post]
"""
import argparse
import json
import os
import re
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
CAL = ROOT / "fixes/facebook-content-calendar.json"
COVER = ROOT / "fixes/assets/fb-cover-cindemir.png"
LOGO = ROOT / "fixes/assets/fb-logo.jpg"
PROFILE = Path("/home/ubuntu/.chrome-agent")
LOG = ROOT / "fixes/facebook-publish.log"

PAGE_ID = "100066585793269"
PAGE_HOME = (
    "https://www.facebook.com/p/Cindemir-Hukuk-B%C3%BCrosu-Cindemir-Law-Office-100066585793269/"
)


def log(msg):
    print(msg, flush=True)
    with LOG.open("a") as f:
        f.write(msg + "\n")


def fb_safe_text(text: str) -> str:
    """Meta One blocks further posts that contain links/domains this month (2 used).

    Keep the English summary and title; drop URLs/domains so İleri can reach Paylaş.
    """
    t = re.sub(r"https?://\S+", "", text)
    t = re.sub(r"\b(?:www\.)?cindemir(?:law)?\.(?:av\.tr|com)(?:/\S*)?", "", t, flags=re.I)
    lines = []
    for ln in t.splitlines():
        s = ln.strip().lower().rstrip(":")
        if s in {"read the full article", "read more", "full article"}:
            continue
        lines.append(ln)
    t = re.sub(r"\n{3,}", "\n\n", "\n".join(lines)).strip()
    if "search this article title" not in t.lower():
        t += "\n\nSearch this article title on the Cindemir Law Office English site."
    return t


def snap(page, name):
    page.screenshot(path=str(ROOT / f"fixes/fb-pub-{name}.png"), full_page=True)


def close_popups(page, *, allow_escape=False):
    if allow_escape:
        for _ in range(2):
            try:
                page.keyboard.press("Escape")
                time.sleep(0.2)
            except Exception:
                pass
    for sel in ['[aria-label="Kapat"]', '[aria-label="Close"]']:
        try:
            loc = page.locator(sel)
            if loc.count():
                loc.last.click(timeout=1500, force=True)
        except Exception:
            pass
    try:
        body = page.locator("body").inner_text(timeout=4000)
        if "Meta One" in body and ("bağlantı" in body.lower() or "abon" in body.lower()):
            for sel in ['[aria-label="Kapat"]', '[aria-label="Close"]']:
                loc = page.locator(sel)
                if loc.count():
                    loc.last.click(timeout=1500, force=True)
                    time.sleep(0.5)
                    break
    except Exception:
        pass


def assert_page(page):
    url = page.url.lower()
    if PAGE_ID not in page.url and "cindemir" not in url:
        raise RuntimeError(f"not on Cindemir page: {page.url}")
    heading = ""
    for sel in ['h1', '[role="main"] h1', 'title']:
        try:
            loc = page.locator(sel)
            if loc.count():
                heading = loc.first.inner_text(timeout=3000)
                break
        except Exception:
            pass
    if heading and "Cindemir" not in heading and PAGE_ID not in page.url:
        raise RuntimeError(f"not on Cindemir page heading={heading!r} url={page.url}")
    # Identity check: reject the unrelated local-association page, not sidebar shortcuts
    if "karadeniz" in url or "eregli" in url:
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


def composer_present(page):
    for sel in [
        'span:has-text("Ne düşünüyorsun")',
        'span:has-text("Bir düşünceni paylaş")',
        'span:has-text("What\'s on your mind")',
        'span:has-text("Güncelleme yaz")',
        'div[role="button"]:has-text("Gönderi oluştur")',
        'div[role="button"]:has-text("Create post")',
        '[aria-label="Gönderi oluştur"]',
        '[aria-label="Create post"]',
    ]:
        try:
            if page.locator(sel).count():
                return sel
        except Exception:
            pass
    return ""


def switch_to_page(page):
    """Switch into Page identity if Facebook shows Geçiş Yap / Switch."""
    if composer_present(page):
        return True
    close_popups(page)
    for label in ["Şimdi Geçiş Yap", "Geçiş Yap", "Switch Now", "Switch"]:
        try:
            loc = page.get_by_role("button", name=label)
            if loc.count():
                loc.first.click(timeout=5000, force=True)
                time.sleep(8)
                log(f"switched to page ({label})")
                break
        except Exception:
            pass
        else:
            continue
        try:
            loc = page.get_by_text(label, exact=True)
            if loc.count():
                loc.first.click(timeout=5000, force=True)
                time.sleep(8)
                log(f"switched to page text ({label})")
                break
        except Exception:
            pass
    else:
        # JS fallback
        try:
            res = page.evaluate(
                """() => {
                  const labels=['Şimdi Geçiş Yap','Geçiş Yap','Switch Now','Switch'];
                  const btns=[...document.querySelectorAll('[role=button]')];
                  for (const label of labels) {
                    const hit=btns.find(el => (el.innerText||'').trim()===label);
                    if (hit) { hit.click(); return label; }
                  }
                  return '';
                }"""
            )
            if res:
                time.sleep(8)
                log(f"switched to page js ({res})")
        except Exception:
            pass

    # Reload as Page so composer appears
    page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
    time.sleep(6)
    close_popups(page)
    return bool(composer_present(page))


def open_composer(page):
    for sel in [
        'span:has-text("Ne düşünüyorsun")',
        'span:has-text("Bir düşünceni paylaş")',
        'span:has-text("What\'s on your mind")',
        'span:has-text("Güncelleme yaz")',
        'div[role="button"]:has-text("Gönderi oluştur")',
        'div[role="button"]:has-text("Create post")',
        '[aria-label="Gönderi oluştur"]',
        '[aria-label="Create post"]',
    ]:
        if page.locator(sel).count():
            page.locator(sel).first.click(timeout=8000, force=True)
            return True
    return False


def publish_post(page, text, idx):
    text = fb_safe_text(text)
    page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
    time.sleep(5)
    close_popups(page, allow_escape=True)
    assert_page(page)

    opened = False
    for attempt in range(3):
        switch_to_page(page)
        close_popups(page)
        if open_composer(page):
            opened = True
            break
        log(f"composer retry {attempt + 1}")
        time.sleep(3)
    if not opened:
        raise RuntimeError("composer not found — maybe not switched to page")
    time.sleep(3)

    editors = page.locator('div[role="dialog"] div[contenteditable="true"][role="textbox"]')
    editor = editors.first if editors.count() else page.locator('div[contenteditable="true"][role="textbox"]').last
    editor.click(timeout=8000)
    page.keyboard.type(text, delay=2)
    # wait for link preview / media spinner to settle
    for _ in range(20):
        time.sleep(1)
        try:
            if page.get_by_text("İleri", exact=True).count():
                break
        except Exception:
            pass
    time.sleep(3)
    snap(page, f"post-{idx}-draft")

    def dismiss_meta_one():
        """Close the Meta One upsell card (gray X, top-right of the card — not composer Close)."""
        try:
            info = page.evaluate(
                """() => {
                  const needle = 'Meta One ile bağlantılardan';
                  const nodes = [...document.querySelectorAll('span,div,h1,h2,h3')].filter(el => {
                    const t = (el.innerText || '').trim();
                    return t.startsWith(needle) && t.length < 90;
                  });
                  if (!nodes.length) return {r: 'none'};
                  nodes.sort((a, b) => {
                    const ra = a.getBoundingClientRect();
                    const rb = b.getBoundingClientRect();
                    return (ra.width * ra.height) - (rb.width * rb.height);
                  });
                  const hit = nodes[0];
                  let root = hit;
                  for (let i = 0; i < 22 && root.parentElement; i++) {
                    root = root.parentElement;
                    const txt = root.innerText || '';
                    const r = root.getBoundingClientRect();
                    const looksModal = r.width >= 360 && r.width <= 820 && r.height >= 220 && r.height <= 720
                      && (txt.includes("Meta One'a abone") || txt.includes('Daha fazla bilgi')
                          || txt.includes('bağlantı içeren'));
                    if (!looksModal) continue;
                    const closes = [...root.querySelectorAll('[aria-label], [role="button"], button')];
                    for (const el of closes) {
                      const a = (el.getAttribute('aria-label') || '').trim();
                      const b = el.getBoundingClientRect();
                      const inCorner = b.width > 8 && b.width < 56 && b.height < 56
                        && b.x > r.x + r.width * 0.72 && b.y < r.y + 70 && b.y >= r.y - 8;
                      if ((a === 'Kapat' || a === 'Close') && inCorner) {
                        el.click();
                        return {r: 'clicked', x: b.x + b.width / 2, y: b.y + b.height / 2};
                      }
                      if (inCorner && a !== 'Kapat' && a !== 'Close') {
                        // likely the circular X without a useful label
                        el.click();
                        return {r: 'corner', x: b.x + b.width / 2, y: b.y + b.height / 2};
                      }
                    }
                    return {
                      r: 'needmouse',
                      x: r.x + r.width - 22,
                      y: r.y + 22,
                      rw: r.width, rh: r.height, rx: r.x, ry: r.y,
                    };
                  }
                  const hb = hit.getBoundingClientRect();
                  return {r: 'heading', x: hb.x + hb.width - 8, y: hb.y - 18, hw: hb.width, hx: hb.x, hy: hb.y};
                }"""
            )
        except Exception as e:
            log(f"meta-one eval {e}")
            return False
        log(f"meta-one dismiss={info}")
        if info.get("r") in ("none",):
            return False
        if info.get("r") in ("clicked", "corner"):
            time.sleep(1)
            return True
        x, y = info.get("x"), info.get("y")
        if x and y:
            page.mouse.click(x, y)
            time.sleep(1)
            log(f"meta-one mouse {x:.0f},{y:.0f}")
            return True
        return False

    for _ in range(3):
        if not dismiss_meta_one():
            break

    def click_dialog_label(name):
        """Mouse-click the largest visible control with exact label inside a dialog."""
        info = page.evaluate(
            """(name) => {
              const hits = [];
              for (const d of document.querySelectorAll('[role=dialog]')) {
                const dt = (d.innerText || '').slice(0, 60);
                if (dt.includes('Bildirimler') && name !== 'Kapat') continue;
                for (const el of d.querySelectorAll('[role=button], button')) {
                  const t = (el.innerText || '').trim();
                  if (t !== name) continue;
                  const r = el.getBoundingClientRect();
                  if (r.width >= 48 && r.height >= 24 && r.y >= 0 && r.x >= 0)
                    hits.push({x: r.x + r.width / 2, y: r.y + r.height / 2, w: r.width, h: r.height, dt});
                }
              }
              hits.sort((a, b) => (b.w * b.h) - (a.w * a.h));
              return hits[0] || null;
            }""",
            name,
        )
        if not info:
            log(f"click {name}: no dialog hit")
            return False
        page.mouse.click(info["x"], info["y"])
        log(f"click {name} @ {info['x']:.0f},{info['y']:.0f} {info['w']:.0f}x{info['h']:.0f}")
        return True

    settings_ok = False
    for _ in range(8):
        dismiss_meta_one()
        if page.get_by_text("Gönderi ayarları", exact=True).count():
            settings_ok = True
            break
        if not click_dialog_label("İleri"):
            loc = page.get_by_text("İleri", exact=True)
            if loc.count():
                box = loc.last.bounding_box()
                if box:
                    page.mouse.click(box["x"] + box["width"] / 2, box["y"] + box["height"] / 2)
                    log("click İleri fallback bbox")
        time.sleep(2.5)
        dismiss_meta_one()
        if page.get_by_text("Gönderi ayarları", exact=True).count():
            settings_ok = True
            break
    if not settings_ok:
        page.get_by_text("Gönderi ayarları", exact=True).first.wait_for(state="visible", timeout=8000)
    time.sleep(1)
    if not click_dialog_label("Paylaş"):
        page.get_by_text("Paylaş", exact=True).last.click(timeout=12000, force=True)
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
    ap.add_argument(
        "--cdp",
        action="store_true",
        help="Attach to already-open desktop Chrome on 127.0.0.1:9222 (do not kill/relaunch)",
    )
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

    own_ctx = False
    if not args.cdp:
        # Avoid matching this shell command line: kill only chrome with this profile
        os.system(
            "pgrep -af 'user-data-dir=/home/ubuntu/.chrome-agent' | awk '{print $1}' | "
            "xargs -r kill -9 2>/dev/null"
        )
        time.sleep(2)

    with sync_playwright() as p:
        if args.cdp:
            browser = p.chromium.connect_over_cdp("http://127.0.0.1:9222")
            ctx = browser.contexts[0] if browser.contexts else browser.new_context()
            page = None
            for pg in ctx.pages:
                if "facebook.com" in pg.url:
                    page = pg
                    break
            if page is None:
                page = ctx.pages[0] if ctx.pages else ctx.new_page()
            page.bring_to_front()
            log(f"attached to desktop Chrome via CDP url={page.url[:80]}")
        else:
            own_ctx = True
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

        ok = 0
        fail = 0
        if not args.no_post:
            for i, post in enumerate(day["posts"][: args.limit], 1):
                if post.get("status") == "published":
                    log(f"post {i} already published, skip")
                    ok += 1
                    continue
                try:
                    publish_post(page, post["summary"], i)
                    post["status"] = "published"
                    ok += 1
                    time.sleep(8)
                except Exception as e:
                    log(f"post {i} err: {e}")
                    snap(page, f"post-{i}-err")
                    post["status"] = "failed"
                    fail += 1

        page.goto(PAGE_HOME, wait_until="domcontentloaded", timeout=120000)
        time.sleep(5)
        snap(page, "day-final")
        if own_ctx:
            ctx.close()

    all_pub = all(p.get("status") == "published" for p in day["posts"])
    if fail == 0 and ok > 0 and all_pub:
        day["status"] = "published"
    elif ok > 0:
        day["status"] = "partial"
    CAL.write_text(json.dumps(cal, indent=2, ensure_ascii=False) + "\n")
    log(f"DONE ok={ok} fail={fail}")
    return 0 if fail == 0 else 2


if __name__ == "__main__":
    raise SystemExit(main())
