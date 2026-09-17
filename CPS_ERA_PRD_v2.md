# Product Requirement Document (PRD)
# CPS ERA — Error-to-Asset Framework (Corporate Knowledge Hub)
**PT Catur Pilar Sejahtera (CPS)**

Versi dokumen: **2.0** — merevisi dan menggantikan v1.0.
Status: dokumen ini adalah **sumber kebenaran fungsional terbaru**. Kalau ada bagian PRD v1.0, `CPS-ERA-Data-Contract.md`, atau prompt lama yang bertentangan dengan dokumen ini, **dokumen ini yang menang** — kecuali untuk hal yang eksplisit ditandai "⚠️ BELUM FINAL" di bagian 6.

---

## 0. Riwayat Revisi

| Versi | Tanggal | Ringkasan Perubahan | Sumber Keputusan |
|---|---|---|---|
| 1.0 | Awal proyek | Draf awal — BA 2 file upload, 3 status, 12 divisi, MySQL, sistem poin single-ledger. | Sesi diskusi awal |
| 1.1 (tidak pernah dirilis sebagai dokumen terpisah) | 12 Sept 2026 | BA jadi form digital CAPA/FTK (`FR/QC/22`), video wajib multi-step, status jadi 4 tahap. | `CPS_ERA_BA_CAPA_Revision_Prompt.md` — sudah dieksekusi & terverifikasi di DB |
| **2.0** | **15 Sept 2026** | **(a)** Koreksi fakta stack & infrastruktur (13 divisi, PostgreSQL, versi paket aktual). **(b)** Perombakan total sistem poin jadi 2 ledger terpisah (XP vs Poin CPS ERA). **(c)** Aturan baru post-test Learning (unlimited attempt, wajib 100%, tanpa feedback benar/salah, kunci jawaban disembunyikan, dukungan soal multi-jawaban). **(d)** Koreksi definisi Knowledge Repository (bukan penyimpanan BA, tapi pustaka pengetahuan umum bertopik). **(e)** Redesain widget Dashboard. | Sesi klarifikasi client (13–15 Sept 2026) |

**Prinsip penyusunan dokumen ini:** setiap keputusan di bawah ditandai sumbernya — **[KONFIRMASI CLIENT]** kalau berasal langsung dari jawaban client, atau **[REKOMENDASI TEKNIS]** kalau itu proposal desain/skema dari sisi pengembangan yang masih perlu di-sign-off. Jangan perlakukan keduanya setara. Item yang masih terbuka dikumpulkan di §6, jangan diasumsikan sendiri saat coding.

---

## 1. Executive Summary & Context

### 1.1 Ringkasan
CPS ERA adalah sistem internal HR/GA yang menggabungkan tiga hal: **manajemen pengetahuan perusahaan**, **pencatatan insiden/kesalahan operasional (BA & Lesson Learned)**, dan **gamifikasi poin yang terhubung ke penilaian KPI karyawan**. Tagline: *"Turn Errors into Assets, Bridge Knowledge for Tomorrow"*. Filosofi dasar: prinsip **Kaizen** — kesalahan bukan aib yang disembunyikan, melainkan bahan baku perbaikan berkelanjutan.

### 1.2 Masalah yang Diselesaikan
| # | Masalah | Penjelasan |
|---|---|---|
| 1 | Repeated Errors | Masalah operasional yang pernah terjadi di satu divisi sering terulang karena tidak terdokumentasi terpusat. |
| 2 | Knowledge Silo | Solusi penanganan masalah hanya diketahui personel yang terlibat saat itu. |
| 3 | Tindakan Korektif Formalitas | Form tindakan perbaikan berakhir jadi dokumen fisik, tidak pernah dipelajari ulang. |
| 4 *(baru di v2.0)* | Pengetahuan Formal Tidak Terpusat | SOP, instruksi kerja, dan kebijakan perusahaan tersebar tanpa satu pustaka rujukan yang mudah dicari karyawan. |

