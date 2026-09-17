# CPS ERA — Prompt Revisi: Form BA jadi Digital CAPA/FTK + Alur Multi-Step Video

Ini pembetulan dari client, **mengganti desain form BA sebelumnya** (2 file upload: File BA + FTK) jadi form digital terstruktur mengikuti template CAPA/FTK resmi perusahaan (`FR/QC/22`), plus alur submit jadi multi-step kayak Google Form.

---

## Ringkasan Perubahan (Sebelum vs Sesudah)

| Aspek | Desain Lama | Desain Baru (Pembetulan Client) |
|---|---|---|
| Isi BA | Upload 2 file: File BA + FTK | **Form digital terstruktur**, field-nya persis mengikuti template CAPA/FTK (`FR/QC/22`) — TIDAK ada upload file BA/FTK sama sekali |
| Video | Attachment opsional yang nempel ke BA | **Wajib**, jadi **step ke-2** dalam alur submit yang sama (seperti tombol "Selanjutnya" di Google Form) |
| Cara isi video | — | Pilih SALAH SATU: upload file langsung ke CPS ERA, ATAU isi link eksternal (Google Drive/OneDrive/dll) |
| Review | Supervisor/Quality (dari desain lama Tahap 3) | **Admin ATAU Atasan** me-review 1 paket (form CAPA + video) |
| Efek approve | Status Reviewed, otomatis jadi Lesson Learned | Status Disetujui + **poin tambahan** ke user (efek ke Lesson Learned tetap berlaku, lihat Catatan) |

> ⚠️ **Konflik yang perlu dikonfirmasi ke client** — lihat bagian paling bawah dokumen ini SEBELUM benar-benar dieksekusi ke production, terutama soal siapa yang review (1 orang vs dual-approval yang sudah dirancang di prompt Video Contribution sebelumnya).

---

## Pemetaan Field: Form CAPA/FTK → Kolom Database

Diambil langsung dari struktur `Form_FTK.xlsx` yang dilampirkan (dokumen resmi `FR/QC/22`).

| Field di Form CAPA/FTK | Kolom DB (`ba_incidents`) | Tipe | Catatan |
|---|---|---|---|
| No. FTK | `nomor_ba` *(sudah ada)* | VARCHAR(20) | Pakai nomor BA yang sudah ada (`BA-YYYY-NNNN`), tidak perlu nomor terpisah |
| Tanggal | `tanggal_pengisian` | DATE | Default hari ini, tetap bisa diedit |
| Penggagas/Auditor — Nama | `created_by` *(sudah ada, FK users)* | — | Ambil otomatis dari user yang login |
| Bagian | `division_id` *(sudah ada)* | — | Ambil otomatis dari divisi user, bisa diubah |
| Sumber Ketidaksesuaian | `sumber_ketidaksesuaian` | ENUM('keluhan_pelanggan','audit','laporan_ketidaksesuaian','pencapaian_sasaran_program','lain_lain') | Pilihan tunggal (radio), sesuai checkbox di form asli |
| Sumber Ketidaksesuaian — "Lain-lain, sebutkan" | `sumber_ketidaksesuaian_lainnya` | VARCHAR(255) NULL | Wajib diisi hanya jika pilih `lain_lain` |
| Tanggal terjadi masalah | `tanggal_masalah` | DATE | |
| Lokasi/Tempat | `lokasi` | VARCHAR(255) | |
| Masalah | `deskripsi_masalah` | TEXT | |
| Why 1 s/d Why 5 | `why_1`, `why_2`, `why_3`, `why_4`, `why_5` | TEXT (masing-masing) | 5 Whys analysis, field terpisah |
| Kesimpulan | `kesimpulan_akar_masalah` | TEXT | |
| Koreksi — deskripsi/PIC/waktu | `koreksi_deskripsi`, `koreksi_pic`, `koreksi_waktu` | TEXT / VARCHAR(150) / VARCHAR(100) | "Tindakan penanganan sementara" |
| Tindakan Korektif — deskripsi/PIC/waktu | `korektif_deskripsi`, `korektif_pic`, `korektif_waktu` | TEXT / VARCHAR(150) / VARCHAR(100) | "Tindakan perbaikan akar masalah" |
| Potensi Risiko (checkbox) | `is_potensi_risiko` | BOOLEAN | Bisa dicentang bersamaan dengan Potensi Peluang |
| Potensi Peluang (checkbox) | `is_potensi_peluang` | BOOLEAN | |
| Verifikasi: Efektif/Tidak Efektif | `status_verifikasi` | ENUM('efektif','tidak_efektif') NULL | **Diisi REVIEWER saat approve/reject**, bukan oleh pembuat BA |
| Bukti Objektif | `bukti_objektif` | TEXT NULL | Diisi kalau `status_verifikasi = efektif` |
| Alasan (jika tidak efektif) | `alasan_tidak_efektif` | TEXT NULL | Diisi kalau `status_verifikasi = tidak_efektif` |
| Tanda Tangan Verifikator + Tanggal | `reviewed_by`, `reviewed_at` *(sudah ada)* | — | Digital signature = aksi approve itu sendiri, tidak perlu upload tanda tangan gambar |

