@props([
    'logs' => collect(),
])
{{-- Riwayat aktivitas laporan CAPA (audit trail internal). Hanya untuk reviewer: supervisor, quality, admin. --}}
<div class="bg-white border border-neutral-200 rounded-md p-6 space-y-4">
    <div class="flex items-center justify-between pb-3 border-b border-neutral-200">
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-900 font-sans">
                Update History (Riwayat Aktivitas)
            </h3>
            <p class="text-[11px] text-neutral-600">
                Audit trail dan lini masa perjalanan status dokumen Berita Acara.
            </p>
        </div>
        <span class="text-[10px] font-mono text-neutral-500">
            {{ $logs->count() }} Aktivitas
        </span>
    </div>

    <div class="relative pl-6 space-y-6 before:content-[''] before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-neutral-200">
        @forelse($logs as $log)
            <div class="relative group">
                <span class="absolute -left-6 top-1 w-2.5 h-2.5 rounded-full bg-brand border-2 border-white ring-2 ring-neutral-200"></span>
                <div class="space-y-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs font-bold text-neutral-900">{{ $log->action }}</span>
                        <span class="text-[11px] text-neutral-500">&middot;</span>
                        <span class="text-[11px] text-neutral-600">{{ $log->actor?->name ?? 'Sistem' }}</span>
                        <span class="text-[11px] text-neutral-400 font-mono">{{ $log->created_at->format('d M Y H:i') }}</span>
                    </div>
                    @if($log->note)
                        <p class="text-xs text-neutral-700 bg-neutral-50 p-2.5 rounded border border-neutral-200">
                            {{ $log->note }}
                        </p>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-xs text-neutral-500 py-3">
                Belum ada riwayat aktivitas tercatat.
            </div>
        @endforelse
    </div>
</div>
