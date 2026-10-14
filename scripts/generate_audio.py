"""Genera MP3 de las palabras y frases que el sitio deja escuchar."""

import asyncio
import hashlib
import json
import sys
from pathlib import Path

import edge_tts

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "assets" / "audio"
VOICE = "zh-CN-XiaoxiaoNeural"
RATE = "-8%"

# Lectura que la voz no elige si el carácter va solo.
SPEAK = {
    ("中", "zhòng"): "众",
    ("为", "wèi"): "位",
    ("传", "chuán"): "船",
    ("传", "zhuàn"): "赚",
    ("倒", "dǎo"): "岛",
    ("倒", "dào"): "到",
    ("冲", "chòng"): "铳",
    ("划", "huá"): "华",
    ("划", "huà"): "话",
    ("卷", "juàn"): "倦",
    ("只", "zhī"): "之",
    ("吐", "tù"): "兔",
    ("呛", "qiàng"): "炝",
    ("圈", "juān"): "捐",
    ("圈", "juàn"): "眷",
    ("地", "de"): "的",
    ("处", "chǔ"): "楚",
    ("头", "tou"): "头",
    ("好", "hào"): "号",
    ("尽", "jǐn"): "锦",
    ("干", "gān"): "甘",
    ("当", "dàng"): "荡",
    ("待", "dāi"): "呆",
    ("得", "de"): "的",
    ("得", "děi"): "得",
    ("扇", "shān"): "山",
    ("折", "shé"): "舌",
    ("担", "dàn"): "旦",
    ("拧", "nǐng"): "拧",
    ("挑", "tiǎo"): "挑",
    ("挨", "ái"): "癌",
    ("撒", "sǎ"): "洒",
    ("散", "sǎn"): "伞",
    ("数", "shǔ"): "属",
    ("晃", "huǎng"): "谎",
    ("涨", "zhàng"): "账",
    ("炸", "zhá"): "闸",
    ("率", "lǜ"): "绿",
    ("看", "kān"): "刊",
    ("着", "zháo"): "着",
    ("种", "zhòng"): "仲",
    ("空", "kòng"): "控",
    ("系", "jì"): "记",
    ("结", "jiē"): "接",
    ("缝", "féng"): "逢",
    ("背", "bēi"): "杯",
    ("落", "là"): "辣",
    ("蒙", "méng"): "盟",
    ("行", "háng"): "航",
    ("调", "diào"): "掉",
    ("转", "zhuàn"): "转",
    ("还", "huán"): "环",
    ("重", "chóng"): "虫",
    ("量", "liáng"): "梁",
    ("长", "zhǎng"): "涨",
    ("闷", "mèn"): "闷",
    ("露", "lòu"): "漏",
}


def audio_id(text: str, pinyin: str = "") -> str:
    key = text.strip()
    if pinyin.strip():
        key += "\n" + pinyin.strip()
    return hashlib.sha1(key.encode("utf-8")).hexdigest()[:16]


def speak_of(text: str, pinyin: str = "") -> str:
    plain = pinyin.replace(" ", "")
    return SPEAK.get((text.strip(), plain), text.strip())


def collect() -> list[tuple[str, str]]:
    items: list[tuple[str, str]] = []
    seen: set[str] = set()

    def add(text: str, pinyin: str = "") -> None:
        text = (text or "").strip()
        pinyin = (pinyin or "").strip()
        if not text:
            return
        ident = audio_id(text, pinyin)
        if ident in seen:
            return
        seen.add(ident)
        items.append((text, pinyin))

    lexicon = json.loads((ROOT / "data" / "lexicon.json").read_text(encoding="utf-8"))
    for row in lexicon:
        add(row["hanzi"], row["pinyin"])
        add(row.get("ex_zh") or "")
    lessons = json.loads((ROOT / "data" / "hsk1.json").read_text(encoding="utf-8"))
    for lesson in lessons:
        for extra in lesson.get("extras") or []:
            add(extra.get("hanzi") or "", extra.get("pinyin") or "")
        for line in lesson.get("dialogue") or []:
            add(line.get("zh") or "")
    return items


async def synth(text: str, dest: Path, sem: asyncio.Semaphore) -> str:
    if dest.exists() and dest.stat().st_size > 800:
        return "skip"
    last = "error"
    async with sem:
        for attempt in range(5):
            try:
                communicate = edge_tts.Communicate(text, VOICE, rate=RATE)
                tmp = dest.with_suffix(".part")
                await communicate.save(str(tmp))
                if tmp.stat().st_size > 800:
                    tmp.replace(dest)
                    return "ok"
                tmp.unlink(missing_ok=True)
                last = "corto"
            except Exception as exc:
                last = str(exc)
                await asyncio.sleep(1.2 * (attempt + 1))
    print("FALLO", text, last, file=sys.stderr)
    return "fail"


async def main() -> None:
    OUT.mkdir(parents=True, exist_ok=True)
    items = collect()
    print(f"piezas {len(items)}", flush=True)
    sem = asyncio.Semaphore(6)
    done = 0
    failed = 0

    async def one(text: str, pinyin: str) -> None:
        nonlocal done, failed
        dest = OUT / f"{audio_id(text, pinyin)}.mp3"
        status = await synth(speak_of(text, pinyin), dest, sem)
        done += 1
        if status == "fail":
            failed += 1
        if done % 100 == 0 or done == len(items):
            print(f"{done}/{len(items)} fallos {failed}", flush=True)

    await asyncio.gather(*(one(text, pinyin) for text, pinyin in items))
    print(f"listo {done} fallos {failed}", flush=True)


if __name__ == "__main__":
    asyncio.run(main())
