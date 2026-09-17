# CPS ERA — Prompt Revisi: Sistem Poin Ganda (XP vs Poin CPS ERA) + Aturan Post-Test + Redefinisi Knowledge Repository

Ini pembetulan dari client (sesi klarifikasi 13–15 September 2026), mengganti asumsi single-ledger poin yang sudah dibangun, merevisi aturan post-test Learning, dan mengoreksi definisi Knowledge Repository yang sebelumnya keliru (dibangun seolah tempat penyimpanan BA, padahal seharusnya pustaka pengetahuan umum).

Rujukan lengkap keputusan bisnis ada di `CPS_ERA_PRD_v2.md` — baca dulu bagian §3.2, §3.3, §3.5, §3.6 sebelum eksekusi. Dokumen ini fokus ke instruksi teknis siap eksekusi.

---

## Ringkasan Perubahan (Sebelum vs Sesudah)

| Aspek | Desain Lama (Asumsi Awal) | Desain Baru (Pembetulan Client) |
|---|---|---|
| Sistem poin | 1 ledger (`total_points`) untuk Level, Leaderboard, DAN KPI sekaligus | **2 ledger independen**: `xp` (Level/Leaderboard) dan Poin CPS ERA (metrik KPI formal, terpisah total) |
| Batas poin | Tidak dibatasi | Poin CPS ERA maks **3/tahun**, reset tahunan. XP tidak dibatasi. |
| Post-test attempt | 2x kesempatan (1x remedial) | **Tidak dibatasi**, boleh diulang sampai lulus |
| Syarat lulus post-test | Tidak ditentukan skor pasti | **Wajib 100%** benar untuk dihitung progress KPI |
| Feedback post-test | Diasumsikan ada indikator benar/salah | **Tidak ada** indikator per soal, kunci jawaban selalu disembunyikan, hanya skor akhir yang tampil |
| Jenis soal | Pilihan tunggal | Mendukung **multi-select** (jawaban benar bisa >1 opsi) |
| Knowledge Repository | Isi = Lesson Learned dari BA (implementasi keliru) | Isi = SOP/kebijakan/instruksi kerja dikelola Admin/HRD per **Topik**, PLUS Lesson Learned BA tetap ada sebagai sumber kedua |
| KPI Contribution | Placeholder "Dalam Pengkajian" | **Formula final**: 5 materi (jenis apapun) selesai + post-test 100% = 1 unit (20%), 5/5 = +1 Poin CPS ERA |

> ⚠️ **Beberapa keputusan skema di prompt ini adalah proposal teknis (bukan instruksi client langsung)** — lihat bagian "Catatan — Keputusan Skema yang Perlu Direview" di akhir dokumen sebelum benar-benar dieksekusi ke production.

---

## Pemetaan Perubahan Skema Database

### A. `point_transactions` — tambah kolom pembeda ledger
| Kolom | Perubahan |
|---|---|
| `ledger_type` | **[BARU]** ENUM/string `'xp'` atau `'poin_cps_era'`, NOT NULL |
| `source_type` | Tambah 2 nilai baru: `'ba_video_approved'` (Jalur A), `'kpi_materi_bundle_completed'` (Jalur B) |

### B. `users` — rename kolom
| Kolom Lama | Kolom Baru |
|---|---|
| `total_points` | `xp` |

### C. `user_kpi_yearly` — tabel baru
| Kolom | Tipe |
|---|---|
| id | UUID PK |
| user_id | UUID FK → users.id |
| period_year | INT |
| materials_completed_count | SMALLINT DEFAULT 0 |
| poin_cps_era_earned | SMALLINT DEFAULT 0 |
| poin_from_ba | SMALLINT DEFAULT 0 |
| poin_from_materi | SMALLINT DEFAULT 0 |
| unique (user_id, period_year) | |

### D. `learning_materials` — tambah kolom
| Kolom | Tipe |
|---|---|
| `xp_reward` | INT NULL |
| `source_ba_id` | UUID NULL FK → ba_incidents.id |
| `status` | ENUM('candidate','published') DEFAULT 'published' |

### E. `quiz_questions` — tambah kolom
| Kolom | Tipe |
|---|---|
| `allow_multiple_answers` | BOOLEAN DEFAULT false |

### F. `knowledge_topics` — tabel baru + relasi
| Kolom | Tipe |
|---|---|
| id | BIGINT UNSIGNED PK |
| name | VARCHAR(150) |
| description | TEXT NULL |
| created_by | UUID FK → users.id |

