<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CPS ERA — Base Component Library Preview</title>

    <!-- Fonts: Inter & IBM Plex Mono -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|ibm-plex-mono:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-neutral-900 bg-neutral-50 antialiased min-h-screen py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto space-y-10">

        <!-- Header -->
        <div class="border-b border-neutral-200 pb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-neutral-900 tracking-tight">
                        CPS ERA — Base Component Library
                    </h1>
                    <p class="text-sm text-neutral-500 mt-1">
                        Implementasi terpusat Design System §5 untuk mencegah visual drift antar halaman.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-neutral-500 font-sans">Status:</span>
                    <x-ui.badge status="closed">Stage 2 Ready</x-ui.badge>
                </div>
            </div>

            <!-- Breakpoint Reference -->
            <div class="mt-4 pt-4 border-t border-neutral-200/60 flex flex-wrap items-center gap-3 text-xs text-neutral-500 font-sans">
                <span class="font-medium text-neutral-900">Uji 3 Breakpoint:</span>
                <span class="px-2 py-0.5 border border-neutral-200 bg-white font-mono rounded-badge">Mobile: 375px</span>
                <span class="px-2 py-0.5 border border-neutral-200 bg-white font-mono rounded-badge">Tablet: 820px</span>
                <span class="px-2 py-0.5 border border-neutral-200 bg-white font-mono rounded-badge">Desktop: 1440px</span>
            </div>
        </div>

        <!-- 1. Buttons (§5) -->
        <section class="space-y-4">
            <div class="border-b border-neutral-200 pb-2">
                <h2 class="text-lg font-semibold text-neutral-900">1. Buttons (Primary & Secondary)</h2>
                <p class="text-xs text-neutral-500 mt-0.5">Fill brand hijau CPS (#0B7840), radius 8px, tanpa panah di teks. Secondary outline neutral-200.</p>
            </div>

            <div class="p-6 bg-white border border-neutral-200 rounded-md space-y-4">
                <div class="flex flex-wrap items-center gap-4">
                    <x-ui.button variant="primary">
                        Simpan Laporan BA
                    </x-ui.button>

                    <x-ui.button variant="secondary">
                        Batal
                    </x-ui.button>

                    <x-ui.button variant="ghost">
                        Reset Filter
                    </x-ui.button>

                    <x-ui.button variant="danger">
                        Hapus Draf
                    </x-ui.button>

                    <x-ui.button variant="primary" disabled>
                        Sedang Memproses
                    </x-ui.button>
                </div>

                <div class="pt-3 border-t border-neutral-200 flex flex-wrap items-center gap-3">
                    <span class="text-xs text-neutral-500 font-medium">Ukuran:</span>
                    <x-ui.button variant="primary" size="sm">Button Small</x-ui.button>
                    <x-ui.button variant="primary" size="md">Button Medium (Default)</x-ui.button>
                    <x-ui.button variant="primary" size="lg">Button Large</x-ui.button>
                </div>
            </div>
        </section>

        <!-- 2. Badges / Status Tag (§5) -->
        <section class="space-y-4">
            <div class="border-b border-neutral-200 pb-2">
                <h2 class="text-lg font-semibold text-neutral-900">2. Status Badges (Stempel Kotak Bersudut Tegas)</h2>
                <p class="text-xs text-neutral-500 mt-0.5">Border 1px, TANPA fill solid, radius 2px (rounded-badge), font IBM Plex Mono.</p>
            </div>

            <div class="p-6 bg-white border border-neutral-200 rounded-md space-y-4">
                <div>
                    <p class="text-xs font-medium text-neutral-500 mb-2">Status BA (3 Tahap Persis):</p>
                    <div class="flex flex-wrap items-center gap-3">
                        <x-ui.badge status="created">Created</x-ui.badge>
                        <x-ui.badge status="reviewed">Reviewed</x-ui.badge>
                        <x-ui.badge status="closed">Closed</x-ui.badge>
                    </div>
                </div>

                <div class="pt-3 border-t border-neutral-200">
                    <p class="text-xs font-medium text-neutral-500 mb-2">Status Achievement & Lainnya:</p>
                    <div class="flex flex-wrap items-center gap-3">
                        <x-ui.badge status="unlocked">Unlocked</x-ui.badge>
                        <x-ui.badge status="locked">Locked</x-ui.badge>
                        <x-ui.badge status="published">Published</x-ui.badge>
                        <x-ui.badge status="draft">Draft</x-ui.badge>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. Metric Cards (§5) -->
        <section class="space-y-4">
            <div class="border-b border-neutral-200 pb-2">
                <h2 class="text-lg font-semibold text-neutral-900">3. Metric Cards</h2>
                <p class="text-xs text-neutral-500 mt-0.5">Label kecil di atas (Inter 12px, normal case), angka besar di bawah (H2 22px), tanpa shadow berat.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-ui.metric-card
                    label="Total Knowledge Assets"
                    value="142"
                    meta="+8 dokumen bulan ini"
                />

                <x-ui.metric-card
                    label="Lesson Learned dari BA"
                    value="38"
                    meta="Terverifikasi Quality"
                />

                <x-ui.metric-card
                    label="Rata-rata Progress Belajar"
                    value="78%"
                    meta="Target divisi: 85%"
                />

                <x-ui.metric-card
                    label="Akumulasi Poin Karyawan"
                    value="2,450"
                    isTechnical="true"
                    meta="Level 6 (Senior Staff)"
                />
            </div>
        </section>

        <!-- 4. List Row dengan Hairline Divider (§5) -->
        <section class="space-y-4">
            <div class="border-b border-neutral-200 pb-2">
                <h2 class="text-lg font-semibold text-neutral-900">4. List Row dengan Hairline Divider</h2>
                <p class="text-xs text-neutral-500 mt-0.5">Dipisahkan divider tipis neutral-200, BUKAN card bertumpuk dengan shadow.</p>
            </div>

            <div class="bg-white border border-neutral-200 rounded-md overflow-hidden">
                <x-ui.list-row :interactive="true">
                    <div>
                        <p class="font-medium text-neutral-900">SOP Penanganan Mesin CNC Saat Overheat</p>
                        <p class="text-xs text-neutral-500 mt-0.5">Divisi Produksi • Diunggah 2 hari lalu</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-ui.badge status="published">Published</x-ui.badge>
                        <span class="font-mono text-xs text-neutral-500">PDF • 2.4 MB</span>
                    </div>
                </x-ui.list-row>

                <x-ui.list-row :interactive="true">
                    <div>
                        <p class="font-medium text-neutral-900">Panduan Pengoperasian Forklift Gudang Raw Material</p>
                        <p class="text-xs text-neutral-500 mt-0.5">Divisi Gudang RM • Diunggah 5 hari lalu</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-ui.badge status="published">Published</x-ui.badge>
                        <span class="font-mono text-xs text-neutral-500">Video • External</span>
                    </div>
                </x-ui.list-row>

                <x-ui.list-row :interactive="true">
                    <div>
                        <p class="font-medium text-neutral-900">Lesson Learned: Kebocoran Seal Hidrolik Injection 04</p>
                        <p class="text-xs text-neutral-500 mt-0.5">Divisi Maintenance • Diunggah kemarin</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-ui.badge status="closed">Closed</x-ui.badge>
                        <span class="font-mono text-xs text-neutral-500">BA-2026-0042</span>
                    </div>
                </x-ui.list-row>
            </div>
        </section>

        <!-- 5. Pola Table-ke-Card untuk Mobile (§4 & §5) -->
        <section class="space-y-4">
            <div class="border-b border-neutral-200 pb-2">
                <h2 class="text-lg font-semibold text-neutral-900">5. Pola Table-ke-Card (Desktop/Tablet: Tabel &rarr; Mobile: Card)</h2>
                <p class="text-xs text-neutral-500 mt-0.5">Buka developer tools dan resize ke 375px untuk melihat tabel berubah menjadi card bertumpuk.</p>
            </div>

            <x-ui.responsive-table>
                <x-slot:head>
                    <th class="py-3 px-4 font-medium">Nomor BA</th>
                    <th class="py-3 px-4 font-medium">Divisi</th>
                    <th class="py-3 px-4 font-medium">Status</th>
                    <th class="py-3 px-4 font-medium">Pelapor</th>
                    <th class="py-3 px-4 font-medium">Tanggal</th>
                    <th class="py-3 px-4 font-medium text-right">Aksi</th>
                </x-slot:head>

                <x-slot:body>
                    <tr class="hover:bg-neutral-50/60 transition-colors">
                        <td class="py-3 px-4 font-mono text-xs text-neutral-900 font-medium">BA-2026-0046</td>
                        <td class="py-3 px-4 text-neutral-900">Produksi</td>
                        <td class="py-3 px-4"><x-ui.badge status="reviewed">Reviewed</x-ui.badge></td>
                        <td class="py-3 px-4 text-neutral-500">Budi Santoso</td>
                        <td class="py-3 px-4 text-neutral-500">06 Sep 2026</td>
                        <td class="py-3 px-4 text-right">
                            <x-ui.button variant="secondary" size="sm">Detail</x-ui.button>
                        </td>
                    </tr>
                    <tr class="hover:bg-neutral-50/60 transition-colors">
                        <td class="py-3 px-4 font-mono text-xs text-neutral-900 font-medium">BA-2026-0045</td>
                        <td class="py-3 px-4 text-neutral-900">Quality Control</td>
                        <td class="py-3 px-4"><x-ui.badge status="closed">Closed</x-ui.badge></td>
                        <td class="py-3 px-4 text-neutral-500">Siti Rahma</td>
                        <td class="py-3 px-4 text-neutral-500">05 Sep 2026</td>
                        <td class="py-3 px-4 text-right">
                            <x-ui.button variant="secondary" size="sm">Detail</x-ui.button>
                        </td>
                    </tr>
                    <tr class="hover:bg-neutral-50/60 transition-colors">
                        <td class="py-3 px-4 font-mono text-xs text-neutral-900 font-medium">BA-2026-0044</td>
                        <td class="py-3 px-4 text-neutral-900">Engineering</td>
                        <td class="py-3 px-4"><x-ui.badge status="created">Created</x-ui.badge></td>
                        <td class="py-3 px-4 text-neutral-500">Ahmad Fauzi</td>
                        <td class="py-3 px-4 text-neutral-500">04 Sep 2026</td>
                        <td class="py-3 px-4 text-right">
                            <x-ui.button variant="secondary" size="sm">Detail</x-ui.button>
                        </td>
                    </tr>
                </x-slot:body>

                <x-slot:mobile>
                    <x-ui.table-card>
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs font-semibold text-neutral-900">BA-2026-0046</span>
                            <x-ui.badge status="reviewed">Reviewed</x-ui.badge>
                        </div>
                        <div class="text-xs text-neutral-500 space-y-1">
                            <p><span class="text-neutral-900 font-medium">Divisi:</span> Produksi</p>
                            <p><span class="text-neutral-900 font-medium">Pelapor:</span> Budi Santoso</p>
                            <p><span class="text-neutral-900 font-medium">Tanggal:</span> 06 Sep 2026</p>
                        </div>
                        <div class="pt-2 border-t border-neutral-200/60 flex justify-end">
                            <x-ui.button variant="secondary" size="sm">Detail</x-ui.button>
                        </div>
                    </x-ui.table-card>

                    <x-ui.table-card>
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs font-semibold text-neutral-900">BA-2026-0045</span>
                            <x-ui.badge status="closed">Closed</x-ui.badge>
                        </div>
                        <div class="text-xs text-neutral-500 space-y-1">
                            <p><span class="text-neutral-900 font-medium">Divisi:</span> Quality Control</p>
                            <p><span class="text-neutral-900 font-medium">Pelapor:</span> Siti Rahma</p>
                            <p><span class="text-neutral-900 font-medium">Tanggal:</span> 05 Sep 2026</p>
                        </div>
                        <div class="pt-2 border-t border-neutral-200/60 flex justify-end">
                            <x-ui.button variant="secondary" size="sm">Detail</x-ui.button>
                        </div>
                    </x-ui.table-card>

                    <x-ui.table-card>
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs font-semibold text-neutral-900">BA-2026-0044</span>
                            <x-ui.badge status="created">Created</x-ui.badge>
                        </div>
                        <div class="text-xs text-neutral-500 space-y-1">
                            <p><span class="text-neutral-900 font-medium">Divisi:</span> Engineering</p>
                            <p><span class="text-neutral-900 font-medium">Pelapor:</span> Ahmad Fauzi</p>
                            <p><span class="text-neutral-900 font-medium">Tanggal:</span> 04 Sep 2026</p>
                        </div>
                        <div class="pt-2 border-t border-neutral-200/60 flex justify-end">
                            <x-ui.button variant="secondary" size="sm">Detail</x-ui.button>
                        </div>
                    </x-ui.table-card>
                </x-slot:mobile>
            </x-ui.responsive-table>
        </section>

        <!-- Anti-AI-Slop Checklist Verification Card -->
        <div class="p-5 bg-white border border-neutral-200 rounded-md">
            <h3 class="text-sm font-semibold text-neutral-900 mb-3">Verifikasi Checklist Anti-AI-Slop (§6)</h3>
            <ul class="text-xs text-neutral-500 space-y-1.5 list-disc list-inside">
                <li><span class="text-neutral-900">Tanpa panah "→"</span> pada seluruh tombol aksi.</li>
                <li><span class="text-neutral-900">Badge kotak sudut tegas (radius 2px)</span>, tanpa fill solid.</li>
                <li><span class="text-neutral-900">Divider tipis (hairline)</span> pada daftar list, bukan card bertumpuk dengan shadow.</li>
                <li><span class="text-neutral-900">Hijau CPS (#0B7840)</span> strictly terkontrol &le;15% dari total luas antarmuka.</li>
                <li><span class="text-neutral-900">Tanpa teks ALL CAPS berulang</span> pada label data metrik.</li>
            </ul>
        </div>

    </div>
</body>
</html>