**Kolom yang DIHAPUS dari desain lama:** `file_ba_url`, `file_ftk_url` (sudah tidak relevan, semua field di atas menggantikannya sebagai data terstruktur, bukan file).

**Tabel `videos` — field baru (mendukung 2 cara input):**
| Kolom | Tipe | Catatan |
|---|---|---|
| `video_file_url` | VARCHAR(255) NULL | Diisi kalau user upload langsung ke CPS ERA |
| `video_external_link` | VARCHAR(255) NULL | Diisi kalau user pakai link Google Drive/OneDrive/dll |
| *(validasi aplikasi)* | — | **Salah satu WAJIB diisi**, tidak boleh dua-duanya kosong, boleh dua-duanya diisi |

---

## Prompt Siap Pakai

```
[TEMPEL BLOK "CONTEXT ANCHOR" DARI ROADMAP BACKEND]

Ini REVISI TOTAL atas modul BA & Lesson Learned yang sebelumnya dibangun di Tahap 3
(yang pakai 2 file upload). Client sudah koreksi desainnya — BA sekarang jadi FORM
DIGITAL TERSTRUKTUR mengikuti template CAPA/FTK resmi perusahaan, dengan alur submit
multi-step. Ikuti spesifikasi ini, JANGAN pertahankan pendekatan file-upload lama untuk
File BA/FTK.

1. MIGRATION — ubah struktur tabel ba_incidents:
   - HAPUS kolom file_ba_url dan file_ftk_url (kalau sudah ada data lama dari Tahap 3
     versi awal, buat migration terpisah untuk backup/migrasi data itu dulu sebelum
     drop kolom, JANGAN langsung drop tanpa strategi backward-compat kalau sudah ada
     data produksi).
   - TAMBAHKAN kolom sesuai tabel pemetaan berikut (gunakan persis nama kolom ini):
     tanggal_pengisian (DATE), sumber_ketidaksesuaian (ENUM 5 pilihan seperti dirinci
     di atas), sumber_ketidaksesuaian_lainnya (VARCHAR NULL), tanggal_masalah (DATE),
     lokasi (VARCHAR), deskripsi_masalah (TEXT), why_1 s/d why_5 (TEXT masing-masing),
     kesimpulan_akar_masalah (TEXT), koreksi_deskripsi (TEXT), koreksi_pic (VARCHAR),
     koreksi_waktu (VARCHAR), korektif_deskripsi (TEXT), korektif_pic (VARCHAR),
     korektif_waktu (VARCHAR), is_potensi_risiko (BOOLEAN default false),
     is_potensi_peluang (BOOLEAN default false), status_verifikasi (ENUM
     'efektif'/'tidak_efektif', NULL default), bukti_objektif (TEXT NULL),
     alasan_tidak_efektif (TEXT NULL).
   - Ubah kolom status dari ENUM lama ('created','reviewed','closed') menjadi ENUM baru:
     'draft','submitted','approved','rejected'. JANGAN hapus histori lama tanpa mapping
     (created->draft/submitted tergantung kondisi, reviewed->approved, closed->approved
     — tulis migration data mapping ini secara eksplisit di comment kode kalau ada data
     lama).

2. MIGRATION — perluas tabel videos:
   - Tambah kolom video_file_url (VARCHAR NULL) dan video_external_link (VARCHAR NULL).
   - Buat validasi di level Form Request/Livewire: salah satu dari dua kolom itu WAJIB
     terisi, tolak submission kalau dua-duanya kosong.

3. LOGIC MULTI-STEP SUBMISSION (buat sebagai Livewire multi-step form atau minimal
   service class yang memisahkan tiap step, JANGAN gabung semua validasi jadi 1 submit
   besar tanpa struktur step):
   - STEP 1 — Form CAPA: semua field dari tabel pemetaan di atas KECUALI field
     Verifikasi (status_verifikasi, bukti_objektif, alasan_tidak_efektif — field itu
     hanya diisi reviewer nanti, JANGAN tampilkan di form Step 1 untuk user pembuat).
     Validasi field wajib: tanggal_masalah, lokasi, deskripsi_masalah, why_1 (minimal
     Why pertama wajib, Why 2-5 opsional tergantung kedalaman analisa), kesimpulan_akar_masalah,
     koreksi_deskripsi, korektif_deskripsi. Simpan sebagai status='draft' saat pindah
     ke step berikutnya (belum final submitted).
   - STEP 2 — Video: pilih metode (upload file ATAU link eksternal), validasi salah
     satu wajib terisi sesuai poin 2. Setelah step ini lengkap, baru status berubah
     dari 'draft' menjadi 'submitted'.
   - Sediakan tombol "Kembali" ke Step 1 dari Step 2 tanpa kehilangan data yang sudah
     diisi (data step 1 tetap tersimpan sebagai draft di database, bukan cuma disimpan
     di state sementara browser).

4. LOGIC REVIEW (SEMENTARA ikuti instruksi client: "admin atau atasan sendiri dapat
   mereview" — TAPI baca CATATAN KONFLIK di bagian akhir prompt ini sebelum
   implementasi final, karena ini kemungkinan bentrok dengan desain dual-approval
   supervisor+HR yang sudah dibangun untuk video contribution sebelumnya):
   - Endpoint/action approve HANYA bisa dieksekusi oleh role admin ATAU supervisor
     (bukan dan, salah satu dari keduanya cukup) yang terkait divisi BA tersebut untuk
     supervisor, atau siapapun untuk admin.
   - Saat approve: reviewer WAJIB mengisi status_verifikasi (efektif/tidak_efektif) dan
     bukti_objektif ATAU alasan_tidak_efektif sesuai pilihannya, isi reviewed_by &
     reviewed_at, ubah status jadi 'approved'.
   - Efek samping approve (SAMA seperti desain lama, tetap dipertahankan):
     a. Insert entri baru ke knowledge_documents type='lesson_learned', ambil ringkasan
        dari deskripsi_masalah + kesimpulan_akar_masalah + korektif_deskripsi, link
        source_ba_id ke BA ini.
     b. Insert point_transactions untuk created_by (poin submit BA+video).
   - Reject: reviewer isi alasan penolakan (field baru, misal catatan_penolakan TEXT),
     status jadi 'rejected'. Tentukan (atau tanya ke client): user boleh edit ulang BA
     yang sama untuk resubmit, atau harus buat BA baru dari nol — untuk sementara
     implementasikan opsi EDIT ULANG (lebih ramah user), tapi tandai ini sebagai
     asumsi yang perlu dikonfirmasi.

5. UPDATE FILAMENT RESOURCE & FORM LIVEWIRE:
   - Ganti form Filament BaIncident dari 2 file input jadi form terstruktur sesuai
     step 1 (bisa satu halaman panjang di Filament, tidak perlu multi-step di admin
     panel — multi-step wizard cukup di sisi Livewire employee-facing).
   - Tampilkan field Verifikasi (status_verifikasi, bukti_objektif/alasan) sebagai
     bagian form approve di Filament, read-only untuk role selain reviewer yang
     bertugas.

6. TEST YANG WAJIB ADA:
   - Submission tidak bisa lanjut ke status 'submitted' kalau Step 2 (video) belum
     lengkap (dua-duanya kosong).
   - Data Step 1 tidak hilang kalau user klik "Kembali" dari Step 2.
   - Approve wajib mengisi status_verifikasi, tidak bisa approve tanpa itu.
   - Reject menyimpan catatan_penolakan, status berubah jadi 'rejected'.
   - Setelah approved, entri lesson_learned otomatis muncul di knowledge_documents
     (perilaku ini TIDAK BOLEH berubah dari desain lama).
```

