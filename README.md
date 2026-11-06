# Andalan Mutu Energi — WordPress Theme

Theme company profile responsif untuk **PT Andalan Mutu Energi**, dengan identitas hijau–merah perusahaan, konten berbahasa Indonesia, dan seluruh konten halaman dikelola melalui WordPress. Implementasi ini mempertahankan struktur hero, kartu layanan, galeri informasi, navigasi, dan interaksi responsif dari desain awal tanpa menyimpan konten situs secara hardcode di template runtime.

## Fitur utama

- **3 Hero Beranda**, **6 Kartu Layanan**, dan **9 Slide Informasi** sebagai post WordPress yang dapat diedit.
- Gambar hero, layanan, dan slide memakai **Featured Image / Media Library**.
- Logo perusahaan didaftarkan sebagai **Custom Logo** dan favicon sebagai **Site Icon** oleh installer.
- **15 lokasi menu** untuk navigasi utama, tautan cepat, kontak, grup footer, media sosial, dan tautan legal.
- Teks global, informasi kontak, catatan footer, copyright, dan label wilayah dapat diedit melalui Customizer.
- Navigasi mobile dengan focus trap, panel pencarian, panel kontak, carousel dengan keyboard/tombol/dot, dan accordion footer mobile.
- Template standar untuk blog, arsip, pencarian, halaman, single post, 404, komentar, dan search form.
- Dukungan Gutenberg melalui `theme.json`, editor style, wide alignment, responsive embed, dan featured image.
- **SEO ringan:** meta description, canonical untuk arsip/home, Open Graph, Twitter Card, Schema Organization/WebSite/WebPage/Article/BreadcrumbList, dan noindex untuk hasil pencarian internal.
- **Security headers dasar:** `nosniff`, `Referrer-Policy`, `X-Frame-Options: SAMEORIGIN`, dan `Permissions-Policy` yang dapat difilter.
- **Deteksi serangan & respons otomatis:** pola request berbahaya, brute-force login, dan pemindaian 404 dengan blokir sementara, log keamanan, serta pemulihan dari wp-admin.
- **Login front-end, CAPTCHA lokal & 2FA email:** halaman Masuk otomatis, penggantian login default `wp-login.php`, shortcode login, logout, remember me, lupa password, CAPTCHA aritmatika bertanda tangan, honeypot, dan verifikasi kode enam digit melalui email.
- **UX korporat:** breadcrumb pada halaman dalam, tombol kembali ke atas, dan preload hero utama.
- **Live color customization:** enam color picker untuk warna utama, aksen, aksen section gelap, teks utama, teks sekunder, dan background terang/footer.
- **Dashboard Pengunjung native:** tayangan, estimasi pengunjung unik harian, grafik 7/30/90 hari, halaman terpopuler, retensi otomatis, dan hapus data—tanpa layanan analitik eksternal.

## Instalasi di WordPress

1. Buka **Appearance → Themes → Add New → Upload Theme**.
2. Unggah `andalan-mutu-energi.zip`, pasang, lalu aktifkan.
3. Saat aktivasi pertama, installer membuat konten awal, halaman **Masuk**, mengimpor gambar ke Media Library, membuat menu, dan mengisi pengaturan global.
4. Disarankan membuat halaman **Beranda** dan **Berita**, lalu membuka **Settings → Reading** dan memilih:
   - **Homepage:** Beranda
   - **Posts page:** Berita
5. Periksa **Appearance → Menus**, **Site Identity**, dan seluruh tautan sebelum situs dipublikasikan.

Jika theme sudah aktif tetapi konten awal belum terpasang, buka **Appearance → Setup Beranda AME**, lalu klik **Pasang Konten Demo** satu kali.

> Installer tidak berjalan pada setiap request dan tidak menimpa menu yang sudah ditetapkan. Setelah instalasi, setiap post, gambar, menu, dan pengaturan dapat diubah atau dihapus secara terpisah. `demo-content.json` hanya menjadi sumber installer, bukan sumber render front-end.

## Mengelola konten