### 1.3 Solusi (Inti Produk) — diperbarui
1. **Pencatatan insiden terstruktur** lewat form digital CAPA/FTK (`FR/QC/22`) dengan alur status 4-tahap dan video penanganan wajib.
2. **Knowledge Vault terpusat** dengan dua jenis isi: (a) pengetahuan formal perusahaan (SOP/kebijakan/instruksi kerja) yang dikelola Admin/HRD berdasarkan **Topik**, dan (b) Lesson Learned otomatis dari BA yang disetujui.
3. **Learning Hub** dengan materi lintas-jenis dan post-test bersyarat 100% sebagai syarat kontribusi KPI.
4. **Sistem poin ganda (dual-ledger)**: XP (gamifikasi harian, Level & Leaderboard) terpisah total dari Poin CPS ERA (metrik formal, dipakai HRD saat evaluasi kinerja).

### 1.4 Batasan Inti & Stack Terverifikasi *(koreksi total dari v1.0)*

> v1.0 menyebut MySQL dan 12 divisi sebagai asumsi awal. Keduanya **sudah tidak berlaku** — berikut fakta yang terverifikasi langsung dari `composer.json`, `package.json`, dan query database aktual per 13–15 September 2026.

| Komponen | v1.0 (asumsi lama) | **v2.0 (fakta terverifikasi)** |
|---|---|---|
| Database | MySQL 8 | **PostgreSQL** (pgsql, port 5432) |
| PHP | Tidak disebut versi pasti | **8.5.10** (runtime lokal) — `composer.json` mensyaratkan `^8.3`, target kompatibilitas produksi disarankan tetap ke gaya penulisan PHP 8.3–8.4 |
| Laravel Framework | Tidak disebut versi pasti | **13.30.1** |
| Filament | Tidak disebut | **5.7.8** |
| Livewire | Tidak disebut, asumsi v3 | **4.4.3** — Alpine.js sudah otomatis di-*inject* oleh Livewire, **jangan** diimpor/diinisialisasi manual di `app.js` |
| Alpine.js | — | 3.17.1 (bundled) |
| Jumlah Divisi | 12 | **13** (lihat §2.1 untuk daftar lengkap — tambahan: IT) |
| Node.js / Vite | — | Node 24.19.0 / Vite 8.2.2 |
| Tailwind CSS | — | 3.4.19 |
| Package pendukung | — | `spatie/laravel-permission` 8.3.0, `spatie/laravel-medialibrary` 11.23.7, `laravel/boost` 2.7.0 |

- Dikerjakan oleh **1 developer (solo)** dibantu AI tooling (Gemini agent mode untuk eksekusi, Claude untuk arsitektur/review).
- **Mixed primary key**: `users`/`divisions`/`learning_categories`/`achievements` pakai BIGINT; tabel domain/transaksi (`ba_incidents`, `knowledge_documents`, `quizzes`, `point_transactions`, dst.) pakai UUID. Ini keputusan final, jangan diseragamkan.
- Status kolom (`ba_incidents.status`, dsb.) disimpan sebagai **string biasa**, bukan ENUM native PostgreSQL — validasi nilai dijaga di level aplikasi/service.

---

## 2. User Roles & Access Matrix (RBAC)

### 2.1 Daftar Role (tidak berubah dari v1.0)
| Role | Deskripsi Singkat |
|---|---|
| **Employee** | Submit BA+video, ikuti materi Learning, ikut misi, lihat poin & leaderboard sendiri. |
| **Supervisor** | Semua hak Employee + review/approve BA & video dari divisinya. |
| **Quality/HRGA Team** | Semua hak Supervisor + kelola Learning, kelola Topik Knowledge Repository, susun post-test dari BA yang disetujui. |
| **Admin** | Akses penuh — kelola user, master data, override semua modul. |

> ⚠️ **Catatan penting soal reviewer BA**: instruksi revisi 12 September menyebut reviewer BA+video adalah **"Admin ATAU Supervisor"** (1 orang, bukan berlapis) — ini **berpotensi bentrok** dengan desain dual-approval (Supervisor→HR berurutan) yang sempat direncanakan untuk modul video secara umum. **Status: MASIH BELUM DIKONFIRMASI ULANG** ke client di sesi ini — lihat §6.1. Jangan asumsikan salah satu sebagai final sampai dikonfirmasi.

### 2.2 Access Matrix (diperbarui)