`knowledge_documents` — tambah kolom `topic_id BIGINT UNSIGNED NULL FK → knowledge_topics.id`. **Jangan drop `division_id`** — dipertahankan dulu sebagai filter sekunder sampai ada keputusan final (lihat catatan penutup).

---

## Prompt Siap Pakai

```
## CONTEXT ANCHOR — CPS ERA Backend (baca & pegang ini sepanjang sesi, jangan menyimpang)

Project: CPS ERA (Error-to-Asset Framework) untuk PT Catur Pilar Sejahtera.

Stack terverifikasi (JANGAN asumsikan versi lain): PHP 8.5.10 (compat target ^8.3),
Laravel Framework 13.30.1, Filament 5.7.8, Livewire 4.4.3, PostgreSQL (BUKAN MySQL),
Node 24.19.0, Vite 8.2.2, Tailwind CSS 3.4.19, spatie/laravel-permission 8.3.0,
spatie/laravel-medialibrary 11.23.7.

Aturan arsitektur yang SUDAH DIKUNCI, jangan diubah tanpa instruksi eksplisit di prompt ini:
- Alpine.js sudah otomatis di-inject oleh Livewire 4 -- JANGAN import/inisialisasi manual
  di resources/js/app.js (penyebab bug wire:click ganda yang pernah terjadi sebelumnya).
- Mixed primary key: users/divisions/learning_categories/achievements pakai BIGINT;
  tabel domain/transaksi (ba_incidents/knowledge_documents/quizzes/point_transactions/
  notifications/learning_materials/quiz_questions) pakai UUID. Jangan diseragamkan.
- Semua kolom status (ba_incidents.status, dll) disimpan sebagai STRING biasa, BUKAN
  ENUM native PostgreSQL -- validasi nilai dijaga di level service/aplikasi.
- Sistem poin WAJIB berbasis ledger (tabel point_transactions), BUKAN kolom counter yang
  diupdate langsung dari banyak tempat -- prinsip audit trail, jangan dilanggar.
- 13 divisi resmi (bukan 12): Engineering, Finance Accounting Tax, Gudang RM, HRGA,
  Keamanan, PPIC, Produksi, Purchasing, Quality Control, Repair, Sales & Marketing,
  Warehouse & Delivery, IT.
- RBAC via spatie/laravel-permission, 4 role: Employee, Supervisor, Quality/HRGA, Admin.
  Batasan "divisi sendiri" untuk Supervisor WAJIB dicek lewat Policy/Gate di level query,
  bukan cuma disembunyikan di UI.
- CPS ERA TIDAK PERNAH terhubung ke sistem HRIS/payroll/SP eksternal dalam bentuk apapun.
  Jangan buat kolom, tabel, atau logic apapun yang menyiratkan integrasi ke luar sistem ini.

Dokumen rujukan WAJIB dibaca sebelum eksekusi apapun di sesi ini (kalau tersedia di
workspace/repo, buka dan baca isinya dulu, jangan cuma mengandalkan ringkasan di prompt ini):
- CPS_ERA_PRD_v2.md -- SUMBER KEBENARAN FUNGSIONAL TERBARU, menggantikan PRD v1.0 total.
  Kalau ada bagian mana pun (termasuk file lain di bawah ini) yang bentrok dengan dokumen
  ini, PRD v2.0 yang menang.
- CPS-ERA-Data-Contract.md -- fakta skema yang sudah pasti, TAPI beberapa barisnya SUDAH
  OUTDATED per PRD v2.0 (terutama soal 12 divisi dan status BA 3-tahap, dan soal kategori
  Knowledge Repository yang sempat ditandai "FINAL" tapi sekarang dibuka ulang) -- jangan
  percaya baris yang sudah jelas bentrok dengan PRD v2.0.
- CPS-ERA-Design-System.md -- token visual, jangan diubah kecuali diminta eksplisit.

Kalau instruksi di bawah ini menyebut angka/formula/kriteria yang TIDAK tercantum eksplisit
di PRD v2.0, JANGAN mengarang nilai baru -- pakai default interim yang sudah disebutkan di
PRD v2.0 bagian 6 (kalau ada), beri komentar kode `// TODO: menunggu keputusan PRD v2.0
§6.[nomor]`, dan LANJUTKAN eksekusi (jangan berhenti minta konfirmasi untuk hal yang sudah
ada default interim-nya). Berhenti dan tanya balik HANYA kalau benar-benar tidak ada default
interim yang bisa dipakai.

---

Ini REVISI atas sistem poin, aturan post-test Learning, dan struktur Knowledge Repository
yang sebelumnya dibangun berdasarkan asumsi awal. Client sudah koreksi lewat sesi
klarifikasi 13-15 September 2026. Baca CPS_ERA_PRD_v2.md bagian 3.2, 3.3, 3.5, 3.6 untuk
konteks bisnis lengkap sebelum mengeksekusi ini. JANGAN pertahankan asumsi single-ledger
poin atau batasan "2x attempt" post-test yang lama.

1. MIGRATION — pisahkan ledger poin:
   - Di tabel point_transactions: tambah kolom ledger_type (string/enum: 'xp' atau
     'poin_cps_era'), NOT NULL, tanpa default (setiap insert wajib eksplisit menentukan
     ledger_type-nya). Tambah 2 nilai baru ke source_type: 'ba_video_approved' dan
     'kpi_materi_bundle_completed'.
   - Rename kolom users.total_points menjadi users.xp (buat migration rename, JANGAN
     drop-lalu-create karena akan menghilangkan data yang sudah ada -- gunakan
     $table->renameColumn('total_points', 'xp')).
   - Update SEMUA query/service yang sebelumnya baca/tulis users.total_points supaya
     memakai users.xp, dan pastikan tiap insert ke point_transactions dari fitur Mission
     & Game atau XP opsional Learning material menyertakan ledger_type='xp'.

2. MIGRATION — tabel baru user_kpi_yearly:
   - Kolom: id (UUID PK), user_id (FK users), period_year (INT), materials_completed_count
     (SMALLINT default 0), poin_cps_era_earned (SMALLINT default 0), poin_from_ba
     (SMALLINT default 0), poin_from_materi (SMALLINT default 0).
   - Unique constraint (user_id, period_year).
   - Tabel ini sumber kebenaran untuk progress "X dari 5 materi" dan "X/3 Poin CPS ERA"
     di dashboard -- JANGAN hitung ulang dari scratch tiap request, tapi JANGAN JUGA
     jadikan satu-satunya sumber tanpa ada jejak di point_transactions (setiap kali
     poin_cps_era_earned bertambah, tetap WAJIB ada baris baru di point_transactions
     dengan ledger_type='poin_cps_era' -- prinsip ledger-as-source-of-truth tetap berlaku,
     tabel ini cuma agregat/cache per tahun untuk mempercepat query dashboard).

3. MIGRATION — perluas learning_materials:
   - Tambah xp_reward (INT NULL, opsional diisi admin).
   - Tambah source_ba_id (UUID NULL, FK ke ba_incidents.id).
   - Tambah status (ENUM/string 'candidate' atau 'published', default 'published' untuk
     data lama yang sudah ada; materi baru yang berasal dari video BA disetujui HARUS
     dibuat dengan status='candidate').

4. MIGRATION — perluas quiz_questions:
   - Tambah allow_multiple_answers (BOOLEAN default false).
   - JANGAN ubah struktur quiz_options -- kolom is_correct yang sudah ada CUKUP untuk
     multi-jawaban benar, tinggal ubah logic scoring di poin 6 di bawah.

5. MIGRATION -- tabel baru knowledge_topics + relasi:
   - knowledge_topics: id (BIGINT PK), name (VARCHAR 150), description (TEXT NULL),
     created_by (FK users).
   - Tambah kolom topic_id (BIGINT NULL, FK ke knowledge_topics.id) ke knowledge_documents.
   - JANGAN drop division_id dari knowledge_documents -- pertahankan sebagai filter
     sekunder opsional untuk saat ini (lihat catatan konflik di akhir prompt ini).

6. LOGIC -- Post-Test Learning (revisi total, HAPUS logic "2x kesempatan" yang lama):
   - Hapus validasi/limit jumlah attempt pada quiz_attempts untuk quiz bertipe post_test.
     Karyawan boleh submit ulang tanpa batas.
   - Setelah submit, response API/Livewire HANYA mengembalikan skor akhir (persentase),
     TIDAK mengembalikan status benar/salah per soal, dan TIDAK mengembalikan is_correct
     dari quiz_options manapun. Cek ulang controller/component: pastikan payload ke
     frontend tidak bocor field is_correct dalam bentuk apapun.
   - Untuk soal dengan allow_multiple_answers=true, jawaban dianggap benar HANYA jika
     user memilih PERSIS semua opsi yang is_correct=true DAN TIDAK memilih opsi manapun
     yang is_correct=false. Skor keseluruhan quiz = 100% hanya jika SEMUA soal terjawab
     benar dengan kriteria ini.
   - progress KPI (lihat poin 7) HANYA bertambah kalau score = 100% persis, bukan
     >= passing grade tertentu yang lebih rendah.

7. LOGIC -- KPI Contribution & Poin CPS ERA (BARU, belum ada implementasi sebelumnya):
   - Buat service baru, misal KpiContributionService, dengan method utama:
     recordMaterialCompletion(User $user, LearningMaterial $material, int $score).
   - Method ini dipanggil setelah quiz_attempt untuk post_test tersimpan. Kalau score=100
     DAN user belum pernah mendapat kredit untuk learning_material_id ini di tahun
     berjalan (cek supaya materi yang sama tidak dihitung dobel kalau diulang-ulang):
       a. Increment user_kpi_yearly.materials_completed_count untuk (user, tahun ini).
       b. Kalau materials_completed_count mencapai 5:
          - Reset materials_completed_count kembali ke 0.
          - Cek dulu apakah user_kpi_yearly.poin_cps_era_earned untuk tahun ini SUDAH
            mencapai 3 -- kalau sudah, JANGAN tambah poin lagi (tapi tetap increment
            materials_completed_count seperti biasa dulu, reset ke 0 tetap terjadi --
            lihat catatan poin terbuka soal ini di akhir prompt, ini asumsi interim).
          - Kalau belum mencapai 3: increment poin_cps_era_earned +1, increment
            poin_from_materi +1, DAN insert baris baru ke point_transactions dengan
            ledger_type='poin_cps_era', source_type='kpi_materi_bundle_completed',
            points=1.
   - Buat method kedua: recordBaVideoApproved(User $user, BaIncident $incident) --
     dipanggil dari service approve BA (BaIncidentService::approve()) SETELAH BA berhasil
     diubah statusnya jadi 'approved'. Cek dulu user_kpi_yearly.poin_cps_era_earned tahun
     ini sudah 3 atau belum:
       - Kalau belum 3: increment poin_cps_era_earned +1, increment poin_from_ba +1, insert
         baris baru ke point_transactions dengan ledger_type='poin_cps_era',
         source_type='ba_video_approved', points=1, source_id=incident->id.
       - Kalau sudah 3: JANGAN insert apapun, BA tetap approved seperti biasa, cuma tidak
         ada reward poin tambahan.
   - Buat scheduled command/job tahunan (atau cek lazy saat request pertama tahun baru --
     pilih salah satu, JANGAN dua-duanya) untuk memastikan setiap user punya baris baru
     di user_kpi_yearly untuk tahun berjalan begitu tahun berganti, dengan semua counter
     mulai dari 0.

8. LOGIC -- Pipeline BA yang disetujui -> kandidat materi Learning (BARU):
   - Di BaIncidentService::approve(), setelah efek lama (insert lesson_learned ke
     knowledge_documents, insert poin ke created_by via poin 7 di atas), tambahkan:
     buat 1 baris baru di learning_materials dengan type='video', source_ba_id=incident->id,
     status='candidate', content_url dari video_file_url ATAU video_external_link milik
     BA tersebut, title default "Video Penanganan: [Nomor BA]" (editable nanti oleh HRD).
   - Materi dengan status='candidate' TIDAK boleh muncul di listing Learning yang dilihat
     Employee biasa -- hanya tampil di panel Filament khusus Admin/Quality/HRGA untuk
     ditinjau, dilengkapi post-test, baru di-publish manual (ubah status jadi 'published').

9. UPDATE FILAMENT RESOURCE:
   - Tambah resource baru KnowledgeTopicResource (CRUD topic, role Admin/Quality/HRGA
     saja).
   - Update KnowledgeDocumentResource: tambah field topic_id (select dari
     knowledge_topics), pertahankan field division_id yang sudah ada sebagai opsional.
   - Update LearningMaterialResource: tambah field xp_reward (opsional), tampilkan kolom
     status dengan filter terpisah "Candidate (dari BA)" vs "Published" supaya HRD gampang
     temukan video yang perlu dibuatkan post-test.
   - Update QuizQuestionResource (atau form builder-nya): tambah toggle
     allow_multiple_answers yang mengubah render input opsi jadi checkbox multi-select
     di form pembuatan soal.

10. UPDATE DASHBOARD LIVEWIRE COMPONENT:
    - Ganti card lama "Total Poin Saya" jadi 4 card terpisah sesuai PRD v2.0 §3.6:
      XP & Level, Learning Progress, KPI Contribution ("X dari 5 materi"), Poin CPS ERA
      Tahun Ini ("X / 3" + breakdown dari BA vs dari Materi).
    - Semua card baca dari users.xp (bukan total_points) dan user_kpi_yearly (bukan
      hardcode/placeholder lama).

11. TEST YANG WAJIB ADA:
    - Post-test bisa disubmit berkali-kali tanpa limit, dan response API tidak pernah
      mengandung field is_correct dari opsi manapun.
    - Post-test dengan allow_multiple_answers=true hanya dianggap benar kalau kombinasi
      opsi yang dipilih PERSIS sama dengan opsi yang is_correct=true.
    - Menyelesaikan materi ke-5 (dengan score 100% semua) memicu insert point_transactions
      ledger_type='poin_cps_era' DAN increment user_kpi_yearly dengan benar, lalu counter
      materials_completed_count reset ke 0.
    - Approve BA memicu insert point_transactions ledger_type='poin_cps_era',
      source_type='ba_video_approved', SELAMA cap tahunan (3) belum tercapai.
    - Setelah poin_cps_era_earned mencapai 3 di tahun berjalan, baik lewat Jalur A maupun
      Jalur B, tidak ada penambahan poin CPS ERA lagi sampai tahun berikutnya -- tapi
      approve BA / lulus post-test tetap berjalan normal tanpa poin tambahan (bukan
      ditolak sistemnya).
    - Materi dengan status='candidate' tidak muncul di listing Learning Employee biasa.
    - users.xp terisi benar dari sumber Mission & Game dan XP opsional Learning material,
      TIDAK tercampur dengan poin_cps_era manapun.
```