| Bagian situs | Lokasi pengelolaan |
| --- | --- |
| Hero dan CTA | **Hero Beranda** |
| Enam kartu layanan | **Kartu Layanan** |
| Slide informasi/berita | **Slide Informasi** |
| Gambar | **Featured Image** pada masing-masing post / **Media Library** |
| Urutan konten | **Page Attributes → Order** pada masing-masing post |
| Navigasi, kontak, footer, legal | **Appearance → Menus** |
| Judul grup footer | Nama menu yang ditetapkan pada lokasi footer |
| Logo dan favicon | **Appearance → Customize → Site Identity** |
| Teks global dan copyright | **Appearance → Customize → Pengaturan Beranda AME** |

Untuk Hero Beranda dan Kartu Layanan, isi judul, kutipan sebagai subjudul, catatan tambahan, label/URL CTA, urutan, dan skema warna. Untuk Slide Informasi, isi judul, kategori, kutipan sebagai deskripsi, label/URL aksi, dan featured image.

## Pratinjau statis

Pratinjau tidak membutuhkan PHP atau database dan membaca file konten yang sama dengan installer:

```bash
npm run dev
```

Buka `http://localhost:3000`. Pratinjau hanya alat bantu pengembangan; perilaku aktivasi/importer tetap membutuhkan lingkungan WordPress.

## Kompatibilitas WordPress dan PHP

- Minimum yang dideklarasikan: **WordPress 6.0** dan **PHP 7.4**; header `Tested up to` diperbarui ke **7.1**.
- Pada 6 November 2026, versi stable resmi terbaru yang diperiksa adalah **WordPress 7.1.4** dan **PHP 8.5.12**. PHP 8.6 masih beta dan tidak dijadikan baseline stable.
- Audit source mencakup fungsi, class, dan hook core; tidak ditemukan API WordPress yang dihapus.
- Runtime smoke test dengan WordPress Playground/SQLite lulus pada **PHP 7.4.34-dev**, **8.3.36**, dan **8.5.12**: aktivasi, pembuatan tabel, konten 3/6/9, halaman Masuk, dan migrasi `theme.json` v2 ke v3.
- Uji HTTP dan end-to-end mencakup homepage, penggantian login default, endpoint lupa password, CAPTCHA, 2FA kustom/native, cookie auth setelah kode, dashboard admin, dan kolom konten kustom.
- Pada PHP 8.5.12, registrasi hook kolom admin dipindahkan ke `init` priority 20 agar textdomain tidak dimuat sebelum waktunya. Setelah perbaikan, response dan debug log bersih dari notice tema.

Pengujian runtime menggunakan PHP-WASM/SQLite dan email ditangkap melalui `pre_wp_mail`, bukan SMTP produksi. Kompatibilitas hosting/MySQL, deliverability SMTP, serta interaksi dengan plugin pihak ketiga tetap perlu divalidasi di staging tujuan.

## Pemeriksaan dan build

```bash
npm run check
python3 scripts/build_theme.py
```

Hasil build: `andalan-mutu-energi.zip`, dengan folder root `andalan-mutu-energi/` yang siap diunggah ke WordPress.

## Struktur penting

- `wp-content/themes/andalan-mutu-energi/` — source theme.
- `inc/content-types.php` — custom post types dan lokasi menu.
- `inc/demo-content.php` — installer satu kali.
- `inc/login.php` — halaman/form login, CAPTCHA lokal, dan autentikasi front-end.
- `inc/two-factor.php` — challenge email 2FA, verifikasi, pengiriman ulang, dan penerbitan cookie setelah kode valid.
- `demo-content.json` — konten awal perusahaan.
- `front-page.php`, `header.php`, `footer.php` — render berbasis data WordPress.
- `preview/` — pratinjau statis dan renderer JSON.
- `scripts/build_theme.py` — pembuatan ZIP tanpa dependensi pihak ketiga.

## Dashboard Pengunjung

Buka **Pengunjung AME** di wp-admin setelah theme aktif. Dashboard menyediakan:

