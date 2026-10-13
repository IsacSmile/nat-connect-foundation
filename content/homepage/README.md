# Homepage content

Fills homepage sections that are already built but were empty:

- **Get involved:** intro line and illustration in the left column.
- **Causes we care about:** 4 cause cards (Wetlands, Mangroves, Lakes, Sacred groves)
  with image and description. The homepage shows 3 plus "View all causes"; `/causes` lists all 4.
- **Explore by topic:** 10 topic chips, in the order listed in `homepage.json`.
- **Post updates:** applies later edits to launch posts that were already imported. Currently
  the Kerala sacred groves post becomes "Devrais: The Sacred Groves of Maharashtra" (with a
  301 from the old URL), and World Wetlands Day now names Maharashtra's Ramsar Sites.

Everything here can be edited later in Admin: Settings (Get involved) and Tags (causes, topics).

## Files

- `homepage.json`: the copy, cause list, topic list and post updates.
- `images/`: illustrations, drawn by `make_homepage_images.py` in the same style as the post covers.

## Build and load

```bash
php bin/build-homepage-pack.php
```

Writes `build/homepage-pack/update.sql` and `uploads.zip`. On Hostinger, in this order:

1. phpMyAdmin → Export a backup.
2. phpMyAdmin → Import → `update.sql`.
3. Unzip `uploads.zip` on your computer; upload the files inside `uploads/2026/10/` into
   `public_html/uploads/2026/10/` with File Manager.

`update.sql` is safe to run twice, and never overwrites anything an admin already set:
the intro and photo are only filled when empty, and a cause is only set up when that tag
has no image yet.

On a fresh install, import the launch posts pack first, then this one.
