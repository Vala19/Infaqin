# CLAUDE.md

Infaqin adalah platform donasi transparan: kampanye dari campaigner terverifikasi, donasi, doa (prayer wall) dengan "Aamiin", dan Open Fund Ledger yang memuat setiap penarikan dana kampanye.

## Stack

- **Backend:** PHP 8.2+, Laravel 12 (tanpa starter kit; auth ditulis sendiri di `app/Http/Controllers/Auth`).
- **Frontend:** Blade + Blade components, Tailwind CSS 4 (`@tailwindcss/vite`), Alpine.js 3 + `@alpinejs/collapse`, Vite 7 (`laravel-vite-plugin`).
- **Database:** SQLite (`database/database.sqlite`) untuk lokal; SQLite `:memory:` untuk test.
- **Test:** PHPUnit 11 (bukan Pest).
- **Lainnya:** Laravel Pint (format kode), Pail (log), mail lokal lewat SMTP Mailtrap (lihat bagian Environment).
- **i18n:** `en` (default) dan `id`. Teks UI memakai `__()` dengan kunci bahasa Inggris; terjemahan di `lang/id.json` dan `lang/id/*.php`. Locale dipilih via `/language/{locale}` dan middleware `SetLocale`.

## Struktur folder

```
app/
  Http/Controllers/          Controller publik & donor (Campaign, Donation, Comment, History, Profile, News, ...)
  Http/Controllers/Admin/    Dashboard, Campaign, News, Donation (daftar), Withdrawal
  Http/Controllers/Auth/     Login, Register, EmailVerification
  Http/Middleware/           EnsureUserIsAdmin (alias 'admin'), SetLocale, SecurityHeaders
  Models/                    User, Campaigner, Campaign, Donation, Comment, News, Withdrawal
  Notifications/             DonationReceipt (email kuitansi)
  Providers/AppServiceProvider.php   preventLazyLoading, aturan Password default, view paginasi
  Support/helpers.php        rupiah(), rupiah_short() (di-autoload via composer "files")
bootstrap/app.php            Registrasi middleware & alias 'admin'
config/infaqin.php           Konten situs: locales, info perusahaan, fitur, testimoni, preset nominal, FAQ
database/migrations/         Skema (lihat bagian Model)
database/seeders/            DatabaseSeeder: akun demo, campaigner, kampanye, donasi, doa, berita
database/factories/          Hanya UserFactory
lang/                        id.json, id/auth.php, id/validation.php
resources/views/             Halaman Blade; admin/, campaigns/, donations/, news/, auth/, errors/, components/, partials/
resources/js/app.js          Komponen Alpine (carousel, kalkulator zakat, format rupiah, dll.)
resources/css/app.css        Tailwind 4 + token brand
routes/web.php               Semua route web
routes/console.php           Command `donations:expire` (dijadwalkan per jam)
tests/Feature/, tests/Unit/  Test PHPUnit
```

## Model & relasi

| Model | Tabel | Relasi | Catatan |
|---|---|---|---|
| `User` | `users` | hasMany `Donation`, hasMany `Comment`, belongsToMany `Comment` via `comment_amens` (`amenedComments`) | Implement `MustVerifyEmail`. `is_admin` sengaja **tidak** mass-assignable. `kindnessStreak()` = minggu berturut-turut dengan donasi `paid`. |
| `Campaigner` | `campaigners` | hasMany `Campaign` | `is_verified` = tanda perisai hijau. |
| `Campaign` | `campaigns` | belongsTo `Campaigner`, hasMany `Donation`, hasMany `Withdrawal`, hasMany `Comment` | Route key `slug`. Status: `draft`/`active`/`closed`. `target_amount`, `collected_amount`, `donor_count` integer. `isOpen()` = aktif & belum lewat deadline. |
| `Donation` | `donations` | belongsTo `User` (nullable), belongsTo `Campaign`, hasOne `Comment` | Route key `reference` (`INF-XXXXXXXXXX`, dibuat otomatis). Status: `pending`/`paid`/`expired`/`failed` (konstanta `STATUS_*`, termasuk `STATUS_EXPIRED`). `MIN_AMOUNT` 10.000, `MAX_AMOUNT` 1.000.000.000, `PAYMENT_WINDOW_HOURS` 24. `status` dan `user_id` tidak mass-assignable. |
| `Comment` (doa) | `comments` | belongsTo `Campaign`, `User`, `Donation` (unik: satu doa per donasi), belongsToMany `User` via `comment_amens` (`amenUsers`) | Hanya bisa dibuat setelah punya donasi `paid` ke kampanye itu. `amens_count` counter. Donasi anonim → nama "Hamba Allah". |
| `Withdrawal` | `withdrawals` | belongsTo `Campaign` | Ledger publik. Jumlah tidak boleh melebihi `collected_amount` dikurangi penarikan sebelumnya. |
| `News` | `news` | belongsTo `User` sebagai `author` (`author_id`) | Route key `slug`. Scope `published()`. |

