# 🎸 Songbook — Chords Above Lyrics (PHP, CBA-style)

Same setup as your **CBA system**: **PHP + MySQL locally**, **Neon Postgres on Render**, mobile-friendly. Data lives in the **database**, so redeploying files **never deletes songs**.

- **Clients (no login)**: open `index.php`, pick a song, chords show **above the lyrics per line**, **transpose − / +** works on any device.
- **Admin (password-only, default `123`)**: login → Dashboard → Manage Songs (**add / edit / delete**).

## Project location

`C:\Users\Admin\Documents\Projects\musician-website`

## Files (mirrors cba-system)

| File | Purpose |
|---|---|
| `index.php` | Public songbook (search + viewer + transpose) |
| `login.php` / `logout.php` / `dashboard.php` | Password-only admin auth |
| `admin_songs.php` | Admin CRUD: add / edit / delete songs |
| `install.php` | One-time setup: creates `songs` table + demo songs (delete after use) |
| `includes/` | config (Neon/MySQL auto-detect), auth, header/footer |
| `assets/` | responsive CSS + transpose JS |
| `database/schema.sql` + `schema_pgsql.sql` | MySQL / Neon schemas |
| `Dockerfile` / `render.yaml` | Render deploy (same as CBA, but Neon URL pasted manually) |

## Song content format

Chord line goes ABOVE its lyric line:
```
[Verse 1]
G              C
Amazing grace how sweet
G          D          G
the sound that saved a soul
```
Blank line = gap, `[Name]` = section header.

## Run locally (XAMPP, like CBA)

1. Start Apache + MySQL, create DB `songbook`, import `database/schema.sql` (phpMyAdmin).
2. Copy this folder to `htdocs/songbook` (or serve: `php -S localhost:8000` from this folder).
3. Open `http://localhost:8000/install.php` → Run Setup → **delete `install.php`** → open `index.php`.
4. Admin login: `login.php`, password `123`.

Or with Neon locally: set env `DATABASE_URL=<neon-string>` before serving — app auto-switches to Postgres.

## Deploy to Render + Neon (like CBA flow)

1. Neon.tech → New Project → copy the **connection string** (`postgresql://...?sslmode=require`).
2. GitHub → New repository → **Add file → Upload files** → drag in all files from `musician-website`.
3. Render.com → **New → Blueprint** → connect repo. It creates `musician-website` from `render.yaml`.
4. In Render Environment set:
   - `DATABASE_URL` = your Neon connection string
   - `ADMIN_PASSWORD` = your admin password (e.g. `123` to start — change it!)
5. Open `https://<your-app>.onrender.com/install.php` → Run Setup → **delete `install.php` from GitHub** (Render redeploys without it) → login.
6. Share the URL — works on phones and computers.

> ⏳ Free-plan note: service sleeps after inactivity — first load takes ~30–60s. Normal on Render free tier.
> Data safety: songs live in Neon, so rebuilds/redeploys never delete them.
