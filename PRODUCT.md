# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

### Primary Users
- **Staf Lapangan & Karyawan Operasional Pabrik (Role: Employee)**:
  - **Situasi**: Menghadapi anomali/insiden di lini produksi, mengecek SOP operasional, atau menyelesaikan misi dan modul pembelajaran di sela shift kerja.
  - **Job to be Done**:
    - Melaporkan Berita Acara (BA) dan Form Tindakan Korektif (FTK) secara cepat, akurat, dan tanpa hambatan birokrasi berlebih.
    - Mengakses dokumen SOP dan panduan teknis di Knowledge Repository via smartphone/tablet langsung di lantai pabrik.
    - Menyelesaikan modul materi pelatihan dan evaluasi post-test, menyelesaikan misi aktif, serta mengumpulkan poin kompetensi.

### Secondary Users
- **Supervisor Divisi Pabrik**: Memantau dan memvalidasi laporan BA staf divisi, mengarahkan tindakan korektif dan pencegahan (preventive action), serta mengawal penyelesaian insiden hingga status Closed.
- **Tim Quality & HRGA**: Memvalidasi kurikulum pembelajaran, mengelola materi dan misi pelatihan, serta memonitor konsistensi budaya Kaizen di seluruh divisi.
- **Manajemen Internal Pabrik & Administrator**: Memantau metrik efektivitas perbaikan operasional, leaderboard divisi, master data 12 divisi, dan hak akses sistem.

## Product Purpose

CPS ERA (*Error-to-Asset Framework* / Corporate Knowledge Hub) adalah Enterprise LMS & Incident Management Dashboard untuk PT Catur Pilar Sejahtera guna mentransformasikan anomali dan insiden operasional menjadi aset pengetahuan organisasi yang bernilai tinggi (*"Turn Errors into Assets, Bridge Knowledge for Tomorrow"*).

Sistem ini didesain dengan fokus utama:
1. **Fungsionalitas**: Mempermudah penyelesaian tugas rutin dan alur kerja insiden tanpa friksi atau birokrasi rumit.
2. **Keterbacaan Data**: Struktur informasi terukur, hierarki visual tegas, serta data teknis yang mudah dipindai di lapangan.
3. **Responsivitas Multi-Device**: Tampilan adaptif yang nyaman digunakan mulai dari workstation kantor, tablet pengawas, hingga ponsel pintar operator di area produksi.
4. **Visual Enterprise Modern Tanpa AI Slop**: Menolak estetika template AI generik (bebas tombol panah berlebih, bebas bayangan mengambang tebal, bebas label huruf kapital teriak, dan tanpa elemen dekoratif hampa), berpegang teguh pada prinsip *Industrial Utility* dan filosofi **Kaizen** (*"Problem is Treasure"*).

## Positioning

Berbeda dengan LMS generik atau sistem ticketing insiden terpisah, CPS ERA menyatukan **pelaporan insiden berbasis FTK**, **repositori pengetahuan kontekstual**, **misi operasional berkala**, **gamifikasi berbasis buku besar transaksi (point ledger)**, dan **evaluasi kompetensi terhubung KPI karyawan** ke dalam satu kesatuan sistem kerja harian perusahaan manufaktur.

## Operating Context

- **Lingkungan Penggunaan**: Lantai produksi pabrik, lini perakitan, area pergudangan, ruang kontrol operasional, dan meja kerja kantor administrasi PT Catur Pilar Sejahtera.
- **Perangkat**: Komputer desktop kantor, laptop workstation, tablet lapangan, dan smartphone staf operasional.
- **Alur Kerja Utama**:
  1. Terjadi anomali di pabrik → Staf membuat BA (nomor otomatis `BA-YYYY-NNNN`) dan mengunggah 2 berkas terpisah (Kronologi BA & FTK).
  2. Supervisor dan tim Quality/HRGA meninjau, menguji tindakan korektif, dan memperbarui status hingga Closed.
  3. Solusi insiden dirangkum dan diterbitkan ke Knowledge Repository atau dijadikan materi di Learning Hub.
  4. Staf menyelesaikan modul pembelajaran, misi harian/mingguan, dan evaluasi post-test (passing grade minimum 70%).
  5. Poin reward otomatis dicatat ke buku besar transaksi poin (`point_transactions`) dan merefleksikan posisi staf di Leaderboard.

## Capabilities and Constraints

### Confirmed Capabilities: 7 Halaman Utama Aplikasi
Aplikasi mencakup 7 halaman utama antarmuka pengguna internal:
1. **Dashboard**: Pusat ringkasan eksekutif dan operasional pribadi (metrik KPI personal, kuota belajar harian, status laporan aktif, notifikasi insiden, dan progres level).
2. **Knowledge Repository**: Repositori terpusat SOP pabrik, panduan teknis, dan video tutorial dengan pencarian instan, filter divisi, sortir kategori, serta penandaan (bookmark) dokumen.
3. **Berita Acara (BA)**: Form pelaporan insiden/anomali, unggah berkas ganda (Kronologi BA & FTK), audit log riwayat peninjauan (*Update History*), serta alur status terverifikasi (Created → Reviewed → Closed).
4. **Learning**: Katalog materi pelatihan multimedia (video, dokumen, tautan), pelacakan progres belajar real-time, gating evaluasi ketat 100% progres, dan post-test interaktif berbobot poin.
5. **Missions**: Antarmuka misi dan tantangan berkala (harian/mingguan/divisi) untuk mendorong penyelesaian target operasional dan pembelajaran aktif staf.
6. **Leaderboard**: Papan peringkat kompetensi dan kontribusi perbaikan (akumulasi poin) transparan per divisi dan tingkat perusahaan, dilengkapi kartu peringkat personal.
7. **Achievements**: Galeri lencana pencapaian (badges) atas pencapaian milestone Kaizen dan penyelesaian kurikulum kompetensi.

