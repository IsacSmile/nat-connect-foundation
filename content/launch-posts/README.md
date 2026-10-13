# Launch posts

16 published posts, 4 in each category (Causes, News, Campaigns, RTI Applications),
each with an original illustrated cover image.

## What these posts are

General explainers: why wetlands matter, how to file an RTI, how to run a clean-up,
what the 2017 Wetlands Rules and the 2022 single-use plastic ban say. They are written
in a neutral voice. None of them claims that NatConnect Foundation did, filed, won or
found anything, and none names real officials, cases or local incidents. Facts about
laws and dates (RTI Act 2005 timelines, Ramsar Convention, Wetlands Rules 2017, plastic
ban items) are general public information, and the legal posts say they are not
legal advice.

The client has not reviewed these posts. Send them `titles.txt` from the built pack.

Cover images are drawn with code (`source/make_images.py`). They are illustrations,
not photographs, and every alt text says so.

## Files

- `posts.json`: the posts (title, slug, category, tags, excerpt, HTML body, cover,
  alt text, caption, editor's pick, publish date).
- `images/`: source covers (1600x900 JPEG).
- `source/`: the Python used to write the posts and draw the covers. Run
  `python3 source/build.py` to regenerate `posts.json` and `images/` into `source/pack/`.

## Build the pack

```bash
php bin/build-content-pack.php
```

Writes `build/content-pack/` (gitignored):

| File | Use |
|---|---|
| `import.sql` | Import in phpMyAdmin. Adds posts, tags and tag links. Skips any slug that already exists, so it is safe to run twice. Views and likes start at 0. |
| `uploads.zip` | Upload to `public_html` in File Manager and extract. Creates `uploads/YYYY/MM/` WebP files. |
| `remove.sql` | Deletes exactly these 16 posts (slug and cover path must both match), plus pack tags left with no posts. |
| `titles.txt` | List of posts to send the client. |

Every cover goes through `ImageUploader::processFile` (re-encode, EXIF strip,
1600/800/400 WebP), and every body through `Sanitizer::clean`, the same as the
admin editor. The builder does not need a database and does not touch `public_html/uploads`.

## Load on Hostinger (staging or live)

1. hPanel → phpMyAdmin → select the database → **Export** → Quick → SQL. Keep the file.
2. **Import** → choose `import.sql` → Go.
3. hPanel → File Manager → open `public_html` → upload `uploads.zip` → right-click →
   **Extract** into `public_html` (not into a subfolder) → delete `uploads.zip`.
4. Open the home page and two or three posts and check the covers load.

Do it in that order: if the images arrive before the database rows, nothing breaks;
if the rows arrive first, covers are briefly missing.

## Removing them later

Best: Admin → Posts → select → Delete. That also deletes the image files.
`remove.sql` removes the database rows only; the image files stay on disk.