---

## Checklist Verifikasi

- [ ] Field Step 1 sesuai persis dengan pemetaan tabel CAPA/FTK di atas, tidak ada field hilang/tertukar
- [ ] Step 2 menolak submit kalau video file & link dua-duanya kosong, menerima kalau salah satu (atau keduanya) terisi
- [ ] Kembali dari Step 2 ke Step 1 tidak menghilangkan data yang sudah diisi
- [ ] Approve wajib isi Verifikasi (efektif/tidak efektif + bukti/alasan), tidak bisa dilewati
- [ ] Reviewer bisa admin ATAU supervisor divisi terkait (bukan wajib dua-duanya)
- [ ] Setelah approved: poin masuk ke pembuat BA DAN entri lesson_learned otomatis terbit
- [ ] Data lama (kalau ada dari implementasi Tahap 3 versi file-upload) tidak hilang begitu saja — ada strategi migrasi/backup

---

## ⚠️ Catatan — Konflik & Hal yang WAJIB Dikonfirmasi ke Client

Instruksi client kali ini **berpotensi bentrok** dengan keputusan yang sudah diambil di prompt "Video Contribution" sebelumnya. Jangan langsung eksekusi ke production sebelum ini clear:

1. **Jumlah reviewer beda dari desain sebelumnya.** Prompt Video Contribution sebelumnya menetapkan **dual-approval** (Supervisor DULU, baru HR, dua tahap berurutan) untuk semua video. Instruksi client kali ini bilang **"admin atau atasan sendiri"** — kedengarannya cuma **1 reviewer** (pilih salah satu peran, bukan 2 tahap berurutan). Ini dua desain yang beda. Tanyakan ke client: apakah untuk alur BA+video (jalur wajib akibat kesalahan) ini review-nya **disederhanakan jadi 1 orang** (menggantikan dual-approval), atau **dual-approval tetap berlaku** dan "admin atau atasan" di sini cuma bahasa santai yang sebenarnya tetap maksudnya proses berlapis?

