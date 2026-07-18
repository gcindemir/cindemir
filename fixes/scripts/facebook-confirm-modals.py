#!/usr/bin/env python3
"""Complete Instagram message-access modal + WhatsApp phone-number continue flow."""
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
SHOT = Path("/workspace/fixes")
OUT = SHOT / "fb-confirm-modals-status.json"

FIND_CLICK = """
({needle, maxLen}) => {
  const norm = (s) => (s || '').replace(/\\s+/g, ' ').trim();
  const target = norm(needle).toLowerCase();
  const nodes = Array.from(document.querySelectorAll('div[role="button"], button, span, a, div'));
  let best = null;
  for (const el of nodes) {
    const t = norm(el.innerText || el.textContent || '');
    if (!t) continue;
    if (t.length > (maxLen || 48)) continue;
    if (t.toLowerCase() === target || t.toLowerCase().includes(target)) {
      const r = el.getBoundingClientRect();
      if (r.width < 8 || r.height < 8) continue;
      if (r.bottom < 0 || r.top > window.innerHeight) continue;
      const score = r.width * r.height;
      // Prefer role=button / button and smaller nodes
      const role = el.getAttribute('role') || '';
      const bonus = (el.tagName === 'BUTTON' || role === 'button') ? -50000 : 0;
      const total = score + bonus;
      if (!best || total < best.total) best = {el, total, text: t, tag: el.tagName, role};
    }
  }
  if (!best) return {ok:false};
  best.el.scrollIntoView({block:'center'});
  best.el.click();
  return {ok:true, text:best.text, tag:best.tag, role:best.role, total:best.total};
}
"""


def dismiss(page):
    for label in ["Belki daha sonra", "Maybe later", "Şimdi değil", "Not now"]:
        try:
            loc = page.get_by_role("button", name=re.compile(re.escape(label), re.I))
            if loc.count():
                loc.first.click(timeout=700)
        except Exception:
            pass


def click_short(page, needle, max_len=40):
    res = page.evaluate(FIND_CLICK, {"needle": needle, "maxLen": max_len})
    print("click", needle, res, flush=True)
    page.wait_for_timeout(2200)
    return res


