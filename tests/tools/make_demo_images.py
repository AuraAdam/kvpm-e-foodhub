"""
Development tool: renders illustrated placeholder images for the DEMO data.

  php database/seed.php --json > /tmp/demo.json
  python3 tests/tools/make_demo_images.py /tmp/demo.json public/uploads

Needs Playwright with Chromium and a colour emoji font. The images are
illustrations (emoji on a coloured background), clearly not product photos.
Sellers replace them by uploading real photos.
"""
import json
import sys
from pathlib import Path

from playwright.sync_api import sync_playwright

CATEGORY_BG = {
    "makanan": ("#F6E7CF", "#EBCB98"),
    "minuman": ("#E3EFE6", "#B9D9C3"),
    "snek": ("#F5E2D3", "#E6BC9C"),
    "alat-tulis": ("#E1EAF4", "#B8CBE4"),
    "aksesori": ("#ECE6F3", "#CEC1E2"),
    "cenderamata": ("#F4E3E8", "#E3B9C6"),
}

PRODUCT_HTML = """<!doctype html><html><head><style>
html,body{margin:0;width:800px;height:600px;overflow:hidden}
.c{width:800px;height:600px;display:grid;place-items:center;position:relative;
background:radial-gradient(circle at 50%% 46%%, %(light)s 0 38%%, %(dark)s 100%%)}
.plate{width:340px;height:340px;border-radius:50%%;background:rgba(255,255,255,.55);display:grid;place-items:center;
box-shadow:0 30px 60px -30px rgba(60,40,10,.35)}
.e{font-family:'Noto Color Emoji';font-size:200px;line-height:1}
.t{position:absolute;right:20px;bottom:16px;font:600 18px system-ui,sans-serif;color:rgba(40,30,20,.45)}
</style></head><body><div class="c"><div class="plate"><span class="e">%(emoji)s</span></div><span class="t">Gambar ilustrasi</span></div></body></html>"""

SQUARE_HTML = """<!doctype html><html><head><style>
html,body{margin:0;width:%(size)spx;height:%(size)spx;overflow:hidden;background:transparent}
.c{width:%(size)spx;height:%(size)spx;display:grid;place-items:center;background:%(bg)s}
.e{font-family:'Noto Color Emoji';font-size:%(font)spx;line-height:1}
</style></head><body><div class="c"><span class="e">%(emoji)s</span></div></body></html>"""


def main(data_file: str, out_dir: str) -> None:
    data = json.loads(Path(data_file).read_text(encoding="utf-8"))
    out = Path(out_dir)
    for sub in ("products", "shops", "categories"):
        (out / sub).mkdir(parents=True, exist_ok=True)

    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page(viewport={"width": 800, "height": 600})
        for item in data["products"]:
            light, dark = CATEGORY_BG.get(item["category"], ("#EEE", "#CCC"))
            page.set_viewport_size({"width": 800, "height": 600})
            page.set_content(PRODUCT_HTML % {"light": light, "dark": dark, "emoji": item["emoji"]})
            page.screenshot(path=str(out / "products" / item["file"]), type="jpeg", quality=82)
        for item in data["shops"]:
            page.set_viewport_size({"width": 256, "height": 256})
            page.set_content(SQUARE_HTML % {"size": 256, "bg": item["color"], "font": 150, "emoji": item["emoji"]})
            page.screenshot(path=str(out / "shops" / item["file"]), type="png")
        for item in data["categories"]:
            page.set_viewport_size({"width": 160, "height": 160})
            page.set_content(SQUARE_HTML % {"size": 160, "bg": "#E2F0E8", "font": 104, "emoji": item["emoji"]})
            page.screenshot(path=str(out / "categories" / item["file"]), type="png")
        browser.close()
    print("Rendered", len(data["products"]), "product,", len(data["shops"]), "shop and", len(data["categories"]), "category images")


if __name__ == "__main__":
    main(sys.argv[1], sys.argv[2])
