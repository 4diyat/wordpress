# Andalan Mutu Energi WordPress Theme

Theme company profile responsif untuk PT Andalan Mutu Energi. Seluruh konten halaman dikelola melalui post, featured image, menu, Media Library, dan pengaturan WordPress—bukan array konten hardcode di template front-end.

## Kompatibilitas WordPress dan PHP

Theme mendeklarasikan minimum **WordPress 6.0** dan **PHP 7.4**, dengan `Tested up to: 7.1`. Pada 6 November 2026, stable terbaru yang diuji adalah **WordPress 7.1.4** dan **PHP 8.5.12**; PHP 8.6 masih berstatus beta.

Aktivasi dan runtime smoke test lulus pada WordPress 7.1.4 menggunakan PHP **7.4.34-dev**, **8.3.36**, dan **8.5.12**. Cakupan uji meliputi pembuatan tabel, konten 3/6/9, halaman Masuk, migrasi `theme.json` v2 ke v3, render homepage, penggantian login default, endpoint lupa password, CAPTCHA, 2FA kustom/native, cookie auth setelah kode, dashboard admin, dan kolom konten kustom. Audit source tidak menemukan fungsi/hook WordPress yang dihapus pada 7.1.4.

Registrasi hook kolom admin dijalankan pada `init` priority 20 agar textdomain tidak dimuat terlalu dini. Setelah perbaikan ini, pengujian PHP 8.5.12 dengan `WP_DEBUG` tidak menghasilkan notice tema.

Runtime menggunakan WordPress Playground dengan PHP-WASM/SQLite. Email 2FA ditangkap melalui `pre_wp_mail`, sehingga deliverability SMTP produksi, hosting/MySQL, dan plugin pihak ketiga tetap perlu diuji di staging.

## Konten awal

Installer satu kali menyediakan:

- 3 Hero Beranda
- 6 Kartu Layanan
- 9 Slide Informasi
- 15 menu untuk navigasi, kontak, footer, media sosial, dan legal
- Logo perusahaan sebagai Custom Logo dan favicon sebagai Site Icon
- Informasi kontak, catatan footer, copyright, dan pengaturan teks global

## Instalasi

1. Unggah dan aktifkan theme melalui **Appearance → Themes → Add New → Upload Theme**.
2. Konten awal dan halaman **Masuk** dipasang otomatis pada aktivasi pertama.
3. Jika theme sudah aktif sebelum konten tersedia, buka **Appearance → Setup Beranda AME** dan klik **Pasang Konten Demo**.
4. Untuk struktur beranda dan blog yang jelas, buat halaman **Beranda** dan **Berita**, lalu tetapkan keduanya melalui **Settings → Reading**.

Installer hanya berjalan sekali dan tidak mengganti menu yang sudah ditetapkan. Semua hasil installer menjadi record WordPress yang dapat diedit atau dihapus secara individual.

## Hero Beranda dan Kartu Layanan

Buka menu **Hero Beranda** atau **Kartu Layanan** di wp-admin. Untuk setiap item:

- **Judul:** judul utama bagian.
- **Kutipan:** subjudul atau ringkasan.
- **Featured Image:** ilustrasi bagian.
- **Page Attributes → Order:** urutan tampil.
- **Section Settings:** catatan tambahan, label/URL CTA, dan skema warna terang/gelap.

## Slide Informasi

Buka **Slide Informasi**. Isi judul, kutipan sebagai deskripsi, kategori/label, label dan URL aksi, featured image, serta urutan. Gunakan gambar lanskap beresolusi tinggi dengan area aman untuk judul di bagian kiri bawah.

## Menu

Buka **Appearance → Menus**. Navigasi utama, tautan cepat, tautan kontak, semua grup footer, media sosial, dan tautan legal memakai menu WordPress yang ditetapkan pada lokasi masing-masing. Judul yang tampil untuk grup footer berasal dari **nama menu**.

## Logo, favicon, dan teks global

- Buka **Appearance → Customize → Site Identity** untuk mengganti logo atau favicon.
- Buka **Appearance → Customize → Pengaturan Beranda AME** untuk mengedit judul galeri, placeholder pencarian, judul tautan cepat, teks panel kontak, catatan footer, informasi kontak, copyright, dan label wilayah.
- Gunakan `[year]` pada copyright untuk menampilkan tahun berjalan otomatis.