def main():
    os.system("ps -C chrome -o pid= 2>/dev/null | while read p; do kill -9 $p; done")
    time.sleep(2)
    tmp = Path(tempfile.mkdtemp(prefix="fb-confirm-"))
    shutil.copytree(PROFILE_SRC, tmp, dirs_exist_ok=True)
    for n in ["SingletonLock", "SingletonCookie", "SingletonSocket"]:
        try:
            (tmp / n).unlink()
        except Exception:
            pass

    status = {"ig": {}, "wa": {}, "updated": time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime())}

    with sync_playwright() as p:
        ctx = p.chromium.launch_persistent_context(
            str(tmp),
            headless=False,
            channel="chrome",
            args=["--no-sandbox", "--disable-dev-shm-usage"],
            env={**os.environ, "DISPLAY": ":1"},
            viewport={"width": 1500, "height": 950},
            slow_mo=45,
        )
        page = ctx.pages[0] if ctx.pages else ctx.new_page()

        # ===== Instagram modal =====
        page.goto(
            f"https://business.facebook.com/latest/inbox/instagram_direct?asset_id={ASSET}&business_id={BIZ}",
            timeout=120000,
        )
        page.wait_for_timeout(5000)
        dismiss(page)
        # Open access UI
        click_short(page, "Erişimi onayla", 30)
        page.wait_for_timeout(2500)
        page.screenshot(path=str(SHOT / "fb-confirm-ig-1.png"))
        body = page.inner_text("body")
        status["ig"]["modal_visible"] = "Confirm Instagram message access" in body or "Instagram messages are unavailable" in body
        # Click the modal primary button (short label only)
        r = click_short(page, "Erişimi onayla", 24)
        # English button variants inside modal
        if status["ig"]["modal_visible"]:
            for lab in ["Confirm", "Allow", "Continue", "Onayla", "İzin ver", "Devam"]:
                rr = click_short(page, lab, 20)
                if rr.get("ok") and rr.get("text", "").lower() in {lab.lower(), "erişimi onayla", "confirm", "allow"}:
                    status["ig"]["modal_click"] = rr
                    break
        page.wait_for_timeout(4000)
        page.screenshot(path=str(SHOT / "fb-confirm-ig-2.png"))
        b2 = page.inner_text("body")
        status["ig"].update(
            {
                "url": page.url,
                "still_approve": "Erişimi onayla" in b2,
                "still_disconnected": "Bağlantı kesildi" in b2,
                "modal_still": "Confirm Instagram message access" in b2,
                "first_click": r,
            }
        )
        print("IG", json.dumps(status["ig"], ensure_ascii=False), flush=True)

        # ===== WhatsApp phone continue =====
        page.goto(
            f"https://business.facebook.com/latest/inbox/wec?asset_id={ASSET}&business_id={BIZ}",
            timeout=120000,
        )
        page.wait_for_timeout(5000)
        dismiss(page)
        page.screenshot(path=str(SHOT / "fb-confirm-wa-1.png"))
        b = page.inner_text("body")
        if "Başla" in b and "ortak bir WhatsApp" in b:
            click_short(page, "Başla", 16)
            page.wait_for_timeout(3000)
        page.screenshot(path=str(SHOT / "fb-confirm-wa-2.png"))
        b = page.inner_text("body")
        status["wa"]["phone_modal"] = "Hangi telefon numarasını" in b or "Which phone number" in b or "+90" in b
        # Ensure number selected then Devam
        if "+90" in b or "550 67 75" in b or "5506775" in b.replace(" ", ""):
            click_short(page, "Devam", 16)
            page.wait_for_timeout(4000)
        page.screenshot(path=str(SHOT / "fb-confirm-wa-3.png"))
        # Possible QR / app confirmation screens
        for lab in ["Devam", "İleri", "Next", "Onayla", "Confirm", "Bitir", "Done", "Anladım", "Got it"]:
            bb = page.inner_text("body")
            if "QR" in bb or "WhatsApp Business Uygulaması" in bb or "scan" in bb.lower():
                status["wa"]["needs_phone_app_scan"] = True
            rlab = click_short(page, lab, 18)
            if rlab.get("ok"):
                status.setdefault("wa_clicks", []).append(rlab)
                page.wait_for_timeout(2500)
                page.screenshot(path=str(SHOT / f"fb-confirm-wa-{lab}.png"))
        page.screenshot(path=str(SHOT / "fb-confirm-wa-final.png"))
        wb = page.inner_text("body")
        status["wa"].update(
            {
                "url": page.url,
                "shows_setup_landing": "ortak bir WhatsApp" in wb,
                "shows_basla": bool(re.search(r"\bBaşla\b", wb)) and "ortak bir WhatsApp" in wb,
                "snip": re.sub(r"\s+", " ", wb)[:1400],
            }
        )
        print("WA", json.dumps({k: status["wa"][k] for k in status["wa"] if k != "snip"}, ensure_ascii=False), flush=True)

        # Final inbox
        page.goto(
            f"https://business.facebook.com/latest/inbox/all?asset_id={ASSET}&business_id={BIZ}",
            timeout=120000,
        )
        page.wait_for_timeout(5000)
        dismiss(page)
        page.screenshot(path=str(SHOT / "fb-confirm-final.png"))
        fb = page.inner_text("body")
        status["final"] = {
            "ig_approve_btn": "Erişimi onayla" in fb,
            "ig_disconnected": "Bağlantı kesildi" in fb,
            "wa_landing": "ortak bir WhatsApp" in fb,
            "url": page.url,
        }
        OUT.write_text(json.dumps(status, ensure_ascii=False, indent=2))
        print(json.dumps(status["final"], ensure_ascii=False, indent=2), flush=True)
        for name in ["Cookies", "Cookies-journal"]:
            s = tmp / "Default" / name
            if s.exists():
                shutil.copy2(s, PROFILE_SRC / "Default" / name)
        ctx.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
