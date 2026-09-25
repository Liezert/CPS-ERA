<x-filament-widgets::widget class="fi-quick-actions-widget">
    <style>
        .fi-qa-grid {
            display: grid !important;
            grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
            gap: 0.75rem !important;
        }
        @media (min-width: 640px) {
            .fi-qa-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }
        @media (min-width: 1024px) {
            .fi-qa-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            }
        }
        .fi-qa-card {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            gap: 0.75rem !important;
            padding: 0.875rem 1rem !important;
            background-color: #ffffff !important;
            border: 1px solid #e4e4e7 !important;
            border-radius: 8px !important;
            text-decoration: none !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            transition: all 0.2s ease-in-out !important;
            box-sizing: border-box !important;
        }
        .fi-qa-card:hover {
            border-color: #0B7840 !important;
            background-color: #f4fbf6 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08) !important;
        }
        .fi-qa-icon-wrap {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 2.5rem !important;
            height: 2.5rem !important;
            border-radius: 8px !important;
            flex-shrink: 0 !important;
        }
        .fi-qa-icon-wrap svg {
            width: 1.25rem !important;
            height: 1.25rem !important;
        }
        .fi-qa-content {
            display: flex !important;
            flex-direction: column !important;
            flex: 1 1 0% !important;
            min-width: 0 !important;
        }
        .fi-qa-title {
            display: block !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            color: #18181b !important;
            line-height: 1.25rem !important;
        }
        .fi-qa-card:hover .fi-qa-title {
            color: #0B7840 !important;
        }
        .fi-qa-desc {
            display: block !important;
            font-size: 0.75rem !important;
            color: #71717a !important;
            line-height: 1rem !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }
        .fi-qa-arrow {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            color: #a1a1aa !important;
            flex-shrink: 0 !important;
            width: 1.125rem !important;
            height: 1.125rem !important;
            transition: transform 0.2s ease, color 0.2s ease !important;
        }
        .fi-qa-arrow svg {
            width: 1.125rem !important;
            height: 1.125rem !important;
        }
        .fi-qa-card:hover .fi-qa-arrow {
            color: #0B7840 !important;
            transform: translateX(2px);
        }

        .fi-qa-alert {
            display: flex !important;
            flex-direction: column !important;
            gap: 0.75rem !important;
            margin-top: 1rem !important;
            padding: 0.875rem 1rem !important;
            background-color: #fffbeb !important;
            border: 1px solid #fde68a !important;
            border-radius: 8px !important;
            box-sizing: border-box !important;
        }
        @media (min-width: 640px) {
            .fi-qa-alert {
                flex-direction: row !important;
                align-items: center !important;
                justify-content: space-between !important;
            }
        }
        .fi-qa-alert-content {
            display: flex !important;
            align-items: center !important;
            gap: 0.75rem !important;
        }
        .fi-qa-alert-icon {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 2.25rem !important;
            height: 2.25rem !important;
            border-radius: 9999px !important;
            background-color: #fef3c7 !important;
            color: #b45309 !important;
            flex-shrink: 0 !important;
        }
        .fi-qa-alert-icon svg {
            width: 1.25rem !important;
            height: 1.25rem !important;
        }
        .fi-qa-alert-title {
            font-size: 0.875rem !important;
            font-weight: 700 !important;
            color: #78350f !important;
            margin: 0 !important;
            line-height: 1.25rem !important;
        }
        .fi-qa-alert-desc {
            font-size: 0.75rem !important;
            color: #92400e !important;
            margin: 0 !important;
            line-height: 1rem !important;
        }
    </style>

    <x-filament::section>
        <x-slot name="heading">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <x-filament::icon icon="heroicon-o-bolt" class="h-5 w-5" style="width: 1.25rem; height: 1.25rem; color: #0B7840;" />
                <span style="font-weight: 700; color: #18181b;">Aksi Cepat & Pintasan</span>
            </div>
        </x-slot>

        <x-slot name="description">
            Akses langsung untuk membuat materi pembelajaran, evaluasi quiz & misi, dokumen pengetahuan, atau input laporan CAPA baru.
        </x-slot>

        <div class="fi-qa-grid">
            <a
                href="{{ route('filament.admin.resources.learning-materials.create') }}"
                class="fi-qa-card"
            >
                <div class="fi-qa-icon-wrap" style="background-color: #E8F5EC; color: #0B7840;">
                    <x-filament::icon icon="heroicon-o-bookmark-square" />
                </div>
                <div class="fi-qa-content">
                    <span class="fi-qa-title">Tambah Materi</span>
                    <span class="fi-qa-desc">Modul Learning Hub</span>
                </div>
                <div class="fi-qa-arrow">
                    <x-filament::icon icon="heroicon-m-chevron-right" />
                </div>
            </a>

            <a
                href="{{ route('filament.admin.resources.quizzes.create') }}"
                class="fi-qa-card"
            >
                <div class="fi-qa-icon-wrap" style="background-color: #ecfdf5; color: #047857;">
                    <x-filament::icon icon="heroicon-o-sparkles" />
                </div>
                <div class="fi-qa-content">
                    <span class="fi-qa-title">Buat Quiz & Misi</span>
                    <span class="fi-qa-desc">Evaluasi & gamifikasi</span>
                </div>
                <div class="fi-qa-arrow">
                    <x-filament::icon icon="heroicon-m-chevron-right" />
                </div>
            </a>

            <a
                href="{{ route('filament.admin.resources.knowledge-documents.create') }}"
                class="fi-qa-card"
            >
                <div class="fi-qa-icon-wrap" style="background-color: #eff6ff; color: #1d4ed8;">
                    <x-filament::icon icon="heroicon-o-book-open" />
                </div>
                <div class="fi-qa-content">
                    <span class="fi-qa-title">Tambah Dokumen</span>
                    <span class="fi-qa-desc">SOP / UU / Repository</span>
                </div>
                <div class="fi-qa-arrow">
                    <x-filament::icon icon="heroicon-m-chevron-right" />
                </div>
            </a>

            <a
                href="{{ route('filament.admin.resources.ba-incidents.create') }}"
                class="fi-qa-card"
            >
                <div class="fi-qa-icon-wrap" style="background-color: #fef3c7; color: #b45309;">
                    <x-filament::icon icon="heroicon-o-document-plus" />
                </div>
                <div class="fi-qa-content">
                    <span class="fi-qa-title">Input Laporan CAPA</span>
                    <span class="fi-qa-desc">Berita Acara & Tindakan</span>
                </div>
                <div class="fi-qa-arrow">
                    <x-filament::icon icon="heroicon-m-chevron-right" />
                </div>
            </a>
        </div>

        @if ($pendingDraftCount > 0)
            <div class="fi-qa-alert">
                <div class="fi-qa-alert-content">
                    <div class="fi-qa-alert-icon">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" />
                    </div>
                    <div>
                        <h4 class="fi-qa-alert-title">Perlu Tindakan Admin</h4>
                        <p class="fi-qa-alert-desc">
                            Terdapat <strong style="font-weight: 700;">{{ $pendingDraftCount }} Laporan CAPA</strong> yang menunggu review Supervisor atau HR.
                        </p>
                    </div>
                </div>

                <x-filament::button
                    tag="a"
                    href="{{ route('filament.admin.resources.ba-incidents.index', ['tableFilters[status][value]' => 'draft']) }}"
                    color="warning"
                    size="sm"
                    icon="heroicon-m-arrow-right"
                    icon-position="after"
                    style="flex-shrink: 0;"
                >
                    Tinjau Laporan ({{ $pendingDraftCount }})
                </x-filament::button>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
