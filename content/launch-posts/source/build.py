import json
import os
import re
import sys

sys.path.insert(0, os.path.dirname(__file__))
from posts_part1 import POSTS_1
from posts_part2 import POSTS_2
from posts_part3 import POSTS_3
from posts_part4 import POSTS_4
from make_images import render

OUT = os.path.join(os.path.dirname(__file__), 'pack')
IMG = os.path.join(OUT, 'images')
os.makedirs(IMG, exist_ok=True)

TAG_NAMES = {'rti': 'RTI', 'how-to': 'How-to'}


def slugify(s):
    s = s.replace("'", '').replace('\u2019', '')
    return re.sub(r'-+', '-', re.sub(r'[^a-z0-9]+', '-', s.lower())).strip('-')


def tag_name(slug):
    return TAG_NAMES.get(slug, slug.replace('-', ' ').capitalize())


posts = []
for p in POSTS_1 + POSTS_2 + POSTS_3 + POSTS_4:
    slug = slugify(p['title'])
    img = f'{slug}.jpg'
    render(p['scene'], p['seed'], os.path.join(IMG, img))
    words = len(re.sub(r'<[^>]+>', ' ', p['content']).split())
    posts.append({
        'title': p['title'],
        'slug': slug,
        'category': p['category'],
        'tags': [{'slug': t, 'name': tag_name(t)} for t in p['tags']],
        'excerpt': p['excerpt'],
        'content_html': p['content'].strip(),
        'cover_image': f'images/{img}',
        'cover_alt': p['cover_alt'],
        'cover_caption': p['cover_caption'],
        'is_pick': p['is_pick'],
        'status': 'published',
        'published_at': p['published_at'],
        'meta_title': p['title'][:60],
        'meta_description': p['excerpt'][:160],
        'word_count': words,
    })
    print(f"{p['category']:<17} {words:>4}w  {slug}")

posts.sort(key=lambda x: x['published_at'])
with open(os.path.join(OUT, 'posts.json'), 'w', encoding='utf-8') as f:
    json.dump(posts, f, ensure_ascii=False, indent=2)
print(len(posts), 'posts written')