- Tayangan hari ini dan estimasi pengunjung unik hari ini.
- Ringkasan dan grafik 7, 30, atau 90 hari.
- Rata-rata pengunjung unik harian dan halaman terpopuler.
- Tabel data yang dapat dibuka untuk aksesibilitas dan pemeriksaan angka.
- Pengaturan pelacakan/retensi melalui **Customize → Pengaturan Beranda AME → Pengunjung & Privasi**.
- Hapus seluruh data melalui form dengan capability dan nonce check.

Modul membuat tabel per-site di database saat aktivasi atau saat administrator membuka wp-admin setelah update. Tidak ada cookie atau request ke layanan eksternal. IP dan user-agent hanya dipakai sesaat untuk membuat hash berotasi harian dan tidak disimpan mentah; query URL dibuang. Bot, pengguna login, non-GET request, DNT, dan Global Privacy Control tidak dicatat.

Angka unik adalah **estimasi**, bukan identitas atau pengukuran resmi seperti Google Analytics. Proxy, perubahan browser/perangkat, dan aturan privasi dapat memengaruhi hasil. Sesuaikan penggunaan fitur dengan kebijakan privasi situs.

## Login front-end, CAPTCHA & 2FA email

Setelah aktivasi/update dan satu kunjungan administrator ke wp-admin, theme membuat atau menemukan halaman **Masuk** berisi `[ame_login_form]`. Shortcode juga dapat dipasang manual pada page lain:

```text
[ame_login_form title="Masuk ke Akun" redirect="/"]
```

Form menyediakan username/email, password, remember me, lupa password, status pengguna yang sudah masuk, tautan Dashboard sesuai capability, dan logout. Error diarahkan kembali ke halaman form tanpa memasukkan username atau password ke URL.

**Customize → Pengaturan Beranda AME → Login & CAPTCHA** menyediakan:

- Aktif/nonaktifkan form front-end.
- **Jadikan halaman Masuk sebagai login default**; default aktif. Tautan `wp_login_url()` dan kunjungan langsung ke `wp-login.php` dialihkan dengan `redirect_to` tetap dipertahankan.
- Ikon Masuk/Keluar opsional di header; default nonaktif agar navigasi existing tidak berubah.
- CAPTCHA pada form front-end dan fallback `wp-login.php`; default aktif.
- **Verifikasi kode email (2FA)** pada form front-end dan native fallback; default aktif.

Penggantian hanya mencakup layar login. Endpoint logout, registrasi, lupa/reset password, post password, dan recovery tetap menggunakan WordPress agar kompatibel. Credential POST langsung ke `wp-login.php` juga tetap didukung untuk integrasi lama dan tetap memerlukan CAPTCHA. Jika halaman Masuk tidak tersedia atau form front-end dimatikan, native login otomatis menjadi fallback.

CAPTCHA berupa penjumlahan/pengurangan dengan payload HMAC, masa berlaku dua jam, serta honeypot. Jawaban tidak disimpan dan tidak ada request atau cookie CAPTCHA pihak ketiga. WordPress tetap memakai cookie autentikasi normal setelah seluruh tahapan login berhasil.

### Verifikasi kode email (2FA)

Setelah username/email, password, dan CAPTCHA benar, cookie autentikasi **belum** diterbitkan. Theme membuat challenge selama 10 menit dan mengirim kode enam digit melalui `wp_mail()`. Form verifikasi tersedia pada halaman Masuk dan native fallback, dengan aksi kirim ulang dan batalkan. Batasnya adalah 5 percobaan kode, 3 pengiriman per challenge (termasuk kode awal), jeda kirim ulang 60 detik, dan 5 email per pengguna per jam.

Challenge disimpan sebagai transient; kode mentah tidak disimpan, hanya hash HMAC yang terikat pada pengguna dan challenge. Permintaan verifikasi/kirim ulang/batal memakai nonce WordPress. Redirect dan remember-me dipertahankan, sedangkan cookie auth serta action `wp_login` baru dibuat setelah kode valid. File core `wp-login.php` tidak diubah.

Pengiriman bergantung pada konfigurasi email WordPress/SMTP. Jika email gagal dikirim, login tidak turun menjadi password-only. Pastikan alamat email setiap akun valid dan SMTP berfungsi. 2FA ini hanya untuk login web interaktif yang didukung; XML-RPC, REST, dan application password tidak menerima challenge email interaktif.

