#!/usr/bin/env python3
"""
db/seed_spots.json（または引数で指定したJSON）の commons_query を使って
Wikimedia Commons から各スポットに対応する画像を1枚ずつ検索・ダウンロードし、
画像/ ディレクトリに保存する。

実行方法:
  python3 db/import/fetch_commons_images.py               # db/seed_spots.json を使う
  python3 db/import/fetch_commons_images.py seed_spots_new.json  # 別ファイルを指定
"""
import json
import sys
import time
import urllib.parse
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent.parent
SEED_FILE = sys.argv[1] if len(sys.argv) > 1 else "seed_spots.json"
SEED_PATH = ROOT / "db" / SEED_FILE
IMAGE_DIR = ROOT / "画像"

API_URL = "https://commons.wikimedia.org/w/api.php"
HEADERS = {"User-Agent": "web-taiken-training-app/1.0 (educational project)"}


def search_commons_image(query: str) -> str | None:
    """Commonsを検索し、最初に見つかった画像ファイルの直リンクURLを返す。"""
    params = {
        "action": "query",
        "format": "json",
        "generator": "search",
        "gsrnamespace": "6",  # File namespace
        "gsrsearch": f"{query} filetype:bitmap",
        "gsrlimit": "5",
        "prop": "imageinfo",
        "iiprop": "url|size|mime",
        "iiurlwidth": "1600",
    }
    url = API_URL + "?" + urllib.parse.urlencode(params)
    req = urllib.request.Request(url, headers=HEADERS)
    with urllib.request.urlopen(req, timeout=15) as res:
        data = json.load(res)

    pages = data.get("query", {}).get("pages", {})
    for page in pages.values():
        imageinfo = page.get("imageinfo")
        if not imageinfo:
            continue
        info = imageinfo[0]
        mime = info.get("mime", "")
        if not mime.startswith("image/"):
            continue
        # 幅指定サムネイルがあればそれを、なければオリジナルを使う
        return info.get("thumburl") or info.get("url")
    return None


def download(url: str, dest: Path) -> None:
    req = urllib.request.Request(url, headers=HEADERS)
    with urllib.request.urlopen(req, timeout=30) as res:
        dest.write_bytes(res.read())


def main() -> None:
    spots = json.loads(SEED_PATH.read_text(encoding="utf-8"))
    IMAGE_DIR.mkdir(exist_ok=True)

    ok, ng = 0, 0
    for spot in spots:
        query = spot["commons_query"]
        file_name = spot["file"]
        dest = IMAGE_DIR / file_name

        # 既に取得済み（前回実行分）ならスキップして再開できるようにする
        if dest.exists() and dest.stat().st_size > 0:
            print(f"[SKIP] {file_name}: 既に存在するためスキップ")
            ok += 1
            continue

        try:
            image_url = search_commons_image(query)
            if not image_url:
                print(f"[NG] {file_name}: 画像が見つかりませんでした（query={query}）")
                ng += 1
                continue
            download(image_url, dest)
            print(f"[OK] {file_name} <- {query}")
            ok += 1
        except Exception as e:
            print(f"[NG] {file_name}: {e}")
            ng += 1

        time.sleep(10)  # Commons APIへの配慮（レート制限対策）

    print(f"\n完了: 成功 {ok}件 / 失敗 {ng}件")


if __name__ == "__main__":
    main()