| Modul / Aksi | Employee | Supervisor | Quality/HRGA | Admin |
|---|:---:|:---:|:---:|:---:|
| Dashboard — lihat ringkasan pribadi (4 widget, lihat §3.6) | ✅ | ✅ | ✅ | ✅ |
| Knowledge Repository — lihat & cari konten | ✅ | ✅ | ✅ | ✅ |
| Knowledge Repository — bookmark konten | ✅ | ✅ | ✅ | ✅ |
| Knowledge Repository — kelola **Topik** (buat/edit/hapus) | ❌ | ❌ | ✅ | ✅ |
| Knowledge Repository — tambah konten (SOP/kebijakan/dll) | ❌ | ❌ | ✅ | ✅ |
| BA & Lesson Learned — buat BA + video (Step 1 & 2) | ✅ | ✅ | ✅ | ✅ |
| BA & Lesson Learned — lihat BA sendiri | ✅ | ✅ | ✅ | ✅ |
| BA & Lesson Learned — lihat semua BA | ❌ | Divisinya saja | ✅ semua divisi | ✅ |
| BA & Lesson Learned — approve/reject (isi Verifikasi) | ❌ | Divisinya saja ⚠️ | ⚠️ (lihat §2.1) | ✅ |
| Learning — ikuti materi & post-test | ✅ | ✅ | ✅ | ✅ |
| Learning — kelola kategori & materi | ❌ | ❌ | ✅ | ✅ |
| Learning — buat post-test dari BA yang disetujui | ❌ | ❌ | ✅ | ✅ |
| Learning — set XP opsional per materi | ❌ | ❌ | ✅ | ✅ |
| Mission & Game — ikut misi | ✅ | ✅ | ✅ | ✅ |
| Mission & Game — buat misi/quiz baru | ❌ | ❌ | ✅ | ✅ |
| Leaderboard — lihat (berbasis XP) | ✅ | ✅ | ✅ | ✅ |
| Poin CPS ERA — lihat riwayat sendiri | ✅ | ✅ | ✅ | ✅ |
| Poin CPS ERA — lihat rekap semua karyawan (untuk evaluasi kinerja) | ❌ | ❌ | ✅ | ✅ |
| User Management (CRUD user, assign role/divisi) | ❌ | ❌ | ❌ | ✅ |
| Master Data Divisi (CRUD 13 divisi) | ❌ | ❌ | ❌ | ✅ |
| Adjustment XP manual (koreksi ledger) | ❌ | ❌ | ❌ | ✅ |

> **Catatan eksplisit soal SP1**: CPS ERA **tidak punya modul/aksi apapun terkait SP (Surat Peringatan)**. Ini bukan fitur yang ditunda — ini **dihapus total dari scope**, dikonfirmasi final oleh client. HRD menangani SP sepenuhnya di luar sistem. Jangan buat tabel, kolom, atau UI apapun yang menyiratkan CPS ERA menyimpan/mengurangi data SP.

---

## 3. Spesifikasi Fitur MVP

### 3.1 BA & Lesson Learned *(Modul Inti — struktur aktual per migrasi 12 Sept 2026)*

**User Story**
> Sebagai Employee, saya ingin melaporkan insiden lewat form digital CAPA terstruktur dan video penanganan, agar insiden tercatat resmi, bisa ditinjau atasan, dan berpotensi jadi bahan ajar untuk karyawan lain.

**Acceptance Criteria (menggantikan seluruh AC BA di v1.0):**
- Nomor BA auto-generate `BA-YYYY-NNNN`, read-only.
- User wajib pilih 1 dari **13 divisi**.
- **Tidak ada** lagi upload 2 file (File BA + FTK) — diganti **form digital terstruktur** mengikuti template resmi `FR/QC/22`. Field lengkap: `tanggal_pengisian`, `sumber_ketidaksesuaian` (5 pilihan), `tanggal_masalah`, `lokasi`, `deskripsi_masalah`, `why_1`–`why_5`, `kesimpulan_akar_masalah`, `koreksi_deskripsi/pic/waktu` (tindakan sementara), `korektif_deskripsi/pic/waktu` (akar masalah), `is_potensi_risiko`, `is_potensi_peluang`.
- **Video wajib** sebagai **Step 2** dari alur submit multi-step (bukan lagi opsional). User pilih salah satu: upload file (`video_file_url`) atau link eksternal (`video_external_link`). Data Step 1 tersimpan sebagai `draft` dan tidak hilang saat user kembali dari Step 2.
- Status baru **4 tahap**: `draft → submitted → approved → rejected` (bukan 3 tahap `Created→Reviewed→Closed` seperti tertulis di v1.0/Design-System §8 — dokumen tersebut **outdated**, lihat §0).
- Approve wajib isi `status_verifikasi` (efektif/tidak_efektif) + `bukti_objektif` ATAU `alasan_tidak_efektif`.
- Efek approve: **(a)** insert `knowledge_documents` type=`lesson_learned` (mekanisme lama, dipertahankan), **DAN (b)** video kandidat otomatis masuk *pool* Learning (mekanisme baru, lihat §3.3), **DAN (c)** pembuat BA dapat **+1 Poin CPS ERA** (Jalur A, lihat §3.5) — tunduk pada batas 3 poin/tahun.
- Reject: reviewer wajib isi `catatan_penolakan`. Status jadi `rejected`.

