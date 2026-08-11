#!/usr/bin/env python3
"""
Clean mu-plugins and deploy safe files while wp-config bypass is ACTIVE.
Run AFTER user re-added WPMU_PLUGIN_DIR bypass (wp-admin must work).
"""
import os, shutil, tempfile, time
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path("/workspace")
PROFILE = Path.home() / ".config/google-chrome"
SAFE_SEO = Path("/tmp/cindemir-seo-fixes-v172-safe.php")
UPLOAD = [
    SAFE_SEO,
    ROOT / "fixes/mu-plugins/cindemir-contact-fixes.php",
    ROOT / "fixes/mu-plugins/cindemir-expose-yoast-meta.php",
    ROOT / "fixes/mu-plugins/cindemir-purge-cache.php",
    ROOT / "fixes/mu-plugins/cindemir-force-upgrade.php",
]
DELETE_PREFIX = "cindemir"


def log(m):
    print(m, flush=True)


def click(page, sels):
    for frame in [page, *page.frames]:
        for s in sels:
            loc = frame.locator(s)
            if loc.count():
                try:
                    loc.first.click(timeout=5000)
                    page.wait_for_timeout(1200)
                    return True
                except Exception:
                    pass
    return False


def nav_mu(page):
    page.goto("https://cindemirlaw.com/wp-admin/admin.php?page=wp_file_manager", timeout=120000)
    page.wait_for_timeout(6000)
    if "login" in page.url.lower() or "critical error" in page.content().lower():
        log("FAIL: wp-admin not available — re-add wp-config bypass first")
        return False
    for name in ["wp-content", "mu-plugins"]:
        for frame in [page, *page.frames]:
            loc = frame.locator(f"text={name}")
            if loc.count():
                try:
                    loc.first.dblclick(timeout=5000)
                    page.wait_for_timeout(2000)
                    log(f"opened {name}")
                    break
                except Exception:
                    pass
    return True


def delete_cindemir_files(page):
  deleted = 0
  for _ in range(25):
    found = False
    for frame in [page, *page.frames]:
      loc = frame.locator("td, span, a").filter(has_text="cindemir")
      if not loc.count():
        continue
      for i in range(min(loc.count(), 30)):
        try:
          txt = loc.nth(i).inner_text(timeout=2000)
        except Exception:
          continue
        if not txt or DELETE_PREFIX not in txt.lower() or not txt.endswith(".php") and ".php" not in txt:
          if DELETE_PREFIX in txt.lower() and ".php" in txt:
            pass
          else:
            continue
        try:
          loc.nth(i).click(timeout=2000)
          page.wait_for_timeout(500)
          if click(page, ["text=Remove", "[title='Remove']", "text=Delete"]):
            click(page, ["button:has-text('YES')", "text=YES", "text=Confirm"])
            page.wait_for_timeout(2000)
            deleted += 1
            found = True
            log(f"deleted #{deleted}")
            break
        except Exception:
          pass
      if found:
        break
    if not found:
      break
  return deleted


def upload_one(page, fpath: Path, as_name: str | None = None) -> bool:
    name = as_name or fpath.name
    click(page, ["text=Upload", "[title='Upload files']"])
    page.wait_for_timeout(1500)
    for frame in [page, *page.frames]:
        ins = frame.locator('input[type="file"]')
        if ins.count():
            ins.first.set_input_files(str(fpath))
            page.wait_for_timeout(2000)
            click(page, ["button:has-text('YES')", "text=YES"])
            page.wait_for_timeout(10000)
            log(f"uploaded {name} ({fpath.stat().st_size} bytes)")
            return True
    return False


def main():
    if not SAFE_SEO.exists():
        log("missing safe seo file")
        return 1

    os.system("pkill -f google-chrome-stable 2>/dev/null")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="clean-"))
    for item in ["Default", "Local State"]:
        s = PROFILE / item
        if s.exists():
            d = tmp / item
            if s.is_dir():
                shutil.copytree(s, d, dirs_exist_ok=True)
            else:
                shutil.copy2(s, d)

    try:
        with sync_playwright() as p:
            ctx = p.chromium.launch_persistent_context(
                str(tmp), headless=False, channel="chrome",
                args=["--no-sandbox"], env={**os.environ, "DISPLAY": ":1"},
                viewport={"width": 1500, "height": 950},
            )
            page = ctx.pages[0] if ctx.pages else ctx.new_page()
            if not nav_mu(page):
                return 2

            n = delete_cindemir_files(page)
            log(f"total deleted: {n}")
            page.screenshot(path=str(ROOT / "fixes/clean-1.png"), full_page=True)

            nav_mu(page)
            # Phase 1: safe seo only
            upload_one(page, SAFE_SEO)
            page.screenshot(path=str(ROOT / "fixes/clean-2.png"), full_page=True)

            ctx.close()
            return 0
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


if __name__ == "__main__":
    raise SystemExit(main())
