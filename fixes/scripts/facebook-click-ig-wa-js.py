#!/usr/bin/env python3
"""Click Meta inbox CTAs via DOM text matching (Instagram approve + WhatsApp Başla)."""
from __future__ import annotations

import json
import os
import re
import shutil
import tempfile
time = __import__("time")
from pathlib import Path

from playwright.sync_api import sync_playwright

PROFILE_SRC = Path(os.environ.get("FB_PROFILE_DIR") or "/tmp/fb-good-session")
ASSET = "336218871992"
BIZ = "1161971660662915"
SHOT = Path("/workspace/fixes")
OUT = SHOT / "fb-click-ig-wa-status.json"


CLICK_JS = """
(label) => {
  const norm = (s) => (s || '').replace(/\\s+/g, ' ').trim();
  const target = norm(label).toLowerCase();
  const nodes = Array.from(document.querySelectorAll('div,span,a,button'));
  let best = null;
  for (const el of nodes) {
    const t = norm(el.innerText || el.textContent || '');
    if (!t) continue;
    // exact or short label match, avoid huge containers
    if (t.toLowerCase() === target || (t.length < 40 && t.toLowerCase().includes(target))) {
      const r = el.getBoundingClientRect();
      if (r.width > 5 && r.height > 5 && r.bottom > 0 && r.top < window.innerHeight) {
        // prefer smaller clickable nodes
        const score = r.width * r.height;
        if (!best || score < best.score) best = {el, score, text: t};
      }
    }
  }
  if (!best) return {ok:false, reason:'not_found'};
  best.el.scrollIntoView({block:'center'});
  best.el.click();
  return {ok:true, text: best.text, tag: best.el.tagName, score: best.score};
}
"""


def dismiss(page):
    for label in ["Belki daha sonra", "Maybe later", "Şimdi değil", "Not now", "Kapat"]:
        try:
            loc = page.get_by_role("button", name=re.compile(re.escape(label), re.I))
            if loc.count():
                loc.first.click(timeout=700)
        except Exception:
            pass


def click_label(page, label):
    res = page.evaluate(CLICK_JS, label)
    print("click", label, res, flush=True)
    page.wait_for_timeout(2500)
    return res


