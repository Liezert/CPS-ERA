---
target: Formulir CAPA / FTK (FR/QC/22)
total_score: 32
max_score: 40
na_heuristics: 
p0_count: 0
p1_count: 2
target_identity: "file:C:\\MyProject\\CPS-ERA\\resources\\views\\livewire\\ba\\create.blade.php"
target_fingerprint: "sha256:04dd1fe501f188f83144b4a09270569c9b66fbd9aa638e235c8ed4177f3eb234"
target_path: "C:\\MyProject\\CPS-ERA\\resources\\views\\livewire\\ba\\create.blade.php"
timestamp: 2026-09-12T15-38-08Z
slug: resources-views-livewire-ba-create-blade-php
---
# UI/UX Design Critique: Formulir CAPA / FTK (FR/QC/22)

Target: `resources/views/livewire/ba/create.blade.php`  
Platform: Web (Desktop, Tablet, Mobile)  
Standard: CPS ERA Design System & Kaizen Industrial QC Framework

---

## Design Health Score

| # | Heuristic | Score | Key Issue |
|---|---|---|---|
| 1 | Visibility of System Status | 3 | Stepper dan status loading jelas, namun belum ada indikator auto-save saat mengetik analisis panjang. |
| 2 | Match Between System and Real World | 4 | Istilah standar mutu manufaktur (FR/QC/22, 5 Whys, Containment, Korektif) sangat sesuai dunia nyata. |
| 3 | User Control and Freedom | 3 | Ada tombol Batal dan Kembali, tetapi node stepper tidak interaktif untuk navigasi langsung. |
| 4 | Consistency and Standards | 4 | Sangat konsisten dengan CPS ERA Design System (Inter, IBM Plex Mono, radius 8px/2px, token netral). |
| 5 | Error Prevention | 3 | Nomor BA terbit otomatis dan dropdown divisi aman; Why 1-5 belum memiliki guardrail panjang/kejelasan teks. |
| 6 | Recognition Rather Than Recall | 3 | Step 2 meringkas No. FTK & masalah, tetapi menyembunyikan konteks detail akar masalah dari Step 1. |
| 7 | Flexibility and Efficiency of Use | 3 | Dua metode video tersedia (file & URL); belum ada keyboard shortcuts untuk pindah step. |
| 8 | Aesthetic and Minimalist Design | 3 | Tata letak industrial bersih; kepadatan vertikal di mobile cukup tinggi (6 seksi sekaligus). |
| 9 | Error Recovery | 3 | Banner error dan pesan inline jelas, tetapi belum ada auto-scroll ke field error pertama pada layar mobile. |
| 10 | Help and Documentation | 3 | Microcopy panduan jelas di setiap input; belum ada modal panduan contoh studi kasus nyata Kaizen RCA. |
| **Total** | | **32/40** | **Good (80%)** |

---

## Design Specificity Verdict

**LLM Assessment**: Antarmuka ini dirancang secara spesifik untuk lingkungan operasional pabrik PT Catur Pilar Sejahtera, bukan formulir CRUD generik. Menggunakan bahasa kontrol mutu baku (ISO 9001 / Kaizen) seperti pembedaan *Containment* versus *Corrective Action*, tangga kausalitas *5 Whys*, dan registrasi terkontrol `FR/QC/22`. Komposisinya memprioritaskan kegunaan industri (*industrial utility*) dengan rasio kontras tinggi dan hierarki tegas.

**Deterministic Scan**: Tool detektor `impeccable detect` melaporkan 0 temuan defek mekanikal pada file `resources/views/livewire/ba/create.blade.php`.

**Visual Overlays**: Tidak ada overlay visual browser eksternal yang diinjeksikan dalam sesi ini.

---

## Overall Impression

Formulir CAPA/FTK telah memiliki fondasi industrial yang sangat solid, berkarakter tegas, dan mematuhi aturan desain CPS ERA. Peluang peningkatan utama terletak pada **manajemen kepadatan kognitif (cognitive load)** pada layar mobile/tablet: memberikan *progressive disclosure* pada analisis 5 Whys dan menyambungkan rujukan visual antara *Kesimpulan Akar Masalah* dengan *Tindakan Korektif*.

---

## What's Working

1. **Header Dokumen Mutu Resmi (FR/QC/22)**: Menghadirkan identitas kontrol kualitas resmi dengan kop kode dokumen, status revisi, dan nomor registrasi otomatis `BA-YYYY-NNNN` yang menumbuhkan rasa disiplin pelaporan.
2. **Pembedaan Semantik Koreksi vs Korektif**: Penggunaan aksen amber (tindakan darurat lokalisir/containment) berdampingan dengan aksen hijau brand (tindakan perbaikan permanen) secara tepat mengedukasi staf dan mencegah kerancuan konsep mutu ISO 9001.
3. **Kontras dan Keterbacaan Data**: Teks utama `neutral-900` dengan border kontrol `neutral-300` memberikan kontras tinggi (WCAG AA) yang sangat nyaman dibaca oleh operator di lantai pabrik dengan kondisi pencahayaan beragam.

