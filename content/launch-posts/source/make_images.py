"""Generate original illustrated cover images (1600x900 JPEG) for the content pack.
Pure procedural drawing with Pillow: no photos, no people, no text, no logos."""
import math
import random
import sys
from PIL import Image, ImageDraw, ImageFilter

W, H = 1600, 900


def lerp(a, b, t):
    return tuple(int(a[i] + (b[i] - a[i]) * t) for i in range(3))


def hexc(h):
    h = h.lstrip('#')
    return tuple(int(h[i:i + 2], 16) for i in (0, 2, 4))


def vgrad(img, top, bottom, y0=0, y1=H):
    d = ImageDraw.Draw(img)
    for y in range(y0, y1):
        t = (y - y0) / max(1, (y1 - y0 - 1))
        d.line([(0, y), (W, y)], fill=lerp(top, bottom, t))


def hills(img, base_y, amp, color, seed, freq=1.0, blur=0):
    rnd = random.Random(seed)
    layer = Image.new('RGBA', (W, H), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    p1, p2, p3 = rnd.random() * 6, rnd.random() * 6, rnd.random() * 6
    pts = [(0, H)]
    for x in range(0, W + 1, 8):
        y = base_y + amp * (0.55 * math.sin(x / 260 * freq + p1)
                            + 0.30 * math.sin(x / 110 * freq + p2)
                            + 0.15 * math.sin(x / 47 * freq + p3))
        pts.append((x, y))
    pts.append((W, H))
    d.polygon(pts, fill=color + (255,))
    if blur:
        layer = layer.filter(ImageFilter.GaussianBlur(blur))
    img.alpha_composite(layer)


def sun(img, x, y, r, color, glow=True):
    layer = Image.new('RGBA', (W, H), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    if glow:
        for i in range(6, 0, -1):
            rr = r + i * 22
            d.ellipse([x - rr, y - rr, x + rr, y + rr], fill=color + (14,))
    d.ellipse([x - r, y - r, x + r, y + r], fill=color + (235,))
    img.alpha_composite(layer.filter(ImageFilter.GaussianBlur(3)))


def water(img, y0, top, bottom, seed, sheen=(255, 255, 255)):
    vgrad(img, top, bottom, y0, H)
    rnd = random.Random(seed)
    layer = Image.new('RGBA', (W, H), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    for _ in range(140):
        y = rnd.randint(y0 + 6, H - 4)
        x = rnd.randint(-50, W)
        ln = rnd.randint(30, 160) * (0.4 + (y - y0) / (H - y0))
        d.line([(x, y), (x + ln, y)], fill=sheen + (rnd.randint(25, 70),), width=2)
    img.alpha_composite(layer.filter(ImageFilter.GaussianBlur(0.8)))


def reeds(img, y_base, color, seed, count=70, x_range=(0, W), height=(80, 220)):
    rnd = random.Random(seed)
    d = ImageDraw.Draw(img)
    for _ in range(count):
        x = rnd.randint(*x_range)
        h = rnd.randint(*height)
        lean = rnd.uniform(-40, 40)
        pts = [(x + lean * (t / 10) ** 2, y_base - h * t / 10) for t in range(11)]
        d.line(pts, fill=color, width=rnd.choice([2, 3, 3, 4]))
        if rnd.random() < 0.35:
            tx, ty = pts[-1]
            d.ellipse([tx - 5, ty - 4, tx + 5, ty + 26], fill=lerp(color, (90, 60, 40), 0.5))


def birds(img, n, seed, area=(200, 1400, 80, 330), color=(40, 45, 70)):
    rnd = random.Random(seed)
    d = ImageDraw.Draw(img)
    for _ in range(n):
        x = rnd.randint(area[0], area[1])
        y = rnd.randint(area[2], area[3])
        s = rnd.randint(10, 22)
        d.line([(x - s, y - s * 0.35), (x - s * 0.4, y - s * 0.5), (x, y)], fill=color, width=3)
        d.line([(x, y), (x + s * 0.4, y - s * 0.5), (x + s, y - s * 0.35)], fill=color, width=3)


def tree_canopy(d, x, y, r, color, rnd):
    for _ in range(7):
        ox, oy = rnd.uniform(-r * 0.6, r * 0.6), rnd.uniform(-r * 0.45, r * 0.3)
        rr = r * rnd.uniform(0.5, 0.85)
        d.ellipse([x + ox - rr, y + oy - rr, x + ox + rr, y + oy + rr], fill=color)


def mangroves(img, waterline, color, seed, count=7):
    rnd = random.Random(seed)
    d = ImageDraw.Draw(img)
    for i in range(count):
        x = int((i + 0.5) * W / count + rnd.uniform(-60, 60))
        trunk_top = waterline - rnd.randint(170, 260)
        for _ in range(9):  # prop roots
            fx = x + rnd.uniform(-130, 130)
            mid = ((x + fx) / 2, waterline - rnd.randint(60, 120))
            pts = []
            for t in range(11):
                tt = t / 10
                px = (1 - tt) ** 2 * x + 2 * (1 - tt) * tt * mid[0] + tt ** 2 * fx
                py = (1 - tt) ** 2 * (waterline - 90) + 2 * (1 - tt) * tt * mid[1] + tt ** 2 * (waterline + 18)
                pts.append((px, py))
            d.line(pts, fill=color, width=5)
        d.line([(x, waterline - 90), (x, trunk_top)], fill=color, width=12)
        tree_canopy(d, x, trunk_top - 20, rnd.randint(90, 130), lerp(color, (60, 120, 70), 0.55), rnd)


def skyline(img, base_y, color, seed):
    rnd = random.Random(seed)
    d = ImageDraw.Draw(img)
    x = 0
    while x < W:
        w = rnd.randint(50, 130)
        h = rnd.randint(60, 230)
        d.rectangle([x, base_y - h, x + w, base_y], fill=color)
        win = lerp(color, (255, 236, 180), 0.55)
        for wy in range(base_y - h + 14, base_y - 10, 26):
            for wx in range(x + 10, x + w - 12, 22):
                if rnd.random() < 0.3:
                    d.rectangle([wx, wy, wx + 8, wy + 12], fill=win)
        x += w + rnd.randint(4, 20)


def rain(img, seed, alpha=60):
    rnd = random.Random(seed)
    layer = Image.new('RGBA', (W, H), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    for _ in range(900):
        x, y = rnd.randint(-100, W), rnd.randint(-50, H)
        d.line([(x, y), (x + 14, y + 46)], fill=(225, 232, 245, alpha), width=2)
    img.alpha_composite(layer)


def clouds(img, seed, color, n=7, y_range=(60, 300), alpha=230):
    rnd = random.Random(seed)
    layer = Image.new('RGBA', (W, H), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    for _ in range(n):
        cx, cy = rnd.randint(0, W), rnd.randint(*y_range)
        for _ in range(9):
            r = rnd.randint(50, 120)
            ox, oy = rnd.randint(-160, 160), rnd.randint(-30, 30)
            d.ellipse([cx + ox - r, cy + oy - r * 0.7, cx + ox + r, cy + oy + r * 0.7], fill=color + (alpha,))
    img.alpha_composite(layer.filter(ImageFilter.GaussianBlur(6)))


def base(top, bottom):
    img = Image.new('RGBA', (W, H))
    vgrad(img, hexc(top), hexc(bottom))
    return img


# ---------- scenes ----------

def scene_dusk_lake(seed):
    img = base('#6E6AA8', '#E8A9A0')
    sun(img, 420, 470, 80, hexc('#FFC98A'))
    hills(img, 480, 35, hexc('#5B5884'), seed, blur=2)
    hills(img, 530, 22, hexc('#3E4E5E'), seed + 1)
    water(img, 560, hexc('#C99AA6'), hexc('#3A3F6A'), seed, sheen=(255, 220, 200))
    reeds(img, H + 10, (36, 44, 56), seed + 2, count=60, x_range=(900, W), height=(140, 340))
    birds(img, 6, seed, area=(800, 1400, 120, 300), color=(40, 36, 70))
    return img


def scene_wetland(seed, dawn=False):
    img = base('#F6C99A', '#FBE9D2') if dawn else base('#9CC6E4', '#E7F1F4')
    sun(img, 1150, 300, 70, hexc('#FFE3A3') if dawn else hexc('#FFF6D8'))
    hills(img, 470, 40, hexc('#8FA9B8') if not dawn else hexc('#C7988A'), seed, blur=2)
    hills(img, 520, 30, hexc('#5E8C6A') if not dawn else hexc('#7E7A64'), seed + 1)
    water(img, 560, hexc('#9FC3D6') if not dawn else hexc('#EFC6A4'), hexc('#3F6F8F') if not dawn else hexc('#6A6F86'), seed)
    reeds(img, H + 10, (46, 84, 52), seed + 2, count=90, height=(120, 300))
    reeds(img, H + 10, (34, 62, 40), seed + 3, count=40, x_range=(0, 380), height=(200, 380))
    birds(img, 9, seed)
    return img


def scene_mangrove(seed):
    img = base('#B9D8E8', '#F0EEDB')
    sun(img, 300, 230, 60, hexc('#FFF1C7'))
    water(img, 600, hexc('#7FB2B5'), hexc('#2E5E6B'), seed)
    mangroves(img, 610, (64, 52, 40), seed, count=6)
    birds(img, 5, seed + 9, area=(700, 1500, 90, 260))
    return img


def scene_city_lake(seed):
    img = base('#F2B880', '#F8E4C8')
    sun(img, 800, 420, 90, hexc('#FFD98C'))
    skyline(img, 560, (88, 82, 108), seed)
    hills(img, 575, 8, hexc('#4F6F5A'), seed + 1)
    water(img, 590, hexc('#E4B98E'), hexc('#43506E'), seed)
    reeds(img, H + 10, (40, 70, 48), seed + 4, count=50, x_range=(1100, W), height=(120, 260))
    return img


def scene_grove(seed):
    img = base('#CFE3C8', '#EEF3E2')
    rnd = random.Random(seed)
    d = ImageDraw.Draw(img)
    for row, (col, y, r) in enumerate([((104, 140, 96), 360, 140), ((70, 112, 70), 470, 150), ((45, 84, 52), 600, 170)]):
        for i in range(10):
            x = i * 175 + rnd.randint(-40, 40)
            d.line([(x, y), (x + rnd.randint(-10, 10), H)], fill=lerp(col, (60, 40, 30), 0.6), width=18)
            tree_canopy(d, x, y, r, col + (255,), rnd)
    # light shafts
    layer = Image.new('RGBA', (W, H), (0, 0, 0, 0))
    ld = ImageDraw.Draw(layer)
    for i in range(5):
        x = 300 + i * 260
        ld.polygon([(x, 0), (x + 80, 0), (x + 260, H), (x + 120, H)], fill=(255, 250, 210, 34))
    img.alpha_composite(layer.filter(ImageFilter.GaussianBlur(14)))
    return img


def scene_monsoon(seed):
    img = base('#5D6B80', '#A9B4C2')
    clouds(img, seed, (72, 80, 96), n=10, y_range=(40, 260))
    skyline(img, 600, (58, 64, 82), seed + 2)
    water(img, 600, hexc('#8A97A8'), hexc('#3B4658'), seed, sheen=(200, 210, 225))
    rain(img, seed, alpha=70)
    return img


def scene_world_day(seed):
    img = base('#86B9DA', '#DDEFF4')
    clouds(img, seed, (255, 255, 255), n=5, y_range=(80, 220), alpha=200)
    hills(img, 500, 50, hexc('#7FA38C'), seed, blur=1)
    water(img, 560, hexc('#A6CFE0'), hexc('#2F5F88'), seed)
    birds(img, 22, seed + 3, area=(150, 1450, 90, 380), color=(30, 38, 70))
    reeds(img, H + 10, (52, 90, 56), seed + 5, count=45, x_range=(1150, W))
    return img


def scene_cleanup(seed):
    img = base('#A8D2E6', '#EEF5F1')
    sun(img, 1300, 220, 55, hexc('#FFF4D0'))
    hills(img, 520, 25, hexc('#6E9879'), seed)
    water(img, 560, hexc('#9CCBD8'), hexc('#3A7090'), seed)
    d = ImageDraw.Draw(img)
    # shore
    d.polygon([(0, 760), (700, 700), (1600, 820), (1600, 900), (0, 900)], fill=(196, 172, 128))
    # wooden boat
    d.polygon([(860, 700), (1240, 700), (1180, 760), (920, 760)], fill=(122, 78, 50))
    d.line([(860, 700), (1240, 700)], fill=(90, 56, 36), width=6)
    # sacks of collected waste (simple rounded shapes) on the shore
    for i, x in enumerate([180, 260, 330]):
        d.rounded_rectangle([x, 690 - i * 4, x + 70, 790], radius=24, fill=(47, 58, 143))
        d.line([(x + 20, 690 - i * 4), (x + 35, 670 - i * 4), (x + 50, 690 - i * 4)], fill=(47, 58, 143), width=6)
    reeds(img, H + 10, (46, 84, 52), seed + 2, count=40, x_range=(1300, W))
    return img


def scene_compost(seed):
    img = base('#E9E2C9', '#F6F1E1')
    d = ImageDraw.Draw(img)
    rnd = random.Random(seed)
    layers = [(560, (126, 92, 60)), (650, (98, 70, 46)), (740, (74, 52, 36)), (830, (56, 40, 28))]
    for y, c in layers:
        hills(img, y, 12, c, seed + y)
    d = ImageDraw.Draw(img)
    for _ in range(70):  # leaves
        x, y = rnd.randint(0, W), rnd.randint(470, 640)
        L = rnd.randint(30, 60)
        ang = rnd.uniform(0, math.pi)
        dx, dy = math.cos(ang) * L / 2, math.sin(ang) * L / 2
        col = rnd.choice([(92, 140, 70), (150, 170, 80), (190, 140, 60), (120, 150, 60)])
        d.polygon([(x - dx, y - dy), (x - dy * 0.4, y + dx * 0.4), (x + dx, y + dy), (x + dy * 0.4, y - dx * 0.4)], fill=col)
    # seedling
    d.line([(800, 560), (800, 360)], fill=(70, 120, 60), width=10)
    d.ellipse([700, 330, 800, 390], fill=(92, 150, 72))
    d.ellipse([800, 300, 910, 365], fill=(80, 140, 66))
    return img


def scene_beach(seed):
    img = base('#9FD0E8', '#E9F4F6')
    sun(img, 1250, 200, 60, hexc('#FFF2C4'))
    water(img, 470, hexc('#7EC0D2'), hexc('#2F7894'), seed)
    d = ImageDraw.Draw(img)
    d.polygon([(0, 640), (500, 600), (1100, 640), (1600, 610), (1600, 900), (0, 900)], fill=(226, 205, 160))
    rnd = random.Random(seed)
    for _ in range(9):  # discarded bottles
        x, y = rnd.randint(80, 1500), rnd.randint(680, 860)
        ang = rnd.uniform(-1.2, 1.2)
        L, Wd = 80, 26
        c, s = math.cos(ang), math.sin(ang)
        pts = [(x + c * a - s * b, y + s * a + c * b) for a, b in [(-L / 2, -Wd / 2), (L / 2, -Wd / 2), (L / 2, Wd / 2), (-L / 2, Wd / 2)]]
        col = rnd.choice([(170, 210, 220), (120, 180, 140), (230, 230, 235)])
        d.polygon(pts, fill=col, outline=(110, 130, 140))
        nx, ny = x + c * (L / 2 + 12), y + s * (L / 2 + 12)
        d.ellipse([nx - 8, ny - 8, nx + 8, ny + 8], fill=(47, 58, 143))
    return img


def scene_saplings(seed):
    img = base('#BFE0EE', '#F3F6EA')
    sun(img, 1300, 200, 55, hexc('#FFF4CF'))
    hills(img, 520, 40, hexc('#9EBE94'), seed, blur=1)
    hills(img, 620, 25, hexc('#7FA36F'), seed + 1)
    d = ImageDraw.Draw(img)
    d.rectangle([0, 700, W, H], fill=(120, 90, 62))
    rnd = random.Random(seed)
    for i in range(7):
        x = 150 + i * 220
        h = rnd.randint(140, 260)
        d.line([(x, 705), (x, 705 - h)], fill=(92, 70, 48), width=10)
        tree_canopy(d, x, 705 - h - 30, rnd.randint(55, 85), (64, 128, 70), rnd)
        d.ellipse([x - 50, 690, x + 50, 720], fill=(96, 70, 48))
    return img


def scene_documents(seed, accent=(47, 58, 143), magnifier=False):
    img = base('#E7E2D6', '#D6CFBF')
    rnd = random.Random(seed)
    layer = Image.new('RGBA', (W, H), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    for i in range(4):  # stacked sheets
        ox, oy = 420 + i * 26 + rnd.randint(-10, 10), 140 + i * 18
        d.rounded_rectangle([ox + 12, oy + 14, ox + 712, oy + 652], radius=8, fill=(0, 0, 0, 40))
        d.rounded_rectangle([ox, oy, ox + 700, oy + 640], radius=8, fill=(252, 251, 246, 255), outline=(200, 195, 182, 255))
    top_x, top_y = 420 + 3 * 26, 140 + 3 * 18
    for j in range(13):
        y = top_y + 90 + j * 38
        ln = rnd.randint(380, 580) if j % 4 else 260
        d.line([(top_x + 60, y), (top_x + 60 + ln, y)], fill=(170, 172, 182, 255), width=8)
    d.rectangle([top_x + 60, top_y + 40, top_x + 330, top_y + 58], fill=accent + (255,))
    # round stamp
    cx, cy = top_x + 560, top_y + 520
    d.ellipse([cx - 80, cy - 80, cx + 80, cy + 80], outline=(46, 125, 50, 220), width=9)
    d.ellipse([cx - 56, cy - 56, cx + 56, cy + 56], outline=(46, 125, 50, 220), width=4)
    if magnifier:
        mx, my, r = 1260, 470, 150
        d.ellipse([mx - r, my - r, mx + r, my + r], fill=(220, 236, 245, 110), outline=(40, 42, 60, 255), width=18)
        d.line([(mx + r * 0.7, my + r * 0.7), (mx + r * 1.6, my + r * 1.6)], fill=(40, 42, 60, 255), width=30)
    else:
        d.line([(1130, 760), (1450, 470)], fill=(40, 42, 60, 255), width=26)
        d.line([(1130, 760), (1105, 783)], fill=(200, 170, 90, 255), width=12)
    img.alpha_composite(layer)
    return img


def scene_viewfinder(seed):
    img = scene_wetland(seed, dawn=False)
    d = ImageDraw.Draw(img)
    c, L, m = (255, 255, 255), 90, 110
    for (x, y, sx, sy) in [(m, m, 1, 1), (W - m, m, -1, 1), (m, H - m, 1, -1), (W - m, H - m, -1, -1)]:
        d.line([(x, y), (x + sx * L, y)], fill=c, width=8)
        d.line([(x, y), (x, y + sy * L)], fill=c, width=8)
    d.ellipse([W / 2 - 14, H / 2 - 14, W / 2 + 14, H / 2 + 14], outline=c, width=5)
    return img


SCENES = {
    'wetland': lambda s: scene_wetland(s),
    'wetland_dawn': lambda s: scene_wetland(s, dawn=True),
    'mangrove': scene_mangrove,
    'city_lake': scene_city_lake,
    'grove': scene_grove,
    'monsoon': scene_monsoon,
    'world_day': scene_world_day,
    'cleanup': scene_cleanup,
    'compost': scene_compost,
    'beach': scene_beach,
    'saplings': scene_saplings,
    'documents': lambda s: scene_documents(s),
    'documents_green': lambda s: scene_documents(s, accent=(46, 125, 50)),
    'viewfinder': scene_viewfinder,
    'dusk_lake': scene_dusk_lake,
    'documents_magnifier': lambda s: scene_documents(s, magnifier=True),
}


def render(scene, seed, out):
    img = SCENES[scene](seed).convert('RGB')
    img = img.filter(ImageFilter.SMOOTH)
    img.save(out, 'JPEG', quality=88, optimize=True)


if __name__ == '__main__':
    render(sys.argv[1], int(sys.argv[2]), sys.argv[3])