---

## Checklist Verifikasi

- [ ] `users.total_points` sudah di-rename jadi `users.xp` di semua tempat (migration, model fillable, query lama)
- [ ] `point_transactions.ledger_type` wajib diisi di SETIAP insert baru, tidak ada yang null/default diam-diam
- [ ] Post-test tidak lagi punya limit attempt, dan tidak bocor status benar/salah per soal ke frontend
- [ ] Soal `allow_multiple_answers=true` discoring dengan kriteria "match persis", bukan "minimal 1 benar"
- [ ] `user_kpi_yearly` bertambah benar untuk kedua jalur (BA & materi), dengan cap 3 dijaga di level service
- [ ] Materi Learning hasil video BA approved berstatus `candidate`, tidak tampil ke Employee sebelum di-publish HRD
- [ ] Knowledge Repository sekarang bisa diisi via `knowledge_topics`, bukan cuma menampilkan Lesson Learned BA
- [ ] Dashboard menampilkan 4 card terpisah sesuai desain baru, tidak ada lagi card "Total Poin Saya" tunggal

---

## ⚠️ Catatan — Keputusan Skema & Kebijakan yang Perlu Direview Sebelum Production

Beberapa hal di prompt ini adalah **proposal teknis dari sisi pengembangan**, bukan instruksi eksplisit client — jangan anggap final tanpa konfirmasi ulang (lihat juga `CPS_ERA_PRD_v2.md` §6.2 dan §6.3 untuk daftar lengkapnya):