---

## Priority Issues

### [P1] Kepadatan Vertikal & Ketiadaan Progressive Disclosure pada 5 Whys
- **Why it matters**: Menampilkan 5 kotak Why secara simultan sejak awal membuat form terasa sangat panjang dan intimidatif bagi operator shift lapangan, memicu kelelahan mental atau *writer's block*.
- **Fix**: Terapkan *progressive disclosure*: Why 1 ditampilkan wajib, Why 2 s/d 5 dibuka bertahap melalui tombol "+ Tambah Tingkat Analisis Kausalitas (Why N)".
- **Suggested command**: `/impeccable distill` atau `/impeccable layout`.

### [P1] Stepper Non-Interaktif & Ketiadaan Auto-Scroll Error
- **Why it matters**: Pada layar mobile, jika tombol "Lanjut" ditekan dan validasi gagal di bagian atas (misal dropdown Divisi terlewat), operator di bawah tidak melihat letak error karena berada di luar viewport. Selain itu, stepper atas tidak dapat diklik untuk navigasi cepat.
- **Fix**: Jadikan badge stepper sebagai tombol navigasi aktif jika draf tersimpan, dan pasang event listener Alpine.js untuk *smooth auto-scroll* ke field error pertama.
- **Suggested command**: `/impeccable clarify` atau `/impeccable harden`.

### [P2] Ketiadaan Tautan Rujukan Visual Antara "Akar Masalah" dan "Tindakan Korektif"
- **Why it matters**: Dalam kaidah Kaizen, Tindakan Korektif wajib mematikan secara presisi apa yang disimpulkan pada Akar Masalah. Terpisahnya kedua seksi tanpa penanda relasi visual memaksa user bolak-balik mengingat teks kesimpulan.
- **Fix**: Tambahkan callout penunjuk atau kartu cuplikan ringkas di atas formulir Tindakan Korektif: *"Fokus Tindakan untuk Mengatasi: [Teks Kesimpulan Akar Masalah]"*.
- **Suggested command**: `/impeccable layout`.

### [P2] Ketiadaan Tombol Eksplisit "Simpan Draf Saja"
- **Why it matters**: Operator yang sedang mengisi form di lantai pabrik sering kali terinterupsi panggilan darurat mesin. Memaksa mereka menekan "Lanjut ke Langkah 2 (Video)" untuk menyimpan draf menimbulkan friksi.
- **Fix**: Sediakan tombol sekunder "Simpan Draf" yang menyimpan progres Langkah 1 tanpa berpindah layar.
- **Suggested command**: `/impeccable harden`.

---

## Persona Red Flags

- **Casey (Operator Lapangan / Mobile)**:
  - Mengisi formulir satu tangan di ponsel dekat area mesin. Tampilan 6 seksi bertumpuk tanpa accordion membuat jarak scroll sangat panjang. Saat tombol "Lanjut" ditekan dan muncul pesan error di atas, Casey bingung karena tombol tidak merespons dan layar tidak otomatis bergulir ke sumber error.
- **Jordan (Karyawan Baru / First-Timer)**:
  - Melihat kotak Why 1 sampai Why 5 berderet kosong memicu kecemasan bahwa seluruh Why harus diisi uraian teknis rumit. Ketiadaan contoh konkret kontekstual berisiko menghasilkan teks seadanya (*filler text*).
- **Alex (Supervisor / Quality Auditor)**:
  - Saat meninjau rencana perbaikan di Langkah 1, Alex harus membaca ulang seluruh rangkaian 5 Whys untuk memvalidasi apakah deskripsi Tindakan Korektif benar-benar sejalan dengan Kesimpulan Akar Masalah.

---

## Minor Observations

- Label "No. FTK / Register BA" pada input readonly sudah memiliki ikon gembok yang baik; penambahan tombol salin cepat (*copy to clipboard*) satu-klik akan semakin mempermudah operator saat berkomunikasi di grup chat koordinasi.
- Pada Langkah 2 (Video), jika operator memilih metode Tautan Eksternal, contoh URL bisa diperluas dengan panduan visual ringkas cara mengambil link "Anyone with the link can view" dari aplikasi mobile Google Drive.

---

## Questions to Consider

- Apakah analisis 5 Whys sebaiknya disederhanakan dengan opsi *progressive reveal* (+ Tambah Why) agar operator tidak merasa terbebani saat kasusnya sederhana?
- Apakah perlu ditambahkan tombol "Simpan Draf Sekarang" di Langkah 1 agar operator bisa menyimpan progres tanpa harus validasi lengkap untuk lanjut ke video?
- Apakah kartu Tindakan Korektif perlu menampilkan cuplikan dinamis dari Kesimpulan Akar Masalah untuk menjaga benang merah solusi?
