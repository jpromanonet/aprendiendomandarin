# -*- coding: utf-8 -*-
"""Arma data/lexicon.json con glosas y ejemplos en español."""
import json
import time
from pathlib import Path

import translators as ts

ROOT = Path(__file__).resolve().parents[1]
SRC = ROOT / "data" / "hsk-3.0-words-all.json"
CACHE = ROOT / "data" / "es-cache.json"
OUT = ROOT / "data" / "lexicon.json"


def norm(value: str) -> str:
    return " ".join((value or "").replace("\n", " ").split())


def load_cache() -> dict:
    if CACHE.exists():
        return json.loads(CACHE.read_text(encoding="utf-8"))
    return {}


def save_cache(cache: dict) -> None:
    CACHE.write_text(json.dumps(cache, ensure_ascii=False), encoding="utf-8")


def translate_one(text: str) -> str:
    last = None
    for attempt in range(5):
        try:
            out = ts.translate_text(text, translator="bing", from_language="en", to_language="es")
            out = norm(out)
            if out:
                return out
        except Exception as exc:  # noqa: BLE001
            last = exc
            time.sleep(1.2 * (attempt + 1))
    print("fallback", text, last, flush=True)
    return text


def translate_chunk(chunk: list[str], depth: int = 0) -> list[str]:
    if not chunk:
        return []
    if len(chunk) == 1 or depth > 8:
        return [translate_one(item) for item in chunk]
    try:
        joined = "\n".join(chunk)
        raw = ts.translate_text(joined, translator="bing", from_language="en", to_language="es")
        lines = raw.split("\n")
        if lines and lines[-1] == "":
            lines = lines[:-1]
        lines = [norm(line) for line in lines]
        if len(lines) == len(chunk) and all(lines):
            return lines
    except Exception as exc:  # noqa: BLE001
        print("chunk error", exc, flush=True)
        time.sleep(1.5)
    mid = max(1, len(chunk) // 2)
    return translate_chunk(chunk[:mid], depth + 1) + translate_chunk(chunk[mid:], depth + 1)


def main() -> None:
    payload = json.loads(SRC.read_text(encoding="utf-8"))
    rows = payload["data"]
    cache = load_cache()
    needed = []
    seen = set()
    for row in rows:
        for key in ("meaning", "exampleEnglish"):
            text = norm(row.get(key) or "")
            if text and text not in cache and text not in seen:
                seen.add(text)
                needed.append(text)
    print(f"pendientes {len(needed)} cache {len(cache)}", flush=True)

    batch = []
    chars = 0
    done = 0
    for text in needed:
        if batch and (len(batch) >= 12 or chars + len(text) > 700):
            translated = translate_chunk(batch)
            for src, dst in zip(batch, translated):
                cache[src] = dst
            save_cache(cache)
            done += len(batch)
            print(f"{done}/{len(needed)}", flush=True)
            batch = []
            chars = 0
            time.sleep(0.2)
        batch.append(text)
        chars += len(text) + 1
    if batch:
        translated = translate_chunk(batch)
        for src, dst in zip(batch, translated):
            cache[src] = dst
        save_cache(cache)
        print(f"{len(needed)}/{len(needed)}", flush=True)

    lexicon = []
    for row in rows:
        meaning = norm(row.get("meaning") or "")
        example_en = norm(row.get("exampleEnglish") or "")
        lexicon.append(
            {
                "level": int(row["level"]),
                "hanzi": row["hanzi"],
                "pinyin": row["pinyin"],
                "es": cache.get(meaning, meaning),
                "ex_zh": row.get("exampleChinese") or "",
                "ex_py": row.get("examplePinyin") or "",
                "ex_es": cache.get(example_en, "") if example_en else "",
            }
        )
    OUT.write_text(json.dumps(lexicon, ensure_ascii=False), encoding="utf-8")
    print("lexicon", len(lexicon), OUT, flush=True)


if __name__ == "__main__":
    main()