**Logika Bisnis:**
- Transisi status dijaga di level service (`BaIncidentService`), bukan constraint database (kolom `status` string biasa).
- Setiap perubahan status tercatat di `ba_activity_logs`.

**Item yang masih terbuka untuk modul ini** → lihat §6.1 (jangan diasumsikan: siapa reviewer final, apakah status `Closed` masih perlu, boleh edit-ulang BA yang di-reject atau harus baru, tipe kolom `koreksi_waktu`/`korektif_waktu`, dan apakah jalur video sukarela ikut berubah formatnya).

---

### 3.2 Knowledge Repository *(Modul Inti — KOREKSI DEFINISI dari v1.0)*

> ⚠️ **Ini perbaikan dari kekeliruan yang sudah dibangun.** Implementasi sebelumnya membuat halaman ini seolah cuma berisi Lesson Learned dari BA. Client mengoreksi: fungsi sebenarnya adalah **pustaka pengetahuan formal perusahaan** — SOP, instruksi kerja, peraturan perusahaan, kebijakan manajemen — bukan tempat penyimpanan laporan insiden semata.

**User Story**
> Sebagai karyawan, saya ingin mencari dan membaca SOP/kebijakan/instruksi kerja resmi perusahaan berdasarkan topik, agar saya bisa merujuknya kapan saja tanpa harus bertanya ke HRD.

**Acceptance Criteria (revisi):**
- Konten dikelompokkan berdasarkan **Topik** — taksonomi baru yang dibuat & dikelola oleh **Admin/HRD** [KONFIRMASI CLIENT], bukan lagi berdasarkan 13 divisi (divisi tetap ada sebagai filter sekunder opsional, lihat catatan di bawah).
- Format konten umumnya **PDF / Microsoft Office** [KONFIRMASI CLIENT], selain jenis lain yang sudah ada (video/presentasi/link).
- **Knowledge Repository TIDAK memberi poin KPI apapun** — baik XP maupun Poin CPS ERA — murni referensi pasif [KONFIRMASI CLIENT, final, dikonfirmasi 2x].
- Dua sumber konten berjalan paralel:
  1. **Konten kurasi manual** (SOP/kebijakan/instruksi kerja) — diinput Admin/HRD, dikelompokkan per Topik. *(Ini bagian yang tadinya kosong/salah arah dan sekarang diperbaiki.)*
  2. **Lesson Learned otomatis dari BA yang disetujui** — mekanisme lama, tetap dipertahankan tanpa perubahan.
- Bookmark & search tetap seperti desain lama.

**⚠️ Belum final — perlu konfirmasi (lihat §6.2):** apakah **Topik** ini menggantikan total peran Divisi sebagai kategori (yang sebelumnya sempat dikunci "FINAL" di Data-Contract §2 item 7 — **keputusan itu sekarang perlu dibuka ulang**), atau Topik berjalan sebagai lapisan filter tambahan di samping Divisi yang tetap dipertahankan.

---

### 3.3 Learning *(revisi besar: aturan post-test + jalur KPI)*

**User Story**
> Sebagai karyawan, saya ingin mempelajari materi dan mengerjakan post-test yang jujur mengukur pemahaman saya (bukan tebak-tebakan), agar kontribusi KPI saya tercatat adil.