Pivot `comment_amens` (PK gabungan `comment_id` + `user_id`, hanya `created_at`). Tabel `withdrawals` ada di migration kampanye; `comments` dan `comment_amens` ada di migration donasi.

## Menjalankan aplikasi & test

```bash
composer install && npm install
php artisan migrate:fresh --seed     # akun demo: admin@infaqin.test / Admin#12345, user@infaqin.test / Donor#12345
php artisan storage:link
composer dev                         # serve + queue + pail + vite sekaligus
```

Test (memakai SQLite in-memory, mail `array`, lihat `phpunit.xml`):

```bash
php artisan test                          # semua test
composer test                             # config:clear lalu test
php artisan test --filter=DonationFlowTest
php artisan test tests/Feature/AdminTest.php
vendor/bin/pint                           # format kode sebelum selesai
```

Test ditulis sebagai class PHPUnit dengan method `test_*` dan trait `RefreshDatabase`. Hanya ada `UserFactory`; model lain dibuat langsung di test (lihat `setUp()` di `tests/Feature/DonationFlowTest.php`).

## Environment

- **Jangan pernah membaca atau menampilkan isi `.env`.** Jika butuh tahu variabel apa yang ada, lihat `.env.example` atau `config/*.php`.
- Midtrans: `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_IS_PRODUCTION`. Variabel ini dipetakan di `config/services.php` ke `services.midtrans.*`, dan kode aplikasi membacanya lewat `config('services.midtrans.*')`. Jangan memanggil `env()` di luar folder `config/`, karena nilainya `null` setelah `config:cache`.
- Mail lokal memakai SMTP Mailtrap (bukan driver `log`): email verifikasi dan kuitansi masuk ke inbox Mailtrap. Test tetap memakai mailer `array`.

## Aturan wajib

