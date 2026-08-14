# S.NET RADIUS MANAGER

**PT Network Inovation Solutions**  
RADIUS-based WiFi Hotspot Voucher Manager — PHP Native, Multi-Router

---

## Persyaratan

- PHP 8.0+ dengan ekstensi: `mysqli`, `json`, `session`, `mbstring`
- MySQL 5.7+ / MariaDB 10.3+
- FreeRADIUS 3.x dengan modul `rlm_sql`
- Web server: Apache/Nginx
- MikroTik RouterOS (sebagai NAS/RADIUS Client saja)

## Instalasi

1. Upload semua file ke web server (misal `/var/www/html/radius-hotspot/`)
2. Buat database MySQL kosong: `CREATE DATABASE radius CHARACTER SET utf8mb4`
3. Buat user DB: `GRANT ALL ON radius.* TO 'radius'@'localhost' IDENTIFIED BY 'password'`
4. Buka browser: `http://server-anda/radius-hotspot/install.php`
5. Ikuti wizard instalasi (test koneksi DB → buat tabel → buat admin pertama)
6. Login dan mulai tambah router!

## Konfigurasi FreeRADIUS

Pastikan FreeRADIUS menggunakan module SQL dan terhubung ke database yang sama:

```ini
# /etc/freeradius/3.0/mods-enabled/sql (symlink dari mods-available)
sql {
    driver = "rlm_sql_mysql"
    server = "localhost"
    login = "radius"
    password = "password"
    radius_db = "radius"
    read_clients = yes
    client_table = "nas"
}
```

## Crontab (Auto-Expire Voucher)

```bash
# Jalankan setiap 5 menit
*/5 * * * * php /var/www/html/radius-hotspot/cron/expire_vouchers.php >> /var/log/snet_cron.log 2>&1
```

## Struktur File Utama

```
index.php          ← Dispatcher utama
login.php          ← Halaman login
install.php        ← Wizard instalasi (hapus setelah selesai)
config/
  database.php     ← Koneksi DB
  config.php       ← Konstanta
  auth.php         ← Autentikasi & RBAC
  db_local.php     ← Konfigurasi DB lokal (dibuat oleh installer)
include/
  header.php       ← Topbar + navbar
  sidebar.php      ← Menu navigasi
  footer.php       ← Closing HTML
  functions.php    ← Helper functions
lib/
  routeros_api.class.php  ← RouterOS API
  radius_coa.php          ← CoA/Disconnect sender
pages/             ← Semua halaman aplikasi
process/           ← POST handlers
ajax/              ← AJAX endpoints (JSON)
cron/              ← CLI scripts
assets/            ← CSS, JS, gambar
```

## Keamanan

- Semua query menggunakan prepared statements (anti SQL injection)
- CSRF token pada setiap form POST
- Session menggunakan `httponly` cookie
- Direktori `config/`, `lib/`, `cron/`, `logs/` dilindungi `.htaccess`
- Password admin menggunakan `password_hash()` (bcrypt)
- Audit trail semua aktivitas admin

## Arsitektur

```
[User WiFi] → [MikroTik (NAS)] → [FreeRADIUS] ← [MySQL/S.NET App]
                                      ↑
                            Database: radcheck, radreply, radacct, nas
```

MikroTik **hanya sebagai NAS** — tidak menyimpan user sama sekali.  
Seluruh data voucher disimpan di database aplikasi.

---
*S.NET RADIUS Manager v1.0.0 — PT Network Inovation Solutions*