Jika administrator terkunci karena email tidak berfungsi, tambahkan sementara baris berikut ke `wp-config.php`, login untuk memperbaiki email, lalu hapus kembali baris tersebut:

```php
define( 'AME_DISABLE_2FA', true );
```

CAPTCHA lokal adalah penghalang bot ringan, bukan setara reCAPTCHA/Turnstile atau bot-detection enterprise. CAPTCHA mencakup form web login; XML-RPC/application password tetap dilindungi oleh firewall brute-force, bukan soal CAPTCHA. Jika memakai full-page cache, kecualikan halaman Masuk dan verifikasi agar CAPTCHA, nonce, serta challenge 2FA selalu baru dan valid.

## Deteksi serangan & respons otomatis

Buka **Keamanan AME** di wp-admin untuk melihat aktivitas terbaru, jumlah ancaman, blokir sementara aktif, ringkasan 7 hari, dan aksi pemulihan.

- **Pola request berbahaya:** request dengan indikasi path traversal, SQL injection, XSS, file inclusion, atau command injection ditolak 403. Sumber diblokir sementara bila IP tersedia.
- **Brute-force login:** default 5 kegagalan dalam 15 menit memicu blokir. Counter direset setelah login berhasil.
- **Pemindaian 404:** default 20 halaman tidak ditemukan dalam 10 menit memicu blokir. Pengguna login dan request non-GET dikecualikan.
- **Durasi blokir:** default 15 menit, dapat diatur 5–120 menit. Tidak ada blokir permanen otomatis.
- **Pemulihan:** administrator dapat membuka satu atau semua blokir. Form memakai capability dan nonce check.

Pengaturan tersedia di **Customize → Pengaturan Beranda AME → Deteksi Serangan**. Log menyimpan hash IP, kode aktivitas, path tanpa query, dan waktu—bukan raw IP, username, password, atau payload. Log dipangkas setelah 30 hari dan dibatasi 1.000 baris.

Perlindungan ini bekerja di lapisan WordPress/PHP dan bukan pengganti WAF, rate limit server, malware scanner, backup, atau update. Header forwarding tidak dipercaya otomatis; di belakang reverse proxy, konfigurasikan client IP dan rate limit pada server agar satu IP proxy tidak menyebabkan false positive.

## Customize warna

Buka **Appearance → Customize → Pengaturan Beranda AME → Warna & Identitas**. Enam color picker mengubah tampilan secara langsung di preview dan tersimpan sebagai Theme Mod:

- **Warna utama:** navigasi dan identitas brand; warna background section gelap diturunkan otomatis.
- **Warna aksen:** tombol dan tautan pada background terang.
- **Warna aksen pada section gelap:** tautan/aksen pada background gelap agar dapat dijaga tetap kontras.
- **Teks utama, teks sekunder, dan background terang/footer.**

Periksa kontras setelah memilih kombinasi baru. Logo dan ilustrasi bitmap tidak di-recolor oleh CSS; ganti file tersebut melalui Media Library bila diperlukan.

## SEO, security, dan batasan

- Edit deskripsi homepage dan data Organization melalui **Appearance → Customize → Pengaturan Beranda AME → SEO & Data Perusahaan**.
- Edit deskripsi SEO individual melalui meta box **SEO & Berbagi Sosial** pada post/page. Jika kosong, theme memakai kutipan atau isi post secara otomatis.
- Output metadata/schema theme otomatis berhenti bila Yoast, Rank Math, SEOPress, atau AIOSEO terdeteksi. Filter `ame_seo_output_enabled` tersedia untuk kebutuhan integrasi lain.
- Security headers dapat dimatikan melalui **Keamanan & Performa**. Nilai header dapat disesuaikan melalui filter `ame_security_headers`.
- Theme tidak memasang CSP agresif, malware scanner, atau sistem backup. Firewall ringan di **Keamanan AME** hanya menangani pola request tertentu dan tidak menggantikan WAF/server rate limit. Gunakan HTTPS, update WordPress, backup berkala, serta konfigurasi security hosting/plugin sesuai kebutuhan produksi.

Versi adaptasi perusahaan: **1.9.2**.