1. **Route admin wajib middleware `admin`.** Setiap route di bawah `/admin` harus berada di grup `Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')` di `routes/web.php`. Jangan membuat route admin di luar grup itu, dan jangan memeriksa `is_admin` secara manual sebagai pengganti middleware.
2. **Donasi hanya untuk akun dengan email terverifikasi.** Route membuat/membayar donasi (`donations.create`, `donations.store`, dan alur pembayaran) harus memakai middleware `verified` selain `auth`. Akun yang belum verifikasi diarahkan ke `verification.notice`.
3. **Status donasi hanya boleh diubah lewat tiga jalur: webhook Midtrans, command cadangan `donations:expire`, dan pembatalan oleh donor.** Donasi baru dibuat `pending`. Status final adalah `paid`, `expired`, dan `failed`. Selain tiga jalur ini, tidak ada kode yang boleh menulis kolom `status`, termasuk admin.
   - **Pemetaan status Midtrans** (`transaction_status`). Berlaku sama untuk webhook dan command cadangan, lewat satu kode bersama:

     | Midtrans | Donasi |
     |---|---|
     | `settlement` | `paid` |
     | `capture` dengan `fraud_status=accept` | `paid` |
     | `capture` dengan `fraud_status=challenge` | tetap `pending` sampai Midtrans mengirim `settlement`/`deny` |
     | `capture` dengan `fraud_status=deny` | `failed` |
     | `expire` | `expired` |
     | `deny`, `cancel`, `failure` | `failed` |
     | `pending` | tetap `pending` |

     Pemetaan hanya diterapkan pada donasi yang masih `pending`. Donasi yang sudah final tidak diubah lagi. Saat menjadi `paid`, `paid_at`, `collected_amount`/`donor_count`, dan kuitansi email diproses di kode bersama yang sama.
   - **Webhook (jalur utama)** di `POST /payments/midtrans/notification`. Handler ini memverifikasi `signature_key`, dikecualikan dari CSRF, dan menerapkan pemetaan di atas.
   - **Command `donations:expire` (cadangan, terjadwal).** Command ini ada karena donatur bisa menutup popup Snap tanpa memilih metode, sehingga Midtrans tidak pernah mengirim `expire`. Untuk setiap donasi `pending` yang melewati batas waktu, command **wajib** mengecek status transaksi ke API Midtrans dulu, lalu menerapkan pemetaan di atas. Jika transaksi tidak ditemukan (404), donasi ditandai `expired`. Jika status Midtrans masih `pending`, donasi dicek lagi di jadwal berikutnya.
   - **Hitung mundur di view hanya informasi.** Sisa waktu bayar boleh ditampilkan (mis. dari `Donation::expiresAt()`), tapi menampilkan halaman tidak boleh menulis status.
   - **Pembatalan donor.** Pemilik donasi boleh membatalkan donasi `pending` lewat `donations.cancel` (`pending` → `failed`). Pembatalan memanggil API cancel Midtrans supaya transaksi tidak bisa dibayar lagi. Jika transaksi belum ada di Midtrans (404, mis. Snap ditutup sebelum memilih metode), pembatalan tetap dilakukan. Donasi yang sudah final tidak bisa dibatalkan.
   - **Idempoten.** Ketiga jalur mengunci baris (`lockForUpdate`) dan hanya mengubah donasi yang masih `pending`. Notifikasi atau proses yang datang belakangan untuk donasi final diabaikan, jadi tidak boleh ada `failed`/`expired` → `paid` atau sebaliknya.
   - `reference` donasi dipakai sebagai `order_id` Midtrans.
4. **Nominal memakai integer rupiah.** Semua uang (`amount`, `target_amount`, `collected_amount`, penarikan) disimpan dan dihitung sebagai integer rupiah utuh: tanpa float, tanpa desimal, tanpa sen, tanpa kolom `decimal`. Kolom memakai `unsignedBigInteger`, cast model `'integer'`, validasi `'integer'`. Format tampilan hanya lewat `rupiah()` / `rupiah_short()` (PHP) atau `formatRupiah` (JS); `gross_amount` ke Midtrans dikirim sebagai integer.
5. **Setiap perubahan harus disertai test.** Setiap perubahan perilaku (fitur, perbaikan bug, route, validasi, migration) harus menambah atau memperbarui test di `tests/Feature` atau `tests/Unit`, dan `php artisan test` harus lulus sebelum pekerjaan dianggap selesai.

### Kode yang belum sesuai aturan (per 2026-10-20)

Jangan anggap ini contoh yang benar; sesuaikan bila mengerjakan area ini (dengan test):

