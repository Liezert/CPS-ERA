# CPS ERA

**Error-to-Asset Framework** — aplikasi web internal PT Catur Pilar Sejahtera.
*"Turn Errors into Assets, Bridge Knowledge for Tomorrow."*

CPS ERA mengubah kesalahan operasional menjadi bahan perbaikan dan pembelajaran (filosofi Kaizen):
laporan ketidaksesuaian dicatat dan ditinjau secara terstruktur, pengetahuan perusahaan dikumpulkan
di satu tempat, dan kontribusi belajar karyawan tercatat sebagai metrik KPI.

## Fitur

- **Laporan CAPA / FTK** — form digital `FR/QC/22` (5 Why, tindakan koreksi & korektif), nomor
  otomatis `BA-YYYY-NNNN`, persetujuan dua tahap Supervisor → HR, catatan potensi kerugian dari
  Supervisor, riwayat aktivitas, dan ekspor Excel per rentang waktu.
- **Learning** — materi pelatihan (dokumen, video, presentasi, artikel, tutorial, tautan) dengan
  post-test wajib 100% benar dan editor soal bergaya formulir.
- **Video kontribusi** — karyawan berbagi video, disetujui HR, lalu terbit di Learning.
- **Knowledge Repository** — pustaka SOP, kebijakan, dan instruksi kerja per topik.
- **Mission & Game** — kuis dan studi kasus dengan XP.
- **Poin** — XP untuk Leaderboard, dan Poin CPS ERA (maks. 3 per tahun) sebagai metrik KPI.
- **Panel admin** — kelola karyawan, materi, kuis, topik, dokumen, periode KPI, dan koneksi Google Drive.

Peran: `employee`, `supervisor`, `quality` (HR/HRGA), `admin`.

## Teknologi

Laravel 13 · Livewire 4 · Filament 5 · MySQL · Tailwind CSS 3 · Vite ·
spatie/laravel-permission · Google Drive API (penyimpanan video dan dokumen)

## Menjalankan di Lokal

Butuh PHP 8.4+, Composer, Node.js, dan MySQL.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Atur koneksi database di `.env`, lalu:

```bash
php artisan migrate --seed
npm run dev
php artisan serve
```

`--seed` mengisi data demo dan akun contoh; jangan dipakai di produksi.

## Test

```bash
php artisan test
```

Test memakai SQLite in-memory (lihat `phpunit.xml`).

## Deploy

Produksi berjalan di shared hosting tanpa queue worker, jadi unggahan ke Google Drive diproses
langsung saat menyimpan. Setiap pembaruan:

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
```

Folder `public/build` tidak disimpan di Git: jalankan `npm run build` lalu unggah hasilnya ke server.
Di produksi, isi data awal hanya dengan `DivisionSeeder`, `RoleSeeder`, dan
`CustomProductionUserSeeder`.