def main():
    os.system("ps -C chrome -o pid= 2>/dev/null | while read p; do kill -9 $p; done")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="fb-click-"))
    shutil.copytree(PROFILE_SRC, tmp, dirs_exist_ok=True)
    for n in ["SingletonLock", "SingletonCookie", "SingletonSocket"]:
        try:
            (tmp / n).unlink()
        except Exception:
            pass

    status = {"actions": [], "updated": time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime())}

    with sync_playwright() as p:
        ctx = p.chromium.launch_persistent_context(
            str(tmp),
            headless=False,
            channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage"],
            env={**os.environ, "DISPLAY": ":1"},
            viewport={"width": 1500, "height": 950},
            slow_mo=40,
        )
        page = ctx.pages[0] if ctx.pages else ctx.new_page()

        # Inbox
        page.goto(
            f"https://business.facebook.com/latest/inbox/all?asset_id={ASSET}&business_id={BIZ}",
            timeout=120000,
        )
        page.wait_for_timeout(6000)
        dismiss(page)
        page.screenshot(path=str(SHOT / "fb-click-0.png"))

        # Instagram approve
        r1 = click_label(page, "Erişimi onayla")
        status["actions"].append({"step": "ig_approve", "result": r1, "url": page.url})
        page.wait_for_timeout(3000)
        page.screenshot(path=str(SHOT / "fb-click-1-ig.png"))
        # dialogs
        for lab in ["İzin ver", "Allow", "Onayla", "Confirm", "Devam", "Continue", "Yeniden bağla", "Reconnect", "Tamam"]:
            r = click_label(page, lab)
            if r.get("ok"):
                status["actions"].append({"step": f"ig_dialog_{lab}", "result": r, "url": page.url})
                page.wait_for_timeout(2000)
                page.screenshot(path=str(SHOT / f"fb-click-ig-{lab.replace(' ', '_')}.png"))

        # Check IG state
        page.goto(
            f"https://business.facebook.com/latest/inbox/instagram_direct?asset_id={ASSET}&business_id={BIZ}",
            timeout=120000,
        )
        page.wait_for_timeout(5000)
        dismiss(page)
        ig_body = page.inner_text("body")
        status["instagram_after"] = {
            "url": page.url,
            "needs_approve": "Erişimi onayla" in ig_body,
            "disconnected": "Bağlantı kesildi" in ig_body,
            "no_messages_placeholder": "Gösterilecek mesaj yok" in ig_body,
            "shot": str(SHOT / "fb-click-2-ig-check.png"),
        }
        page.screenshot(path=str(SHOT / "fb-click-2-ig-check.png"))
        print("IG_AFTER", status["instagram_after"], flush=True)

        # WhatsApp Başla flow - stay on WA channel
        page.goto(
            f"https://business.facebook.com/latest/inbox/wec?asset_id={ASSET}&business_id={BIZ}",
            timeout=120000,
        )
        page.wait_for_timeout(5000)
        dismiss(page)
        page.screenshot(path=str(SHOT / "fb-click-3-wa.png"))
        r2 = click_label(page, "Başla")
        status["actions"].append({"step": "wa_basla", "result": r2, "url": page.url})
        page.wait_for_timeout(4000)
        page.screenshot(path=str(SHOT / "fb-click-4-wa-after-basla.png"))

        # In wizard, prefer selecting existing account carefully
        for lab in [
            "Cindemir Hukuk Bürosu / Cindemir Law Office",
            "Cindemir Hukuk Bürosu",
            "Devam",
            "Continue",
            "İleri",
            "Next",
            "Bağla",
            "Connect",
            "Onayla",
            "Confirm",
            "Tamam",
            "Done",
            "Bitir",
            "Finish",
        ]:
            # only click if still on whatsapp-related URL or dialog visible
            r = click_label(page, lab)
            if r.get("ok"):
                status["actions"].append({"step": f"wa_{lab}", "result": r, "url": page.url})
                page.wait_for_timeout(2500)
                page.screenshot(path=str(SHOT / f"fb-click-wa-{abs(hash(lab))%10000}.png"))
            # stop if redirected away from setup into random pages
            if "business_users" in page.url or "authorizations" in page.url:
                break

        page.wait_for_timeout(3000)
        page.screenshot(path=str(SHOT / "fb-click-5-wa-final.png"))
        wa_body = page.inner_text("body")
        status["whatsapp_after"] = {
            "url": page.url,
            "shows_basla": bool(re.search(r"\bBaşla\b", wa_body)) and "ortak bir WhatsApp" in wa_body,
            "shows_yeni": "WhatsApp" in wa_body and "Yeni" in wa_body,
            "snip": re.sub(r"\s+", " ", wa_body)[:1200],
        }
        print("WA_AFTER", {k: status["whatsapp_after"][k] for k in status["whatsapp_after"] if k != "snip"}, flush=True)

        # Final all-inbox snapshot
        page.goto(
            f"https://business.facebook.com/latest/inbox/all?asset_id={ASSET}&business_id={BIZ}",
            timeout=120000,
        )
        page.wait_for_timeout(5000)
        dismiss(page)
        page.screenshot(path=str(SHOT / "fb-click-6-final.png"))
        fb = page.inner_text("body")
        status["final"] = {
            "ig_approve": "Erişimi onayla" in fb,
            "ig_disconnected": "Bağlantı kesildi" in fb,
            "wa_setup_cta": "ortak bir WhatsApp" in fb or ("Başla" in fb and "WhatsApp" in fb),
            "url": page.url,
        }
        OUT.write_text(json.dumps(status, ensure_ascii=False, indent=2))
        print(json.dumps(status["final"], ensure_ascii=False, indent=2), flush=True)
        # sync cookies
        for name in ["Cookies", "Cookies-journal"]:
            s = tmp / "Default" / name
            if s.exists():
                shutil.copy2(s, PROFILE_SRC / "Default" / name)
        ctx.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