## Interaksi

- Menu mobile mengunci scroll dan menyediakan focus trap serta tombol Escape.
- Pencarian memfokuskan input saat panel dibuka.
- Panel kontak menyediakan tautan WhatsApp, telepon, dan email dari menu WordPress.
- Galeri mendukung tombol sebelumnya/berikutnya, pagination dot, swipe/scroll, serta tombol keyboard Arrow, Home, dan End.
- Grup footer menjadi accordion pada layar mobile.
- Breadcrumb membantu orientasi pada halaman dalam; tombol kembali ke atas muncul setelah halaman digulir.

## Dashboard Pengunjung

Buka **Pengunjung AME** di wp-admin untuk melihat tayangan, estimasi pengunjung unik harian, grafik 7/30/90 hari, rata-rata unik per hari, dan halaman terpopuler. Data tabel tersedia di balik opsi **Lihat data tabel**.

- Tabel database dibuat saat aktivasi atau ketika administrator membuka wp-admin setelah update.
- **Customize → Pengaturan Beranda AME → Pengunjung & Privasi** mengatur aktif/nonaktif dan retensi 7–365 hari.
- Tidak ada cookie atau request ke layanan analitik eksternal.
- Raw IP/user-agent tidak disimpan; hanya hash berotasi harian dan path tanpa query yang dicatat.
- Bot, pengguna login, non-GET request, DNT, dan Global Privacy Control dikecualikan.
- Tombol hapus data menggunakan capability dan nonce check.

Angka unik adalah estimasi, bukan identitas atau pengukuran resmi. Proxy dan perubahan perangkat/browser dapat memengaruhi hasil. Tinjau kebijakan privasi situs sebelum mengaktifkan pelacakan di produksi.

## Login front-end, CAPTCHA & 2FA email

Theme membuat atau menemukan halaman **Masuk** berisi `[ame_login_form]` saat aktivasi/update dan kunjungan administrator ke wp-admin. Shortcode dapat dipindahkan ke page lain atau digunakan dengan atribut:

```text
[ame_login_form title="Masuk ke Akun" redirect="/"]
```

Form menyediakan username/email, password, remember me, lupa password, status login, logout, dan tautan Dashboard sesuai capability. Error menggunakan kode publik dan tidak mengirim username/password melalui URL.

Pengaturan **Customize → Pengaturan Beranda AME → Login & CAPTCHA**:

- Form front-end aktif/nonaktif.
- **Jadikan halaman Masuk sebagai login default**, default aktif. Filter `login_url` dan redirect `login_init` memakai halaman kustom serta mempertahankan `redirect_to`.
- Ikon Masuk/Keluar opsional di header, default nonaktif.
- CAPTCHA aktif/nonaktif pada form front-end dan fallback `wp-login.php`, default aktif.
- **Verifikasi kode email (2FA)** aktif/nonaktif pada form front-end dan native fallback, default aktif.

Hanya layar login yang diganti. Logout, registrasi, lupa/reset password, post password, dan recovery tetap memakai endpoint WordPress. Credential POST langsung ke `wp-login.php` tetap kompatibel dan memerlukan CAPTCHA. Native login menjadi fallback bila halaman kustom tidak tersedia atau form front-end dimatikan.

CAPTCHA menggunakan penjumlahan/pengurangan dengan payload HMAC, kedaluwarsa dua jam, dan honeypot. Jawaban tidak disimpan; tidak ada request/cookie CAPTCHA pihak ketiga. Cookie autentikasi WordPress digunakan setelah seluruh tahapan login berhasil.

### Verifikasi kode email (2FA)

Setelah kredensial dan CAPTCHA benar, kode enam digit dikirim melalui `wp_mail()` sebelum cookie autentikasi dibuat. Challenge berlaku 10 menit dan menyediakan form verifikasi, kirim ulang, serta batal pada halaman Masuk dan native fallback. Maksimal 5 percobaan kode, 3 pengiriman per challenge termasuk kode awal, jeda kirim ulang 60 detik, dan 5 email per pengguna per jam.

Kode mentah tidak disimpan; transient hanya menyimpan hash HMAC yang terikat pada pengguna dan challenge. Permintaan verifikasi/kirim ulang/batal memakai nonce. Remember-me dan redirect lokal divalidasi, sedangkan cookie auth dan action `wp_login` baru dibuat setelah kode benar. Core `wp-login.php` tidak dimodifikasi.

