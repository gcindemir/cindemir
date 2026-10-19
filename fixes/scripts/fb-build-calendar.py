#!/usr/bin/env python3
"""Build full 5/day English FB content calendar from article URL list."""
import json
import re
from datetime import date, datetime, timedelta, timezone
from pathlib import Path

ROOT = Path("/workspace")
URLS = json.loads((ROOT / "fixes/facebook-all-urls.json").read_text())
EXISTING = json.loads((ROOT / "fixes/facebook-articles.json").read_text())
OUT = ROOT / "fixes/facebook-content-calendar.json"

# Istanbul = UTC+3 (no DST in TR since 2016)
TR = timezone(timedelta(hours=3))
START = date(2026, 7, 19)  # day after already-published batch
TIMES = ["10:00", "12:30", "15:00", "17:30", "20:00"]  # local TR

TR_SLUGS = re.compile(
    r"(yabanci|hukumlu|tutuklu|turk-hukuku|hakkinda|bilgilendirme|/cisg-/)",
    re.I,
)

PUBLISHED = {
    "https://cindemirlaw.com/opening-a-company-in-turkey-for-foreigners/",
    "https://cindemirlaw.com/deportation-law-in-turkey/",
    "https://cindemirlaw.com/debt-recovery-in-turkey/",
    "https://cindemirlaw.com/consensual-divorce-in-turkey-uncontested-divorce/",
    "https://cindemirlaw.com/getting-criminal-record-in-turkey/",
}

HOOKS = {
    "company": "Forming a company in Türkiye as a foreigner has clear registration and compliance steps.",
    "deport": "Deportation and removal decisions under Turkish immigration law carry strict timelines and appeal rights.",
    "debt": "Debt recovery in Türkiye often runs through enforcement offices, mediation and court procedure.",
    "divorce": "Uncontested divorce in Türkiye can move faster when spouses settle custody and property in a protocol.",
    "criminal": "Turkish criminal-record and police-clearance documents are often required for visas and residence abroad.",
    "airport": "Airport detention or customs issues can escalate quickly for foreign travellers in Türkiye.",
    "real": "Foreign property purchases in Türkiye involve title, zoning and currency rules that need a legal check.",
    "citizen": "Turkish citizenship pathways are set out in Law No. 5901 — residence, marriage, investment and more.",
    "inherit": "Cross-border inheritance with Turkish assets raises probate, forced-heir and documentation issues.",
    "crypto": "Crypto-asset disputes and fraud in Türkiye sit at the intersection of criminal, civil and regulatory law.",
    "eu": "EU-facing compliance (GDPR, AI Act) often requires a non-EU company to appoint a legal representative.",
    "prison": "Visiting or assisting a foreign detainee in a Turkish prison requires procedure-aware counsel.",
    "default": "Practical English guidance from Cindemir Law Office for foreign clients dealing with Turkish law.",
}


def title_from_url(url: str) -> str:
    slug = url.rstrip("/").split("/")[-1]
    t = slug.replace("-", " ").strip()
    # keep acronyms
    words = []
    for w in t.split():
        if w.lower() in {"eu", "ai", "gdpr", "cisg", "echr", "spk", "poa"}:
            words.append(w.upper())
        elif w.lower() in {"in", "of", "and", "for", "to", "the", "a", "an", "under", "on"}:
            words.append(w.lower())
        else:
            words.append(w.capitalize())
    if words:
        words[0] = words[0].capitalize()
    return " ".join(words)


def hook_for(url: str, title: str) -> str:
    u = (url + " " + title).lower()
    for key, phrase in [
        ("crypto", HOOKS["crypto"]),
        ("token", HOOKS["crypto"]),
        ("airport", HOOKS["airport"]),
        ("customs", HOOKS["airport"]),
        ("deport", HOOKS["deport"]),
        ("entry-ban", HOOKS["deport"]),
        ("company", HOOKS["company"]),
        ("debt", HOOKS["debt"]),
        ("divorce", HOOKS["divorce"]),
        ("criminal", HOOKS["criminal"]),
        ("police", HOOKS["criminal"]),
        ("real-estate", HOOKS["real"]),
        ("title-deed", HOOKS["real"]),
        ("citizenship", HOOKS["citizen"]),
        ("inherit", HOOKS["inherit"]),
        ("gdpr", HOOKS["eu"]),
        ("ai-act", HOOKS["eu"]),
        ("eu-", HOOKS["eu"]),
        ("prison", HOOKS["prison"]),
        ("inmate", HOOKS["prison"]),
        ("detain", HOOKS["prison"]),
    ]:
        if key in u:
            return phrase
    return HOOKS["default"]


def summary(url: str, title: str, desc: str = "", excerpt: str = "") -> str:
    hook = hook_for(url, title)
    body = ""
    if excerpt and len(excerpt) > 60 and "Author:" not in excerpt:
        body = excerpt.strip()
        if len(body) > 220:
            body = body[:217].rsplit(" ", 1)[0] + "…"
    elif desc and len(desc) > 40 and not desc.startswith("本文"):
        body = desc.strip()
        if len(body) > 220:
            body = body[:217].rsplit(" ", 1)[0] + "…"
    else:
        body = f"This guide covers the key procedures, documents and risks for foreign clients — {title.lower()}."

    return f"{hook}\n\n{body}\n\nRead the full article:\n{url}"


