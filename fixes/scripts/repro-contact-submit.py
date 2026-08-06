#!/usr/bin/env python3
"""Reproduce contact form submit failure on /contacts/."""
import json, time
from playwright.sync_api import sync_playwright

SITE = "https://cindemirlaw.com"
UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"


def main():
    t = int(time.time())
    with sync_playwright() as p:
        b = p.chromium.launch(headless=True)
        page = b.new_page(viewport={"width": 1280, "height": 900}, user_agent=UA)
        logs = []
        page.on("console", lambda m: logs.append({"type": m.type, "text": m.text[:300]}))
        page.on("pageerror", lambda e: logs.append({"type": "pageerror", "text": str(e)[:400]}))
        reqs = []
        page.on("request", lambda r: reqs.append({"method": r.method, "url": r.url[:200]}) if ("contact" in r.url or "ajax" in r.url.lower() or r.method == "POST") else None)
        resps = []
        page.on("response", lambda r: resps.append({"status": r.status, "url": r.url[:200], "method": r.request.method}) if r.request.method == "POST" else None)

        page.goto(f"{SITE}/contacts/?lang=en&nocache={t}", wait_until="domcontentloaded", timeout=120000)
        page.wait_for_timeout(5000)
        info = page.evaluate(
            """() => {
              const form = document.querySelector('form.avia_ajax_form');
              const fb = document.getElementById('cindemir-contact-form-fallback-js');
              const jq = !!(window.jQuery);
              const avia = !!(window.avia_framework_globals || window.aviaJS || window.avia);
              const handlers = form ? (typeof getEventListeners==='function' ? null : 'n/a') : null;
              return {
                hasForm: !!form,
                hasFallback: !!fb,
                jquery: jq,
                aviaGlobals: !!(window.avia_framework_globals),
                bound: form && form.dataset.cindemirBound,
                submitDisabled: !!(form && form.querySelector('[type=submit]')?.disabled),
                scripts: [...document.querySelectorAll('script[id^=cindemir]')].map(s=>s.id),
                errorBox: (document.querySelector('.ajaxresponse')||{}).className||null,
                formAction: form && form.action
              };
            }"""
        )
        print("INFO", json.dumps(info, indent=2))

        if info.get("hasForm"):
            page.fill("#avia_1_1", "Test User Cursor")
            page.fill("#avia_2_1", "cursor-test@example.com")
            page.fill("#avia_3_1", "+905551112233")
            page.fill("#avia_4_1", "Automated contact form test from Cursor agent. Please ignore.")
            # leave honeypot empty
            page.click('input[type="submit"]')
            page.wait_for_timeout(8000)
            after = page.evaluate(
                """() => {
                  const form = document.querySelector('form.avia_ajax_form');
                  const box = document.querySelector('.ajaxresponse');
                  return {
                    formDisplay: form ? getComputedStyle(form).display : null,
                    boxClass: box ? box.className : null,
                    boxText: box ? (box.innerText||'').slice(0,400) : null,
                    boxHTML: box ? (box.innerHTML||'').slice(0,500) : null,
                    buttonValue: form && form.querySelector('[type=submit]') ? form.querySelector('[type=submit]').value : null
                  };
                }"""
            )
            print("AFTER", json.dumps(after, indent=2))

        print("POSTS", json.dumps(resps, indent=2))
        print("REQ_POST", json.dumps([r for r in reqs if r["method"]=="POST"], indent=2))
        errs = [l for l in logs if l["type"] in ("error", "pageerror") or "TypeError" in l.get("text","")]
        print("ERRS", json.dumps(errs[:20], indent=2))
        page.screenshot(path="/workspace/fixes/contact-submit-repro.png")
        b.close()


if __name__ == "__main__":
    main()