**Acceptance Criteria — Post-Test (revisi total dari aturan lama "2x kesempatan"):**
- **Attempt tidak dibatasi** — karyawan boleh mengulang post-test berkali-kali sampai lulus [KONFIRMASI CLIENT — merevisi aturan lama yang membatasi 2x kesempatan/remedial].
- Syarat lulus & dihitung progress KPI: **wajib 100% benar**, tidak ada ambang toleransi.
- **Tidak ada indikator benar/salah per soal** — baik saat mengerjakan maupun setelah submit. Yang ditampilkan setelah submit **hanya skor akhir** (mis. "80%").
- **Kunci jawaban selalu disembunyikan** dari karyawan, kapan pun.
- Jenis soal mendukung **multiple-select** (jawaban benar bisa lebih dari 1 opsi per soal), bukan cuma pilihan tunggal.
- Aturan ini **khusus Learning**. Modul Mission & Game (§3.4) sengaja **tetap** pakai UX lama (feedback instan benar/salah + poin langsung) — dua modul ini dijaga beda UX secara sengaja, jangan disamakan saat implementasi.

**Acceptance Criteria — Kontribusi KPI (Jalur B, lihat detail lengkap poin di §3.5):**
- "5 materi" mencakup **semua 7 jenis materi Learning** (Dokumen/Video/Presentasi/Artikel/Tutorial/Link/File Pendukung) [KONFIRMASI CLIENT] — tidak spesifik video saja.
- 1 materi selesai + post-test 100% = **+1 unit** progress (20% dari total).
- 5/5 unit tercapai = **+1 Poin CPS ERA**, tunduk pada cap 3/tahun (§3.5).

**Acceptance Criteria — Pipeline BA → Learning (mekanisme baru):**
- Saat BA+video disetujui Admin/HRD, video otomatis jadi **kandidat materi Learning** (status `candidate`, belum tampil ke karyawan lain).
- Admin/HRD menyusun post-test dari isi laporan BA tersebut, lalu **rilis manual** (`status → published`) agar bisa dipelajari & dikerjakan post-test-nya oleh karyawan lain.

**Acceptance Criteria — XP dari Learning (opsional, terpisah dari KPI):**
- Admin **boleh** (opsional) menetapkan `xp_reward` per materi Learning — ini masuk ke ledger **XP**, sama sekali tidak berkaitan dengan syarat 100%/KPI Contribution di atas.

---

### 3.4 Mission & Game *(tidak berubah dari v1.0 — dipertegas ulang)*

Tetap seperti desain awal: tipe Case Study/Quiz, jawab pilihan ganda → **feedback instan benar/salah + poin langsung** jika benar. Poin dari modul ini masuk ke ledger **XP**, bukan Poin CPS ERA. Kebijakan retry masih memakai interim lama (Data-Contract §3 item 4): boleh diulang, tapi `point_transactions` cuma dibuat sekali seumur hidup per `quiz_id` — **belum direvisit** di sesi ini, tetap berstatus interim.

---

### 3.5 Sistem Poin & Gamifikasi *(Section baru — pengganti asumsi single-ledger di v1.0)*

> Ini bagian paling banyak berubah dari v1.0. Sebelumnya sistem poin diasumsikan 1 ledger (`total_points`) untuk segalanya — Level, Leaderboard, dan KPI sekaligus. **Ini sudah tidak berlaku.**

**Dua ledger yang sama sekali independen:**

| | **XP** | **Poin CPS ERA** |
|---|---|---|
| Fungsi | Gamifikasi harian — Level & Leaderboard | Metrik formal — bahan evaluasi kinerja HRD |
| Sumber | Quiz Mission & Game (instan) + XP opsional per materi Learning | Jalur A (BA+video approved) & Jalur B (5 materi + post-test 100%) |
| Batas | Tidak dibatasi | **Maksimal 3 poin per tahun** [KONFIRMASI CLIENT], gabungan dari Jalur A + Jalur B |
| Reset | Tidak pernah reset (akumulatif) | **Reset tahunan**, mengikuti periode penilaian kinerja perusahaan (2x/tahun secara HR, tapi data final untuk gaji/bonus dipakai dari semester akhir) [KONFIRMASI CLIENT] |
| Tampil di | Dashboard card "XP & Level", Leaderboard | Dashboard card "Poin CPS ERA (Tahun Ini)", rekap HRD |

**Cara mendapat Poin CPS ERA (cap gabungan 3/tahun):**
- **Jalur A** — BA + video dibuat, disetujui Admin/HRD → **+1 Poin CPS ERA** langsung ke pembuat.
- **Jalur B** — selesaikan 5 materi Learning (jenis apapun) dengan post-test 100% tiap materi → **+1 Poin CPS ERA**.
- Kedua jalur **digabung** menuju batas 3/tahun — bukan 3 per jalur.

