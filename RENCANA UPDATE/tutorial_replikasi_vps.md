# Panduan Setup Replikasi Database Master-Master (Ubuntu 24.04)

Karena kedua VPS Anda menggunakan **Ubuntu 24.04**, panduan ini akan sangat relevan. Biasanya pada Ubuntu, database yang digunakan untuk RADIUS adalah **MariaDB** atau **MySQL**. Panduan ini kompatibel untuk keduanya.

Konsepnya: **VPS 1** akan membaca perubahan di **VPS 2**, dan **VPS 2** juga akan membaca perubahan di **VPS 1**.

> [!IMPORTANT]
> **Persiapan Sebelum Mulai:**
> 1. Pastikan Anda sudah membuat *backup* (dump) database RADIUS Anda yang sekarang, untuk berjaga-jaga.
> 2. Pastikan kedua database di VPS 1 dan VPS 2 memiliki data awal yang **SAMA PERSIS**. Anda bisa melakukan *dump* dari VPS 1 dan meng-importnya ke VPS 2 sebelum memulai langkah ini.
> 3. Pastikan port MySQL/MariaDB (`3306`) terbuka di Firewall (UFW) pada kedua VPS agar mereka bisa saling berkomunikasi.
>    *Perintah di VPS 1:* `sudo ufw allow from IP_VPS_2 to any port 3306`
>    *Perintah di VPS 2:* `sudo ufw allow from IP_VPS_1 to any port 3306`

---

## Langkah 1: Konfigurasi di VPS 1 (Master 1)

1. Buka terminal di **VPS 1** dan edit file konfigurasi database:
   ```bash
   sudo nano /etc/mysql/mariadb.conf.d/50-server.cnf
   # ATAU jika pakai MySQL: sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
   ```

2. Cari baris `bind-address`, dan ubah menjadi `0.0.0.0` agar bisa diakses dari luar:
   ```ini
   bind-address            = 0.0.0.0
   ```

3. Tambahkan konfigurasi replikasi berikut di bawah bagian `[mysqld]`:
   ```ini
   server-id               = 1
   log_bin                 = /var/log/mysql/mysql-bin.log
   binlog_do_db            = nama_database_radius_anda  # Ganti dengan nama DB radius Anda!
   
   # Pengaturan Anti-Bentrok Primary Key (Sangat Penting untuk Master-Master)
   auto_increment_increment = 2
   auto_increment_offset   = 1
   ```
   Simpan dan keluar dari editor (`Ctrl+X`, lalu `Y`, lalu `Enter`).

4. Restart service database:
   ```bash
   sudo systemctl restart mariadb
   # ATAU: sudo systemctl restart mysql
   ```

5. Masuk ke console MySQL:
   ```bash
   sudo mysql -u root -p
   ```

6. Buat user khusus replikasi untuk VPS 2:
   ```sql
   CREATE USER 'replikasi_user'@'%' IDENTIFIED BY 'password_yang_sangat_kuat';
   GRANT REPLICATION SLAVE ON *.* TO 'replikasi_user'@'%';
   FLUSH PRIVILEGES;
   ```

7. Cek status Master di VPS 1 (Jangan tutup window ini, kita butuh datanya nanti):
   ```sql
   SHOW MASTER STATUS\G;
   ```
   *Catat nilai pada **`File`** (misal: `mysql-bin.000001`) dan **`Position`** (misal: `1234`).*

---

## Langkah 2: Konfigurasi di VPS 2 (Master 2)

1. Buka terminal di **VPS 2** dan edit file konfigurasi database seperti di VPS 1:
   ```bash
   sudo nano /etc/mysql/mariadb.conf.d/50-server.cnf
   ```

2. Ubah `bind-address` menjadi `0.0.0.0`.

3. Tambahkan konfigurasi replikasi di bawah bagian `[mysqld]`. **Perhatikan `server-id` dan `auto_increment_offset` berbeda dengan VPS 1**:
   ```ini
   server-id               = 2
   log_bin                 = /var/log/mysql/mysql-bin.log
   binlog_do_db            = nama_database_radius_anda  # Ganti dengan nama DB radius!
   
   # Pengaturan Anti-Bentrok Primary Key
   auto_increment_increment = 2
   auto_increment_offset   = 2
   ```
   Simpan dan keluar.

4. Restart service database:
   ```bash
   sudo systemctl restart mariadb
   ```

5. Masuk ke console MySQL:
   ```bash
   sudo mysql -u root -p
   ```

6. Buat user khusus replikasi (agar VPS 1 bisa membaca VPS 2):
   ```sql
   CREATE USER 'replikasi_user'@'%' IDENTIFIED BY 'password_yang_sangat_kuat';
   GRANT REPLICATION SLAVE ON *.* TO 'replikasi_user'@'%';
   FLUSH PRIVILEGES;
   ```

7. Hubungkan **VPS 2** agar membaca data dari **VPS 1**. (Gunakan data `File` dan `Position` yang Anda catat di Langkah 1 tadi):
   ```sql
   CHANGE MASTER TO 
   MASTER_HOST='IP_VPS_1', 
   MASTER_USER='replikasi_user', 
   MASTER_PASSWORD='password_yang_sangat_kuat', 
   MASTER_LOG_FILE='mysql-bin.xxxxxx', 
   MASTER_LOG_POS=xxxx;
   
   START SLAVE;
   ```
   *(Catatan: Jika memakai MariaDB 10.5+, Anda bisa memakai perintah `CHANGE REPLICATION SOURCE TO ...` dan `START REPLICA;`, tapi perintah lama di atas masih jalan).*

8. Cek apakah replikasi berhasil jalan:
   ```sql
   SHOW SLAVE STATUS\G;
   ```
   *Pastikan `Slave_IO_Running` dan `Slave_SQL_Running` bernilai **Yes**.*

9. Sekarang, ambil status Master dari VPS 2 (Catat ini untuk dipakai di VPS 1):
   ```sql
   SHOW MASTER STATUS\G;
   ```
   *Catat nilai **`File`** dan **`Position`** dari VPS 2.*

---

## Langkah 3: Menghubungkan Kembali VPS 1 ke VPS 2

1. Kembali ke terminal MySQL di **VPS 1**.
2. Hubungkan VPS 1 agar membaca data dari VPS 2:
   ```sql
   CHANGE MASTER TO 
   MASTER_HOST='IP_VPS_2', 
   MASTER_USER='replikasi_user', 
   MASTER_PASSWORD='password_yang_sangat_kuat', 
   MASTER_LOG_FILE='mysql-bin.xxxxxx',  -- (Isi dengan File dari VPS 2)
   MASTER_LOG_POS=xxxx;                 -- (Isi dengan Position dari VPS 2)
   
   START SLAVE;
   ```

3. Cek apakah berhasil:
   ```sql
   SHOW SLAVE STATUS\G;
   ```
   *Pastikan `Slave_IO_Running` dan `Slave_SQL_Running` bernilai **Yes**.*

---

## Selesai!

Sekarang kedua database Anda sudah saling terhubung secara real-time. 

**Cara Mengetesnya:**
1. Masuk ke phpMyAdmin atau aplikasi panel Anda di VPS 1.
2. Buat satu *voucher/user* baru secara acak (misalnya nama `test_replikasi`).
3. Cek database di VPS 2. Data user `test_replikasi` tersebut pasti sudah otomatis muncul dalam hitungan kurang dari 1 detik.

Jika sudah berhasil, Anda tinggal mengatur **Mikrotik** agar mengarahkan server RADIUS-nya ke VPS 1 dan VPS 2 seperti yang dijelaskan sebelumnya.