- **Aturan 2:** route donasi di `routes/web.php` hanya memakai `auth`, belum `verified`.
- **Aturan 3:** belum ada integrasi Midtrans, route webhook, maupun `snap_token`.
  - `DonationController::confirm()` (simulasi pembayaran, menandai `paid`) harus diganti handler webhook. Logikanya (lock, hitung donor baru, increment `collected_amount`, kirim `DonationReceipt`) dipindah ke kode bersama yang dipakai webhook dan `donations:expire`.
  - `DonationController::cancel()` sudah benar sebagai jalur pembatalan donor (`pending` → `failed`). Yang kurang: belum memanggil API cancel Midtrans (dengan 404 ditoleransi) dan belum memakai `lockForUpdate`.
  - Command `donations:expire` di `routes/console.php` boleh tetap ada, tapi sekarang langsung menandai `failed` tanpa mengecek Midtrans. Seharusnya cek status transaksi dulu, lalu menandai `expired` atau memproses `paid`.
  - `DonationController::expireIfOverdue()` menandai `failed` saat halaman dibuka (`show()` dan `confirm()`). Ini jalur keempat yang tidak diizinkan: hapus. Hitung mundur sisa waktu di view boleh tetap ada sebagai informasi.
  - Belum ada pemetaan status Midtrans (`capture`/`fraud_status`, `deny`/`cancel`/`failure`).
- **Status:** `Donation` belum punya `STATUS_EXPIRED`. Kedaluwarsa masih disimpan sebagai `failed` (`routes/console.php`, `DonationController`, seeder, `DonationFlowTest`), dan label UI `failed` masih "Cancelled" (`history.blade.php`, `components/status-badge.blade.php`).
- **Status campaign:** `Campaign::STATUSES` masih `active`/`completed`/`closed`, belum ada `draft`. `completed` masih dipakai di filter `CampaignController::index`, `campaigns/index.blade.php`, dan `components/status-badge.blade.php`.
- **Mail:** `README.md` masih menyebut driver `log` untuk mail lokal.

## Rancangan target (belum diimplementasi)

Sesuai ERD v3 & BPMN v3. Saat mengerjakannya, pindahkan butir dari sini ke bagian Model/Aturan, dan sertakan test.

- **Kategori:** tabel `categories` (`name`, `slug`) menggantikan string `campaigns.category` dan konstanta `Campaign::CATEGORIES`. Relasinya lewat `campaigns.category_id` (Campaign belongsTo Category, Category hasMany Campaign).
- **`campaigners.is_internal`:** `true` = Syaamil, ditampilkan sebagai "Program". `false` = mitra, ditampilkan sebagai "Campaign".
- **`users.is_active`:** user nonaktif tidak bisa login.
- **`news.campaign_id`** (nullable): berita bisa terkait satu campaign (News belongsTo Campaign, Campaign hasMany News).
- **Pivot `wishlists`** (`user_id`, `campaign_id`, unique gabungan). Wajib login.
- **`donations.snap_token`:** token Snap Midtrans. `reference` dipakai sebagai `order_id`.
- **Visibilitas campaign berdasarkan status:**
  - `draft` → 404 di semua halaman publik: daftar, detail, donasi (`create`/`store`), carousel, dan campaign terkait (related). Admin tetap bisa melihat dan mengedit draft lewat `/admin`.
  - `closed` → tetap tampil di halaman publik, tanpa tombol donasi. Route donasi menolaknya, seperti `isOpen()` sekarang.
- **Command `campaigns:close`,** dijadwalkan harian pukul 00.00: campaign `active` yang lewat `deadline` → `closed`.
- **Admin:** CRUD kategori & campaigner, daftar donatur, menonaktifkan akun, filter & ekspor CSV donasi. Semua di grup middleware `admin` (aturan 1).
- **Route webhook:** `POST /payments/midtrans/notification` (lihat aturan 3).

## Konvensi lain

- Validasi di controller (`$request->validate(...)`), input teks dibersihkan dengan `strip_tags`. Pencarian `LIKE` di-escape dengan `addcslashes($term, '%_\\')`.
- Kepemilikan donasi dicek di controller dan mengembalikan 404 (bukan 403) agar tidak membocorkan keberadaan data.
- `Model::preventLazyLoading()` aktif di luar production: selalu eager load (`with`/`load`).
- Route publik yang menulis data diberi `throttle`.
- Teks UI baru harus lewat `__()` dan ditambahkan ke `lang/id.json`; `LocaleTest` merender setiap halaman dalam bahasa Indonesia.