**⚠️ Belum final (lihat §6.3):** apakah progress "X dari 5 materi" tetap terus bertambah/tercatat untuk motivasi setelah cap 3 poin/tahun sudah tercapai, atau berhenti dihitung. Juga belum ditentukan tanggal pasti reset tahunan (awal tahun kalender vs mengikuti tanggal penilaian kinerja semester akhir).

**Formula Level dari XP** — ⚠️ **masih interim/asumsi teknis**, belum ada sign-off client eksplisit soal kurva angkanya (lihat §6.4 untuk proposal awal yang bisa dipakai sementara).

---

### 3.6 Dashboard, Leaderboard, Profile, Notifikasi, Achievement *(Modul Pendukung — Dashboard direvisi)*

**Dashboard — 4 widget terpisah** [REKOMENDASI TEKNIS, sudah disetujui client, istilah dipertahankan sesuai versi berjalan]:

| Widget | Isi | Sumber |
|---|---|---|
| **XP & Level** (ganti nama dari "Total Poin Saya") | XP terkumpul, level saat ini, progress bar ke level berikutnya | ledger XP |
| **Learning Progress** | % materi yang ditandai selesai (semua jenis, terlepas ada post-test atau tidak) — metrik keaktifan belajar, bukan metrik formal | `user_learning_progress` |
| **KPI Contribution** | Format "X dari 5 materi" + progress bar — progress Jalur B, reset tahunan | Learning + post-test 100% |
| **Poin CPS ERA (Tahun Ini)** | Format "X / 3" + breakdown kecil "Dari Laporan BA: x · Dari Materi: y" | Jalur A + Jalur B, tabel `point_transactions`/agregasi tahunan |

Modul lain (Leaderboard, Profile, Notifikasi, Achievement) **tidak berubah** dari struktur v1.0 — Leaderboard tetap berbasis **XP** (bukan Poin CPS ERA, karena Poin CPS ERA bukan alat gamifikasi kompetitif).

---

## 4. Rancangan Database (PostgreSQL) — perubahan dari v1.0

> Bagian ini hanya mencantumkan tabel yang **berubah** atau **baru**. Tabel lain (`divisions`, `ba_activity_logs`, `user_bookmarks`, `learning_categories`, `achievements`, `user_achievements`, `notifications`) tidak berubah dari v1.0 — cuma catatan: semua `id` UUID kecuali tabel master data yang tetap BIGINT (lihat §1.4).

> **Seluruh skema di bagian ini berlabel [REKOMENDASI TEKNIS]** — client menetapkan aturan bisnis, bukan nama kolom. Review dulu sebelum dieksekusi ke migration.

### 4.1 `ba_incidents` — struktur aktual (menggantikan skema lama v1.0 total)
Lihat pemetaan lengkap di `CPS_ERA_BA_CAPA_Revision_Prompt.md` §"Pemetaan Field" — tidak diulang di sini untuk menghindari duplikasi yang bisa drift. Ringkasan: kolom `file_ba_url`/`file_ftk_url` sudah **dihapus**, digantikan ~24 kolom form CAPA terstruktur, `status` jadi `draft|submitted|approved|rejected`.

### 4.2 `point_transactions` — tambah kolom pembeda ledger
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | UUID PK | |
| user_id | UUID FK → users.id | |
| **ledger_type** | **ENUM('xp','poin_cps_era') NOT NULL** | **[BARU]** pembeda dua ledger |
| points | INT | |
| source_type | ENUM('mission_completed','learning_material_xp','ba_video_approved','kpi_materi_bundle_completed','admin_adjustment') | **[DIPERBARUI]** tambah 2 nilai baru untuk Poin CPS ERA |
| source_id | UUID NULL | |
| description | VARCHAR(255) | |
| created_at | TIMESTAMP | dipakai untuk hitung periode tahun berjalan |

### 4.3 `users` — rename kolom
| Kolom | Perubahan |
|---|---|
| `total_points` → **`xp`** | INT DEFAULT 0 — denormalized dari `point_transactions` WHERE `ledger_type='xp'` |
| `level` | tetap, dihitung dari `xp` |