1. **Topik vs Divisi di Knowledge Repository.** Prompt ini mempertahankan `division_id` sambil menambah `topic_id` baru (dua taksonomi berdampingan). Client belum eksplisit konfirmasi apakah Divisi sebaiknya dihapus total sebagai kategori KR atau memang tetap dipakai berdampingan dengan Topik. Kalau ternyata Divisi harus dihapus dari KR, ini migration tambahan (drop kolom + update semua query filter) yang belum tercakup di prompt ini.

2. **Progress "X dari 5 materi" setelah cap 3 poin/tahun tercapai.** Prompt ini mengasumsikan progress tetap dihitung & reset ke 0 tiap kelipatan 5 (buat riwayat/motivasi) meski tidak ada lagi poin yang diberikan. Kalau client maunya progress **berhenti sepenuhnya** begitu cap tercapai (tidak usah reset-reset lagi sampai tahun baru), logic di poin 7 prompt ini perlu disesuaikan.

3. **Tanggal reset tahunan.** Prompt ini belum menentukan kapan tepatnya "tahun berjalan" berganti (1 Januari kalender, atau tanggal spesifik siklus penilaian kinerja HR). Job/command di poin 7 perlu tanggal pasti sebelum benar-benar dijadwalkan ke scheduler produksi.

4. **Independensi Jalur A dan Jalur B.** Prompt ini mengasumsikan approve BA (Jalur A) TIDAK ikut menambah `materials_completed_count` (Jalur B) — dua hitungan yang benar-benar terpisah. Kalau ternyata client maunya BA yang disetujui juga dihitung sebagai salah satu dari "5 materi", logic poin 7 & 8 perlu digabung.

Begitu 4 poin ini dikonfirmasi ke client, kabari — PRD v2.0 §6.2/§6.3 dan prompt ini bisa langsung disesuaikan jadi versi final tanpa asumsi interim.
