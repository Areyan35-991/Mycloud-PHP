# MyCloud — Personal File Storage

Self-hosted, private, Dropbox-style file storage. Runs on your Windows 10 desktop.
Your files never leave your machine.

---

## Requirements

| Requirement | Notes |
|---|---|
| XAMPP 8.x | Apache + PHP 8.1+ |
| PHP extensions | `pdo_sqlite`, `fileinfo`, `gd` (all included in XAMPP) |
| Cloudflare account | Free tier — for the public tunnel |

---

## Setup (step-by-step)

### 1. Install XAMPP

Download from https://www.apachefriends.org and install.
Start Apache from the XAMPP Control Panel.

### 2. Copy the project

Place this entire folder at:
```
C:\xampp\htdocs\mycloud\
```

Your public entry point will be:
```
http://localhost/mycloud/public/
```

### 3. Set your password

Open a terminal in the project root and run:

```bash
php setup.php
```

Copy the output hash, then open `config/config.php` and replace the placeholder in `OWNER_PASSWORD_HASH`.

Also set your preferred username in `OWNER_USERNAME`.

### 4. Set your APP_URL

In `config/config.php`, set `BASE_URL` to match your Cloudflare tunnel URL once you have it (step 6).
For local testing, keep it as `http://localhost/mycloud/public`.

### 5. Verify permissions

Make sure the following directories are **writable by Apache**:
```
storage/uploads/
storage/logs/
db/
```

In XAMPP on Windows this is automatic. If you move the project elsewhere, `chmod 750` those directories.

### 6. Set up Cloudflare Tunnel (access from anywhere)

**a.** Create a free account at https://cloudflare.com

**b.** Download `cloudflared.exe` from:
https://github.com/cloudflare/cloudflared/releases/latest

**c.** Open a terminal and run:
```bash
cloudflared tunnel --url http://localhost:80
```

**d.** It prints a URL like:
```
https://your-name-here.trycloudflare.com
```

**e.** Update `BASE_URL` in `config/config.php` to that URL + `/mycloud/public`

**Optional — permanent URL with your own domain:**
Follow Cloudflare's Named Tunnel guide to bind it to your own domain (free).

### 7. First login

Open your browser and go to:
```
http://localhost/mycloud/public/login.php
```

Or from your phone via the Cloudflare tunnel URL.

---

## Security model

| Threat | Mitigation |
|---|---|
| Brute-force login | IP locked out after 5 failures for 15 min |
| CSRF attacks | Double-submit token on every POST |
| Session fixation | ID rotated on login |
| Direct file access | `uploads/` blocked by `.htaccess`; all served via `download.php` |
| MIME spoofing | `finfo` checks actual file bytes, not client-supplied type |
| File metadata leaks | EXIF stripped from JPEG/PNG via GD on upload |
| Session hijacking | HTTPOnly + SameSite=Strict + 1-hour idle timeout |
| Directory listing | `Options -Indexes` in `.htaccess` |
| Clickjacking | `X-Frame-Options: DENY` on every response |

---

## Project structure

```
mycloud/
├── config/
│   └── config.php          ← all settings live here
├── db/
│   ├── migrations/
│   │   └── 001_initial_schema.sql
│   ├── migrator.php
│   └── cloud.db            ← created automatically on first run
├── public/                 ← Apache document root
│   ├── .htaccess
│   ├── login.php
│   ├── dashboard.php
│   ├── upload.php
│   ├── download.php
│   ├── preview.php
│   ├── share_create.php
│   ├── share.php
│   ├── delete.php
│   └── logout.php
├── src/
│   ├── bootstrap.php       ← loaded by every page
│   ├── db.php
│   ├── security.php
│   ├── Auth/Auth.php
│   ├── Storage/Storage.php
│   └── Share/ShareLink.php
├── storage/
│   ├── uploads/            ← your files live here
│   │   └── .htaccess       ← blocks direct HTTP access
│   └── logs/
├── templates/
│   └── error.php
└── setup.php               ← run once to generate password hash
```

---

## Upgrading / adding features

- **Expiring share links** — add an `expires_at` column (already in the schema). Update `ShareLink::create()` to accept a TTL.
- **Multiple users** — replace the config constants with a `users` table. Hash passwords with `password_hash()`.
- **Folder support** — add a `parent_id` column to `files` and render a tree in the dashboard.
- **Thumbnails** — generate on upload using GD; store alongside the original in `uploads/`.
