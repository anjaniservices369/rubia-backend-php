# Media storage (images / documents) - operations guide

Replaces Firebase Storage and ImageKit. Code: `app/modules/filestorage/services/MediaStore.php`,
`app/modules/filestorage/controllers/MediaController.php`, `app/commands/MediaController.php`.

## How it is stored

| Driver | Where the bytes live | When to use |
|---|---|---|
| `disk` (default) | files under `MEDIA_STORAGE_PATH` (default `app/runtime/media`), metadata row in table `media` | normal choice; keeps the database small |
| `db` | `media.data` BLOB | only for a single small server; needs `max_allowed_packet` >= the largest file |

Choose with `MEDIA_DRIVER=disk|db` (environment) or `params['media']['driver']`.

## Why this does not blow up the database

- Default driver is `disk`: the database holds only a ~200 byte row per file.
- Images are re-encoded on upload: longest side capped at 1600 px, JPEG quality 82, metadata/GPS removed.
  A 4000x3000 test photo went from 3.8 MB to 0.86 MB (a noise image is the worst case; real photos shrink more).
- The same owner uploading the same file again reuses the stored copy.
- Upload limit 10 MB; types JPEG, PNG, WebP, GIF, PDF only (checked on content).

## Sizing

Roughly 14,000 documents/photos existed in Firebase. At about 300-500 KB each after optimisation, plan for
**4-7 GB** for the same volume, plus growth. Run `php yii media/stats` to see real numbers
(files, MB, average size, last 7 days, free disk space).

## Before going live (checklist)

1. Create the table: `php yii db/up --yes` (script `001_media_and_page_tables`).
2. Set a storage path on a disk with enough free space, **outside the web root**, writable by the web user:
   `MEDIA_STORAGE_PATH=/var/rubia/media`. (The default `runtime/media` also works; a `.htaccess` that denies
   direct access is written automatically, but nginx ignores it, so prefer a path outside the web root.)
3. If you ever use the `db` driver: `SHOW VARIABLES LIKE 'max_allowed_packet';` must be at least 8 MB
   (MySQL 8 default is 64 MB; MariaDB 10.x is 16 MB; the local XAMPP default is only 1 MB, which then lowers the
   upload limit automatically).
4. PHP: `upload_max_filesize` and `post_max_size` >= 12M; GD (with JPEG/PNG/WebP) and `exif` extensions enabled.
5. **Back up the storage directory together with the database** (for example `rsync` or a nightly snapshot).
   A database backup alone no longer contains the files.

## Maintenance commands

```
php yii media/stats                          usage, driver, limits, free space
php yii media/move-to-disk --limit=500       move any BLOB rows out of the database into files
php yii media/purge-orphans --days=30        dry run: files no record points to
php yii media/purge-orphans --days=30 --apply  delete them
```

Schedule `media/purge-orphans --apply` (for example weekly) so abandoned uploads (a user uploaded but never
saved the form) do not accumulate.

## When disk is no longer enough

The `media` row already records `storage` and `path`, so a third driver (S3-compatible object storage such as
AWS S3, Cloudflare R2 or MinIO) can be added in `MediaStore` without changing the apps or the stored URLs.
Move to it when a single server's disk or backup window becomes a problem or when you run more than one
web server (all servers must see the same files; until then use a shared volume).