### 4.4 `user_kpi_yearly` — **[TABEL BARU]**
Menyimpan progress & rekap Poin CPS ERA per user per tahun (karena sifatnya reset periodik, tidak cocok jadi kolom permanen di `users`).
| Kolom | Tipe |
|---|---|
| id | UUID PK |
| user_id | UUID FK → users.id |
| period_year | INT |
| materials_completed_count | SMALLINT DEFAULT 0 — progress Jalur B (0-5, reset ke 0 tiap kali capai 5) |
| poin_cps_era_earned | SMALLINT DEFAULT 0 — total gabungan, dibatasi maks 3 di level aplikasi |
| poin_from_ba | SMALLINT DEFAULT 0 — breakdown dashboard |
| poin_from_materi | SMALLINT DEFAULT 0 — breakdown dashboard |
| — unique constraint (user_id, period_year) | |

### 4.5 `learning_materials` — tambah 3 kolom
| Kolom | Tipe | Keterangan |
|---|---|---|
| `xp_reward` | INT NULL | opsional, diisi admin |
| `source_ba_id` | UUID NULL FK → ba_incidents.id | diisi otomatis kalau materi berasal dari video BA yang disetujui |
| `status` | ENUM('candidate','published') DEFAULT 'published' | `candidate` khusus video dari BA menunggu post-test dibuat HRD |

### 4.6 `quiz_questions` — tambah 1 kolom
| Kolom | Tipe | Keterangan |
|---|---|---|
| `allow_multiple_answers` | BOOLEAN DEFAULT false | menentukan render checkbox (multi) vs radio (single) di frontend. Skema `quiz_options.is_correct` yang sudah ada **tidak perlu diubah** — cukup validasi "semua opsi correct dipilih & tidak ada opsi salah dipilih" di service layer scoring. |

### 4.7 `knowledge_topics` — **[TABEL BARU]** + relasi
| Kolom | Tipe |
|---|---|
| id | BIGINT UNSIGNED PK |
| name | VARCHAR(150) |
| description | TEXT NULL |
| created_by | UUID FK → users.id |

`knowledge_documents` — tambah kolom `topic_id BIGINT UNSIGNED NULL FK → knowledge_topics.id`. Kolom `division_id` yang sudah ada **dipertahankan dulu** sebagai filter sekunder opsional sampai §6.2 dikonfirmasi (jangan di-drop tanpa keputusan final).

---

## 5. Non-Functional Requirements & Out of Scope

### 5.1 Non-Functional Requirements (diperbarui)
- **Database produksi: PostgreSQL** (bukan MySQL seperti v1.0 — koreksi fakta, lihat §1.4).
- RBAC wajib di level query/policy (`DivisionScopedPolicy`), bukan hanya UI hiding.
- Ledger poin wajib berbasis tabel transaksi (`point_transactions`), sekarang dengan pembeda `ledger_type` — **jangan pernah** mengedit `users.xp` langsung dari luar service perhitungan ledger.
- Stack: Laravel 13 + Livewire 4 + Filament 5, `spatie/laravel-permission`, `spatie/laravel-medialibrary`.

### 5.2 Out of Scope (diperbarui)
- AI search / knowledge assistant pada Knowledge Repository.
- **Integrasi/otomasi apapun dengan sistem SP (Surat Peringatan) atau HRIS** — ini bukan cuma ditunda, tapi **dihapus permanen dari scope** per konfirmasi client eksplisit (bukan lagi item PRD §5.3 versi lama yang masih "dipertimbangkan").
- API publik/mobile (Sanctum) — MVP fokus web.
- Storage file mandiri (S3) — pakai link eksternal dulu untuk video besar.
- Struktur kursus Learning berjenjang (modul→lesson) — tetap struktur flat kategori→materi.

### 5.3 Prinsip Standalone-Auth (dipertegas ulang)
CPS ERA **tidak membaca maupun menulis data ke sistem HRIS eksternal** dalam bentuk apapun, termasuk untuk keperluan SP. Kalau ada permintaan integrasi semacam ini di masa depan, itu adalah **perubahan arsitektur besar** yang harus dibahas ulang sebagai keputusan terpisah, bukan asumsi default.

---

## 6. Hal yang Belum Difinalkan (Konsolidasi — Jangan Diasumsikan Saat Coding)

