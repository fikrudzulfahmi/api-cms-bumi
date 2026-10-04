# Migrasi ke Hosting Lain (cPanel)

Panduan memindahkan CMS ini ke hosting cPanel lain — mis. ke **hosting milik klien**.

## Ringkas
**Bukan hanya SSH key yang berubah.** Ada 3 bagian:

1. **Setup sekali** di server baru
2. **Ubah secrets** di GitHub (2 repo)
3. **Jalankan workflow** (push / Re-run)

---

## A. Setup sekali di server baru (cPanel)

1. **Aktifkan SSH** — cPanel → Security → SSH Access → Manage → **Enable**.
   (Port 22 terbuka ≠ akun ini otomatis boleh SSH.)

2. **Siapkan deploy key.** Boleh memakai kunci yang **sama** seperti sekarang (lebih praktis):
   ```bash
   ssh-keygen -t ed25519 -f ~/.ssh/deploy -N ""
   cat ~/.ssh/deploy.pub >> ~/.ssh/authorized_keys
   chmod 700 ~/.ssh && chmod 600 ~/.ssh/authorized_keys && chmod g-w,o-w ~
   # ambil isi private key untuk secret SSH_KEY (satu baris, tahan copy-paste):
   cat ~/.ssh/deploy | base64 -w0 ; echo
   ```

3. **Clone repo backend** (repo frontend **tidak** perlu di-clone — GitHub Actions yang build & upload `dist/`):
   ```bash
   cd ~ && git clone https://github.com/fikrudzulfahmi/api-cms-bumi.git api-cms-bumi
   ```

4. **Domain + document root** (cPanel → Domains):
   - **API** : subdomain `api-<domain>` → Document Root = `api-cms-bumi/public`
   - **Frontend** : subdomain/domain → Document Root = folder tujuan (dibuat oleh deploy, mis. `bumi.<domain>`)

5. **Database** — cPanel → MySQL Databases → buat DB + user → grant **ALL PRIVILEGES**.

6. **Backend `.env`** di `~/api-cms-bumi/.env`:
   ```env
   APP_NAME="CMS Madrasah"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://api-<domain>

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=<db>
   DB_USERNAME=<user>
   DB_PASSWORD=<pass>

   SESSION_DRIVER=database
   QUEUE_CONNECTION=database
   CACHE_STORE=database

   ADMIN_EMAIL=admin@<domain>
   ADMIN_PASSWORD=<ganti!>
   ```

7. **Install & migrate** (via SSH):
   ```bash
   cd ~/api-cms-bumi
   composer install --no-dev --optimize-autoloader
   php artisan key:generate
   php artisan migrate --force --seed
   php artisan storage:link
   ```
   > Jika `php`/`composer` tidak ketemu di SSH non-interaktif, jangan pakai PATH default —
   > skrip `.github/scripts/backend_deploy.sh` sudah menangani ini (mencari composer di
   > `/opt/cpanel/composer/bin/composer`, dll).

8. **SSL** — cPanel → SSL/TLS Status → **Run AutoSSL** untuk kedua domain.
   > ⚠️ **Prasyarat penting:** domain harus sudah diarahkan ke **nameserver hosting**
   > (mis. `ns1/ns2/ns3.dewaweb.com`) di registrar. Kalau DNS domain induk belum didelegasikan,
   > AutoSSL gagal / cert terbit untuk nama "bayangan" `<nama>.<domain-lama>`.
   > Cek: `nslookup -type=NS <domain>` atau DoH `https://dns.google/resolve?name=<domain>&type=NS`.

---

## B. Ubah secrets di GitHub

Repo → Settings → Secrets and variables → Actions.

| Repo | Secret | Nilai |
|---|---|---|
| `api-cms-bumi` | `SSH_HOST` | IP/host server baru |
| | `SSH_USER` | user cPanel baru |
| | `SSH_KEY` | private key |
| | `SSH_PORT` | biasanya `22` |
| | `DEPLOY_PATH` | nama folder repo backend, mis. `api-cms-bumi` |
| `cms-bumi` | `SSH_HOST` | (sama) |
| | `SSH_USER` | (sama) |
| | `SSH_KEY` | (sama) |
| | `SSH_PORT` | (sama) |
| | `DEPLOY_PATH` | nama folder docroot frontend, mis. `bumi.<domain>` |
| | `VITE_API_BASE_URL` | `https://api-<domain>/api` |

> 💡 **Kalau memakai kunci yang sama** (public key-nya ditambahkan ke server baru), maka
> secret **`SSH_KEY` tidak perlu diubah** — cukup `SSH_HOST`, `SSH_USER`, `SSH_PORT`,
> `DEPLOY_PATH`, dan `VITE_API_BASE_URL`.

`VITE_API_BASE_URL` **wajib** diganti: nilai ini di-*bake* saat build. Cukup ganti secret +
re-run workflow, bundle akan dibangun ulang dengan URL baru.

---

## C. Jalankan

Push commit kecil **atau** repo → Actions → **Re-run all jobs**.

Verifikasi:
- `https://api-<domain>/api/settings` → JSON
- `https://<domain>/` → halaman tampil
- `https://<domain>/profil` → 200 (butuh `.htaccess` SPA, sudah ada di `public/.htaccess`)

---

## Checklist cepat

- [ ] SSH aktif + deploy key terpasang di server baru
- [ ] Backend ter-clone + `.env` terisi
- [ ] `composer install` + `migrate --force --seed` + `storage:link` sukses
- [ ] Docroot API → `api-cms-bumi/public`
- [ ] Secrets **kedua** repo diperbarui (host/user/port/path + `VITE_API_BASE_URL`)
- [ ] Workflow hijau
- [ ] AutoSSL terbit dengan **nama domain yang benar** (cek `openssl s_client`)