def main():
    meta = {a["url"]: a for a in EXISTING}
    cleaned = []
    for u in URLS:
        if u in PUBLISHED:
            continue
        if TR_SLUGS.search(u):
            continue
        if u.rstrip("/").endswith("/cisg"):
            continue
        cleaned.append(u)

    # Prefer known P0/P1 order first if present
    priority = [
        "https://cindemirlaw.com/airport-detention-in-turkey-legal-risks-for-foreign-travellers-and-how-to-respond/",
        "https://cindemirlaw.com/sales-of-real-estate-to-foreigners-in-turkey/",
        "https://cindemirlaw.com/turkish-citizenship-law-in-english/",
        "https://cindemirlaw.com/enforcement-of-a-foreign-decision-in-turkey/",
        "https://cindemirlaw.com/airport-customs-seizures-and-smuggling-offences-under-turkish-law/",
        "https://cindemirlaw.com/how-to-lift-entry-ban-to-turkey/",
        "https://cindemirlaw.com/turkish-inheritance-law/",
        "https://cindemirlaw.com/humanitarian-residence-permit-in-turkey-legal-insights-by-cindemir-law-office/",
        "https://cindemirlaw.com/can-russian-establish-a-company-in-turkey/",
        "https://cindemirlaw.com/criminal-record-deletion-in-turkey-for-foreign-nationals/",
    ]
    ordered = []
    seen = set()
    for u in priority + cleaned:
        if u in seen or u in PUBLISHED:
            continue
        if TR_SLUGS.search(u):
            continue
        seen.add(u)
        ordered.append(u)

    # Day 1 already published — keep in calendar for record
    day1_posts = [
        {
            "id": f"d1-{i}",
            "title": meta.get(u, {}).get("title") or title_from_url(u),
            "url": u,
            "summary": summary(
                u,
                meta.get(u, {}).get("title") or title_from_url(u),
                meta.get(u, {}).get("description", ""),
                meta.get(u, {}).get("excerpt", ""),
            ),
            "scheduled_at": None,
            "status": "published",
        }
        for i, u in enumerate(
            [
                "https://cindemirlaw.com/opening-a-company-in-turkey-for-foreigners/",
                "https://cindemirlaw.com/deportation-law-in-turkey/",
                "https://cindemirlaw.com/debt-recovery-in-turkey/",
                "https://cindemirlaw.com/consensual-divorce-in-turkey-uncontested-divorce/",
                "https://cindemirlaw.com/getting-criminal-record-in-turkey/",
            ],
            1,
        )
    ]

    days = [
        {
            "day": 1,
            "date": "2026-07-18",
            "status": "published",
            "posts": day1_posts,
        }
    ]

    day_num = 2
    i = 0
    while i < len(ordered):
        d = START + timedelta(days=day_num - 2)
        posts = []
        for slot, t in enumerate(TIMES):
            if i >= len(ordered):
                break
            u = ordered[i]
            i += 1
            title = meta.get(u, {}).get("title") or title_from_url(u)
            hh, mm = map(int, t.split(":"))
            when = datetime(d.year, d.month, d.day, hh, mm, tzinfo=TR)
            posts.append(
                {
                    "id": f"d{day_num}-{slot+1}",
                    "title": title,
                    "url": u,
                    "summary": summary(
                        u,
                        title,
                        meta.get(u, {}).get("description", ""),
                        meta.get(u, {}).get("excerpt", ""),
                    ),
                    "scheduled_at": when.isoformat(),
                    "status": "queued",
                }
            )
        days.append({"day": day_num, "date": d.isoformat(), "status": "queued", "posts": posts})
        day_num += 1

    cal = {
        "page": "Cindemir Hukuk Bürosu / Cindemir Law Office",
        "page_id": "100066585793269",
        "source": "https://cindemirlaw.com",
        "cadence": "5 posts per day",
        "timezone": "Europe/Istanbul",
        "times_local": TIMES,
        "language": "en",
        "brand": {
            "primary": "#336666",
            "secondary": "#286060",
            "accent": "#8bba34",
            "cover": "fixes/assets/fb-cover-cindemir.png",
            "logo": "https://cindemirlaw.com/wp-content/uploads/2020/06/cropped-logoicon-1-1-300x300.jpg",
            "cover_subtitle": "Istanbul · Turkish & International Law",
            "bio": "Cindemir Law Office — Istanbul. Turkish & international counsel for foreign clients: company formation, deportation, divorce, debt recovery, criminal record. EN / RU.\nhttps://cindemirlaw.com",
        },
        "stats": {
            "published_day1": 5,
            "queued_posts": sum(len(d["posts"]) for d in days if d["day"] > 1),
            "queued_days": len(days) - 1,
        },
        "days": days,
    }
    OUT.write_text(json.dumps(cal, indent=2, ensure_ascii=False) + "\n")
    print(
        f"days={len(days)} queued_posts={cal['stats']['queued_posts']} -> {OUT}",
        flush=True,
    )


if __name__ == "__main__":
    main()