> Ini gabungan semua open item dari 3 sumber: PRD v1.0 §5.3, konflik di `CPS_ERA_BA_CAPA_Revision_Prompt.md`, dan temuan baru sesi gamifikasi/KR (13-15 Sept). Dikelompokkan per modul biar gampang ditelusuri progres jawabannya.

### 6.1 BA & Lesson Learned *(carry-over dari revisi 12 Sept, belum dijawab ulang)*
1. Reviewer BA+video: benar-benar **1 orang** (Admin ATAU Supervisor, menggantikan dual-approval), atau dual-approval tetap berlaku dan "admin atau atasan" cuma bahasa santai?
2. Apakah status **`Closed`** terpisah masih dibutuhkan setelah `approved`, atau `approved` sudah jadi status akhir?
3. BA yang **`rejected`** boleh diedit ulang untuk resubmit (asumsi interim: boleh), atau wajib buat BA baru?
4. `koreksi_waktu`/`korektif_waktu` tetap VARCHAR bebas, atau perlu jadi tipe DATE (untuk reminder otomatis)?
5. Apakah revisi form digital CAPA ini **spesifik jalur wajib** (akibat insiden) saja — jalur video sukarela (tanpa BA) diasumsikan tetap pakai alur lama, belum dikonfirmasi.
6. Poin untuk aktivitas **review** BA oleh Supervisor/Quality — interim: reviewer tidak dapat poin apapun (XP maupun Poin CPS ERA).

### 6.2 Knowledge Repository *(baru — dari sesi ini)*
7. Apakah **Topik** menggantikan total peran **Divisi** sebagai kategori utama (mengoreksi keputusan lama yang sempat dikunci "FINAL"), atau Topik jadi lapisan tambahan di samping Divisi yang tetap dipertahankan?
8. Apakah Lesson Learned otomatis dari BA tetap dikategorikan per Divisi seperti sekarang, atau ikut dipindah ke bawah struktur Topik juga?

### 6.3 Sistem Poin & Gamifikasi *(baru — dari sesi ini)*
9. Setelah cap 3 Poin CPS ERA/tahun tercapai, apakah progress "X dari 5 materi" tetap terus dicatat (untuk motivasi/riwayat), atau berhenti dihitung karena sudah tidak ada poin lagi yang bisa didapat tahun itu?
10. Tanggal pasti reset tahunan Poin CPS ERA & KPI Contribution — mengikuti tahun kalender, atau tanggal spesifik penilaian kinerja semester akhir perusahaan?
11. Apakah menyelesaikan Jalur A (BA+video sendiri) ikut menambah hitungan progress "X dari 5 materi" di Jalur B untuk orang yang sama, atau kedua jalur benar-benar independen tanpa saling mempengaruhi angka progress satu sama lain?

### 6.4 Level & XP *(carry-over dari v1.0, masih terbuka)*
12. Formula/kurva XP dibutuhkan per level — belum ada sign-off eksplisit. **Proposal interim** [REKOMENDASI TEKNIS, silakan pakai sementara dengan penanda `// TODO: interim, perlu sign-off`]: kurva sederhana `XP dibutuhkan level (n→n+1) = 100 × n` (Level 1→2 = 100 XP, Level 2→3 = 200 XP, dst. — linear dulu, bisa diganti eksponensial kalau dirasa terlalu cepat/lambat setelah data pemakaian riil terkumpul).
13. Kriteria unlock otomatis tiap Achievement/Badge — belum dirinci satu per satu.
14. Batas ukuran & format file — untuk video upload BA (Step 2) dan dokumen Knowledge Repository (PDF/Office) belum ada angka pasti.
15. Kebijakan retry Mission & Game (boleh ulang misi yang sudah selesai, poin berulang atau cuma sekali) — masih interim lama: boleh ulang, poin cuma 1x seumur hidup per `quiz_id`.

---

*Dokumen ini disusun dari akumulasi keputusan lintas beberapa sesi klarifikasi dengan client (12–15 September 2026). Semua item di §6 sengaja dibiarkan terbuka — jangan diisi asumsi sepihak oleh AI tooling manapun (Gemini/Claude) saat eksekusi kode. Kalau butuh salah satu dari 15 poin ini untuk lanjut coding, tandai dengan komentar `TODO: menunggu keputusan PRD v2.0 §6.[nomor]` dan pakai default interim yang sudah disebutkan (kalau ada), bukan mengarang angka/kriteria baru.*
