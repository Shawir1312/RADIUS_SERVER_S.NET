# Rencana Arsitektur: High Availability (HA) RADIUS Server

Dokumen ini adalah cetak biru (blueprint) untuk menerapkan sistem RADIUS yang tahan banting (High Availability) menggunakan 2 VPS yang berjalan secara bergantian dan tersinkronisasi secara real-time. Anda dapat menerapkan panduan ini kapan saja Anda siap.

## Ringkasan Solusi
Sistem ini memecahkan masalah sinkronisasi manual dan sisa waktu voucher yang tidak akurat saat salah satu VPS mati. Kita akan menggunakan **Replikasi Database Master-Master** dan fitur bawaan **Multi-RADIUS Mikrotik**.

> [!IMPORTANT]
> Sistem ini bekerja di level mesin (Database & Router), sehingga tidak perlu lagi menggunakan script PHP manual untuk melakukan *backup/restore* atau sinkronisasi data antar VPS.

---

## Tahap 1: Konfigurasi Replikasi Database (Master-Master)

Langkah pertama adalah membuat database di VPS 1 dan VPS 2 saling terhubung secara real-time. Jika ada perubahan voucher di VPS 1, akan otomatis tercatat di VPS 2 dalam hitungan milidetik, dan sebaliknya.

**Dokumen Panduan Detail:**
Silakan buka dokumen panduan teknis yang telah saya siapkan:
👉 `tutorial_replikasi_vps.md` (Berada di dalam folder yang sama dengan file ini)

---

## Tahap 2: Konfigurasi FreeRADIUS

Pastikan *service* FreeRADIUS di kedua VPS berjalan normal:
- FreeRADIUS di VPS 1 menunjuk ke localhost VPS 1.
- FreeRADIUS di VPS 2 menunjuk ke localhost VPS 2.
- Keduanya memiliki `secret` RADIUS yang sama persis di file `clients.conf`.

---

## Tahap 3: Konfigurasi Failover di Mikrotik (Winbox)

Ini adalah langkah agar Mikrotik otomatis memindahkan traffic ke VPS 2 saat VPS 1 mati.

1. Buka Winbox ➔ Menu **RADIUS**.
2. **Tambahkan Server 1 (Utama)**:
   - Klik `+`.
   - Centang *Hotspot* / *PPP*.
   - **Address**: `[IP_VPS_1]`
   - **Secret**: `[secret_radius_anda]`
   - **Timeout**: `1000` ms (Sangat penting diset kecil agar cepat pindah saat mati).
3. **Tambahkan Server 2 (Cadangan)**:
   - Klik `+` lagi.
   - Centang layanan yang sama.
   - **Address**: `[IP_VPS_2]`
   - **Secret**: `[secret_radius_anda]`
   - **Timeout**: `1000` ms.
4. **Pastikan Urutan Benar**:
   - Di daftar RADIUS, pastikan IP VPS 1 berada di baris pertama (paling atas) dan IP VPS 2 berada di bawahnya. Mikrotik mengeksekusi dari atas ke bawah.

---

## Tahap 4: (Opsional) Sinkronisasi File Web Panel

Karena Anda memiliki Web Panel PHP (Radius Server S.Net), jika Anda meng-upload logo atau mengubah desain template voucher di VPS 1, file tersebut tidak otomatis pindah ke VPS 2 (karena database hanya menyalin teks data, bukan file fisik gambar/PHP).

**Solusi:**
Anda bisa membuat *cronjob* (penjadwalan) di VPS 1 menggunakan perintah `rsync` untuk menyalin folder assets/template ke VPS 2 setiap beberapa menit secara otomatis.

```bash
# Contoh command rsync (nantinya bisa dimasukkan ke crontab)
rsync -avz -e ssh /path/ke/folder/web/vps1/ user@IP_VPS_2:/path/ke/folder/web/vps2/
```
