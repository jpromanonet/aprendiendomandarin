import math
from pathlib import Path

def star(cx, cy, r, rot):
    pts = []
    for i in range(5):
        a = math.radians(rot + i * 72)
        b = a + math.radians(36)
        pts.append((cx + r * math.cos(a), cy + r * math.sin(a)))
        pts.append((cx + r * 0.382 * math.cos(b), cy + r * 0.382 * math.sin(b)))
    return " ".join(f"{x:.2f},{y:.2f}" for x, y in pts)

def facing(cx, cy):
    return math.degrees(math.atan2(5 - cy, 5 - cx))

parts = [f'<polygon points="{star(5, 5, 3, -90)}"/>']
for cx, cy in [(10, 2), (12, 4), (12, 7), (10, 9)]:
    parts.append(f'<polygon points="{star(cx, cy, 1, facing(cx, cy))}"/>')
svg = (
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 20">'
    '<rect width="30" height="20" fill="#DE2910"/>'
    '<g fill="#FFDE00">' + "".join(parts) + "</g></svg>"
)
Path(__file__).resolve().parents[1].joinpath("assets", "flag.svg").write_text(svg, encoding="utf-8")
