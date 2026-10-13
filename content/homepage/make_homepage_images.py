"""Draw the homepage illustrations (Get involved + cause cards) in the same style as the post covers."""
import os
import random
import sys

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, os.path.join(HERE, '..', 'launch-posts', 'source'))
import make_images as mi  # noqa: E402
from PIL import ImageDraw, ImageFilter  # noqa: E402


def scene_get_involved(seed):
    """Lake shore at morning: new saplings planted along the bank, collected waste sacks, a boat."""
    img = mi.base('#A9D3E8', '#F1F6EC')
    mi.sun(img, 1180, 230, 62, mi.hexc('#FFF1C6'))
    mi.hills(img, 470, 38, mi.hexc('#8DB39A'), seed, blur=1)
    mi.water(img, 520, mi.hexc('#9CCBD8'), mi.hexc('#3A7090'), seed)
    d = ImageDraw.Draw(img)
    d.polygon([(0, 690), (600, 640), (1600, 700), (1600, 900), (0, 900)], fill=(176, 140, 98))
    d.polygon([(0, 760), (800, 720), (1600, 780), (1600, 900), (0, 900)], fill=(150, 112, 76))
    rnd = random.Random(seed)
    for i in range(5):
        x = 700 + i * 160
        h = rnd.randint(120, 190)
        base_y = 715 + i * 6
        d.line([(x, base_y), (x, base_y - h)], fill=(92, 70, 48), width=9)
        mi.tree_canopy(d, x, base_y - h - 24, rnd.randint(46, 66), (66, 132, 72), rnd)
        d.ellipse([x - 44, base_y - 10, x + 44, base_y + 16], fill=(110, 80, 54))
    for i, x in enumerate([330, 415, 492]):
        d.rounded_rectangle([x, 640 - i * 4, x + 72, 742], radius=24, fill=(47, 58, 143))
        d.line([(x + 20, 640 - i * 4), (x + 36, 618 - i * 4), (x + 52, 640 - i * 4)], fill=(47, 58, 143), width=6)
    d.polygon([(250, 585), (500, 585), (462, 625), (288, 625)], fill=(122, 78, 50))
    mi.birds(img, 6, seed + 1, area=(300, 1000, 90, 260))
    return img


mi.SCENES['get_involved'] = scene_get_involved

JOBS = [
    ('get_involved', 301, 'get-involved.jpg'),
    ('world_day', 311, 'cause-wetlands.jpg'),
    ('mangrove', 317, 'cause-mangroves.jpg'),
    ('city_lake', 331, 'cause-urban-lakes.jpg'),
    ('grove', 337, 'cause-sacred-groves.jpg'),
]

if __name__ == '__main__':
    out = os.path.join(HERE, 'images')
    os.makedirs(out, exist_ok=True)
    for scene, seed, name in JOBS:
        mi.render(scene, seed, os.path.join(out, name))
        print('drew', name)
