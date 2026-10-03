# API CMS Bumi — MA Bustanul Muta'allimin

Backend API (Laravel 12 + Sanctum) untuk CMS website madrasah.

## Stack
- Laravel 12 (PHP 8.2+)
- Sanctum (token auth)
- Database: SQLite (lokal) / MariaDB (produksi)

## Data yang dikelola
| Endpoint publik | Keterangan |
| --- | --- |
| `/api/settings` | Pengaturan sekolah (nama, motto, kontak, PPDB, dll.) |
| `/api/profil` | Sejarah, visi & misi |
| `/api/guru-karyawan` | Guru & karyawan |
| `/api/berita?kategori=berita\|pengumuman\|prestasi` | Berita / pengumuman / prestasi |
| `/api/berita/{slug}` | Detail berita |
| `/api/umpan-balik` | Umpan balik lulusan |
| `/api/jurusan` & `/api/jurusan/{slug}` | Jurusan |
| `/api/fasilitas` | Fasilitas |
| `/api/ekstrakurikuler` | Ekstrakurikuler |
| `/api/galeri?kategori=kegiatan\|murid\|fasilitas` | Galeri / foto |

Endpoint admin (butuh token `Bearer`): prefix `/api/admin/...`
`login`, `logout`, `me`, `upload`, dan CRUD penuh semua resource di atas.

## Setup lokal
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve --port=8011
```

## Akun admin default
- Email: `admin@bustanulmutaallimin.sch.id`
- Password: `admin123`

> ⚠️ **Wajib diganti di produksi.** Set env `ADMIN_EMAIL` dan `ADMIN_PASSWORD`
> pada file `.env` server, lalu jalankan ulang `php artisan db:seed` (atau
> `migrate:fresh --seed`).

## Deploy otomatis (push → produksi)
Repo ini memakai GitHub Actions (`deploy.yml`) untuk auto-deploy ke cPanel
Dewaweb: SSH ke server → `git pull` → `composer install` → `migrate` → cache.

### Setup sekali (di cPanel)
1. **Clone repo pertama kali** di server, mis. ke `~/api-cms-bumi`:
   - cPanel → **Git Version Control** → Create → clone
     `git@github.com:fikrudzulfahmi/api-cms-bumi.git` (atau HTTPS + PAT).
2. **Buat subdomain** `api-cms-bumi.ingintau.my.id` → arahkan document root ke
   folder `public/` pada repo (`~/api-cms-bumi/public`).
3. **Siapkan `.env`** di server (salin `.env.example`):
   - `APP_URL=https://api-cms-bumi.ingintau.my.id`
   - `DB_CONNECTION=mysql` + kredensial database cPanel.
   - `ADMIN_EMAIL` & `ADMIN_PASSWORD` (wajib ganti dari default).
   - `APP_ENV=production`, `APP_DEBUG=false`.
4. **SSH key untuk GitHub Actions** (sekali):
   - Generate key tanpa passphrase: `ssh-keygen -t ed25519 -f ~/.ssh/deploy -N ""`
   - `cat ~/.ssh/deploy.pub >> ~/.ssh/authorized_keys`
   - `cat ~/.ssh/deploy` → salin **private key**.
   - Di GitHub → repo → **Settings → Secrets and variables → Actions**:
     - `SSH_HOST` = host cPanel
     - `SSH_USER` = username cPanel
     - `SSH_KEY` = private key (tempel)
     - `SSH_PORT` = `22` (opsional)
     - `DEPLOY_PATH` = path repo di server (mis. `~/api-cms-bumi`)

> Catatan: di Dewaweb, `php` default di SSH bisa versi lama. Jika `php`/`composer`
> tidak menemukan PHP 8.2, gunakan path eksplisit (mis. `ea-php82`) — sesuaikan
> perintah di `deploy.yml`.

Setelah itu, setiap `git push` ke `main` otomatis ter-deploy.