### Technical Constraints
- Dikerjakan oleh 1 developer (solo): arsitektur pragmatis, modular, minim dependensi luar, mengutamakan stabilitas kode dan kemudahan pemeliharaan.
- Stack teknologi: Laravel 12 (PHP 8.4), Livewire 3, Alpine.js, Tailwind CSS, Filament 4, dan database MySQL.
- Standar visual wajib patuh pada `CPS-ERA-Design-System.md` (dominasi putih 85-90%, aksen hijau resmi `#0B7840`, font Inter untuk teks umum dan IBM Plex Mono untuk data teknis).

### Undecided Facts (Open Decisions PRD §5.3 / TODO)
Fakta-fakta kebijakan berikut sengaja ditandai sebagai keputusan terbuka dan menunggu keputusan final manajemen PT Catur Pilar Sejahtera:
1. Bobot poin pasti antar aktivitas (submit BA vs membuat materi vs kuis).
2. Formula kalkulasi akumulasi poin ke persentase penilaian KPI bulanan.
3. Kebijakan konversi poin ke reward finansial atau benefit non-moneter.
4. Kebijakan batas pengulangan (retry) dan cooldown pengerjaan post-test.
5. Kebijakan masa berlaku / kedaluwarsa poin (apakah ada reset tahunan).
6. Ketetapan Service Level Agreement (SLA) durasi review BA (apakah ada auto-approval).
7. Ketentuan spesifik untuk membuka (unlock) masing-masing badge achievement.

## Brand Commitments

- **Nama Produk**: CPS ERA (*Error-to-Asset Framework*).
- **Perusahaan**: PT Catur Pilar Sejahtera.
- **Tagline**: *"Turn Errors into Assets, Bridge Knowledge for Tomorrow"*.
- **Warna Identitas**:
  - `brand`: `#0B7840` (Hijau industri resmi perusahaan).
  - `brand-dark`: `#085C30` (Aksen hover dan penekanan teks).
  - `brand-tint`: `#E8F5EC` (Latar belakang lembut badge dan highlight status).
- **Logo Perusahaan**: Menggunakan file logo resmi oval PT Catur Pilar Sejahtera (`public/images/cps-logo.png`). Dilarang diwarnai ulang atau diletakkan di atas background gelap/hijau.
- **Tipografi**:
  - `Inter`: Font tunggal untuk seluruh heading, label, navigasi, dan body text.
  - `IBM Plex Mono`: Dikhususkan untuk data teknis (Employee ID `CPS-00124`, Nomor BA `BA-YYYY-NNNN`).
- **Voice & Tone**: Profesional, lugas, mengedepankan perbaikan berkelanjutan, menghargai keterbukaan, serta objektif tanpa menyudutkan.

## Evidence on Hand

- **Spesifikasi Produk**: [CPS_ERA_PRD.md](file:///c:/MyProject/CPS-ERA/CPS_ERA_PRD.md) Versi 1.0.
- **Panduan Desain**: [CPS-ERA-Design-System.md](file:///c:/MyProject/CPS-ERA/CPS-ERA-Design-System.md).
- **Kontrak Data & Skema Database**: [CPS-ERA-Data-Contract.md](file:///c:/MyProject/CPS-ERA/CPS-ERA-Data-Contract.md).
- **Aset Visual Resmi**: [cps-logo.png](file:///c:/MyProject/CPS-ERA/public/images/cps-logo.png) di direktori publik aplikasi.
- **Rangkaian Automated Tests**: 176 automated tests (100% lulus, 1262 assertions) yang memvalidasi seluruh alur bisnis, RBAC, livewire upload, evaluasi post-test, foto profil, dan rendering UI.

## Product Principles

1. **Problem is Treasure (Filosofi Kaizen)**: Kesalahan operasional diperlakukan sebagai aset pembelajaran, bukan alasan untuk mencari kambing hitam.
2. **Fungsionalitas & Kecepatan Operasional**: Alur kerja dirancang seringkas dan seefisien mungkin bagi operator lapangan tanpa mengurangi kelengkapan audit trail sistem.
3. **Keterbacaan Data & Anti-AI Slop**: Menolak ornamen visual generik yang hampa. Mengutamakan hairline border industrial, kontras warna yang nyaman, dan tipografi terstruktur.
4. **Pengetahuan Terbuka Lintas Divisi**: Solusi atas suatu kendala harus dapat diakses dan dimanfaatkan oleh seluruh karyawan demi mencegah insiden berulang.
5. **Gamifikasi yang Adil & Transparan**: Setiap perolehan poin dicatat pada buku besar (*point ledger*) yang jelas sumber dan riwayatnya untuk menjaga kepercayaan karyawan terhadap penilaian KPI.

## Accessibility & Inclusion

- Memenuhi standar WCAG 2.1 Level AA dengan rasio kontras warna teks minimal 4.5:1 terhadap latar belakang putih/terang.
- Responsivitas mobile-first untuk staf lapangan: tabel multi-kolom otomatis bertransformasi menjadi kartu bertumpuk pada layar smartphone (<640px).
- Ukuran target sentuh (touch target) pada tombol aksi utama minimal 44x44px untuk kenyamanan penggunaan dengan sarung tangan atau perangkat portabel.
