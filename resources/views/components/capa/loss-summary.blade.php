@props(['incident'])
{{-- Catatan potensi kerugian dari Supervisor (review tahap 1). --}}
<div {{ $attributes }}>
    <dt class="font-semibold text-neutral-600">Potensi Kerugian (catatan Supervisor)</dt>
    @if ($incident->potensi_kerugian)
        <dd class="mt-1 text-neutral-900">Ada · rekomendasi ganti rugi <strong class="font-mono">Rp {{ number_format($incident->nilai_kerugian, 0, ',', '.') }}</strong></dd>
        <dd class="mt-1">
            <ul class="space-y-0.5 text-neutral-800">
                @foreach ($incident->penanggung_kerugian ?? [] as $row)
                    <li>Ditanggung oleh {{ $row['nama'] }} sebesar <span class="font-mono">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</span></li>
                @endforeach
            </ul>
        </dd>
    @else
        <dd class="mt-1 text-neutral-900">Tidak ada</dd>
    @endif
</div>