2. **Status "Closed" masih dipakai atau tidak?** Desain lama punya 3 status (Created→Reviewed→Closed). Instruksi client kali ini cuma nyebut sampai "disetujui, dapat poin" — tidak menyebut ada tahap "penutupan" terpisah setelah approved. Prompt di atas SEMENTARA menghilangkan status Closed (diganti jadi cuma draft/submitted/approved/rejected). Konfirmasi: apakah memang sudah cukup sampai "approved" saja, atau tetap perlu ada status akhir "Closed" terpisah (misal untuk kasus yang butuh follow-up jangka panjang)?

3. **BA yang di-reject boleh diedit ulang atau harus buat baru?** Belum disebutkan eksplisit oleh client, prompt di atas ASUMSI boleh edit ulang — perlu dikonfirmasi.

4. **Field "Waktu Pelaksanaan"** (koreksi_waktu, korektif_waktu) — di form asli ini kemungkinan diisi tanggal target atau durasi teks bebas (misal "3 hari", "sebelum 30/09"). Prompt di atas pakai VARCHAR supaya fleksibel — kalau client mau ini jadi DATE field yang bisa dipakai buat reminder/notifikasi otomatis, perlu didesain ulang jadi tipe DATE, bukan teks bebas.

5. **Apakah revisi ini HANYA berlaku untuk jalur wajib (akibat kesalahan), atau jalur video sukarela juga ikut berubah formatnya?** Instruksi client kali ini keliatannya spesifik ke konteks "form BA" (yang memang cuma ada di jalur wajib) — video sukarela (tanpa BA) kemungkinan tetap pakai alur lama (upload video + dual approval dari prompt sebelumnya), TIDAK terpengaruh revisi ini. Ini asumsi yang perlu dikonfirmasi juga.

Begitu 5 poin ini dijawab client, kabarin — aku update PRD bagian 3.1 & 4.2 sekaligus, plus roadmap backend Tahap 3 supaya semuanya konsisten dengan keputusan final.