Fitur memerlukan email akun yang valid serta `wp_mail()`/SMTP yang berfungsi. Kegagalan email tidak menyebabkan fallback ke password-only. 2FA hanya mencakup login web interaktif yang didukung, bukan XML-RPC, REST, atau application password.

Untuk pemulihan darurat administrator, tambahkan sementara `define( 'AME_DISABLE_2FA', true );` ke `wp-config.php`, perbaiki pengiriman email, lalu hapus kembali konstanta tersebut.

CAPTCHA lokal adalah penghalang bot ringan, bukan pengganti layanan bot-detection enterprise. CAPTCHA hanya untuk form web login; XML-RPC/application password tetap mengandalkan proteksi brute-force. Kecualikan halaman Masuk dan verifikasi dari full-page cache agar CAPTCHA, nonce, serta challenge 2FA selalu baru dan valid.

## Deteksi serangan & respons otomatis

Buka **Keamanan AME** di wp-admin untuk memantau aktivitas, prioritas tinggi, blokir sementara, dan ringkasan 7 hari.

- Request dengan pola path traversal, SQL injection, XSS, file inclusion, atau command injection ditolak 403 dan diblokir sementara bila IP tersedia.
- Brute-force login diblokir setelah 5 kegagalan dalam 15 menit secara default; counter direset setelah login berhasil.
- Pemindaian 404 diblokir setelah 20 404 dalam 10 menit secara default. Pengguna login dan non-GET request tidak dihitung.
- Blokir berakhir otomatis setelah 15 menit secara default dan dapat dibuka lebih awal oleh administrator.
- **Customize → Pengaturan Beranda AME → Deteksi Serangan** mengatur toggle, ambang 3–20 login, ambang 5–100 404, dan durasi 5–120 menit.
- Aksi hapus log/buka blokir menggunakan capability dan nonce check.

Log hanya menyimpan hash IP, kode aktivitas, path tanpa query, dan waktu. Raw IP, username, password, dan payload tidak disimpan. Log dipangkas setelah 30 hari dan dibatasi 1.000 baris.

Ini adalah kontrol application-layer ringan, bukan pengganti WAF, rate limit server, malware scanner, backup, atau update. Header forwarding tidak dipercaya otomatis; konfigurasikan client IP pada reverse proxy/server untuk menghindari false positive pada IP bersama.

## Customize warna

Buka **Appearance → Customize → Pengaturan Beranda AME → Warna & Identitas** untuk mengubah warna utama, aksen, aksen section gelap, teks utama, teks sekunder, dan background terang/footer. Perubahan tampil langsung di preview dan tersimpan sebagai Theme Mod.

Warna background section gelap diturunkan otomatis dari warna utama. Gunakan warna utama yang cukup gelap dan periksa kontras setelah perubahan. Logo dan ilustrasi bitmap tidak di-recolor oleh CSS; ganti melalui Media Library bila ingin memakai artwork dengan warna lain.

## SEO dan security

- **Appearance → Customize → Pengaturan Beranda AME → SEO & Data Perusahaan:** deskripsi homepage, telepon, email, dan alamat untuk Schema Organization.
- Meta box **SEO & Berbagi Sosial** pada post/page menyimpan deskripsi SEO individual.
- Theme menyediakan meta description, canonical untuk arsip/home, Open Graph, Twitter Card, serta Schema Organization, WebSite, WebPage, Article, dan BreadcrumbList.
- Hasil pencarian internal dan halaman 404 mendapatkan `noindex, follow` selama output SEO theme aktif.
- Jika Yoast, Rank Math, SEOPress, atau AIOSEO aktif, metadata/schema theme tidak ditampilkan agar tidak terjadi duplikasi.
- **Keamanan & Performa** menyediakan security headers dasar dan preload hero. **Deteksi Serangan** menyediakan firewall ringan; keduanya tidak menggantikan HTTPS, WAF, malware scanning, backup, atau hardening server.
- Header dapat disesuaikan dengan filter `ame_security_headers`; output SEO dapat dikendalikan dengan filter `ame_seo_output_enabled`.

Periksa kembali seluruh alamat, nomor telepon, email, URL layanan, dan pernyataan perusahaan setelah konten dipasang di lingkungan produksi.

Versi: **1.9.2**.
