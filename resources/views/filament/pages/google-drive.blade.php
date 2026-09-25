<x-filament-panels::page>
    @if (session('gdrive_status'))
        <div class="fi-section rounded-lg border border-success-300 bg-success-50 p-4 text-sm text-success-700">
            {{ session('gdrive_status') }}
        </div>
    @endif

    @if (session('gdrive_error'))
        <div class="fi-section rounded-lg border border-danger-300 bg-danger-50 p-4 text-sm text-danger-700">
            {{ session('gdrive_error') }}
        </div>
    @endif

    <x-filament::section>
        <x-slot name="heading">Status Koneksi</x-slot>
        <x-slot name="description">
            Berkas video CAPA dan dokumen Knowledge Repository diunggah ke akun Google Drive yang terhubung di bawah ini.
        </x-slot>

        @if ($connection)
            <div class="space-y-3 text-sm">
                <div class="flex items-center gap-2">
                    <x-filament::badge color="success">Terhubung</x-filament::badge>
                    @if ($connection->connected_email)
                        <span class="font-medium">{{ $connection->connected_email }}</span>
                    @endif
                </div>

                <dl class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <dt class="text-gray-500">Dihubungkan oleh</dt>
                        <dd>{{ $connection->connectedBy?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Waktu</dt>
                        <dd>{{ $connection->connected_at?->format('d M Y H:i') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Izin diminta</dt>
                        <dd class="break-all">{{ $scope }}</dd>
                    </div>
                </dl>
            </div>
        @else
            <div class="space-y-3 text-sm">
                <x-filament::badge color="warning">Belum terhubung</x-filament::badge>
                <p class="text-gray-600">
                    Unggahan berkas belum dapat berjalan. Hubungkan satu akun Google yang akan menjadi pemilik seluruh berkas.
                </p>
            </div>
        @endif

        <x-slot name="footer">
            @if ($clientConfigured)
                <x-filament::button
                    tag="a"
                    href="{{ route('admin.google-drive.connect') }}"
                    icon="heroicon-o-link"
                    :color="$connection ? 'gray' : 'primary'"
                >
                    {{ $connection ? 'Hubungkan Ulang / Ganti Akun' : 'Connect Google Drive' }}
                </x-filament::button>
            @else
                <span class="text-sm text-danger-600">
                    Isi GOOGLE_DRIVE_CLIENT_ID &amp; GOOGLE_DRIVE_CLIENT_SECRET pada .env terlebih dahulu.
                </span>
            @endif
        </x-slot>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Konfigurasi</x-slot>

        <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-gray-500">Redirect URI (daftarkan persis di Google Cloud Console)</dt>
                <dd class="break-all font-mono text-xs">{{ $redirectUri ?: '(belum diatur)' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Folder video CAPA</dt>
                <dd class="break-all font-mono text-xs">{{ $folderBa ?: '(belum diatur)' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Folder Knowledge Repository</dt>
                <dd class="break-all font-mono text-xs">{{ $folderKnowledge ?: '(belum diatur)' }}</dd>
            </div>
        </dl>
    </x-filament::section>
</x-filament-panels::page>
