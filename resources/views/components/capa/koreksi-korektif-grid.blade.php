@props([
    'incident' => null,
    'record' => null,
])

@php
    $ba = $incident ?? $record ?? (isset($getRecord) ? $getRecord() : null);
@endphp

@if($ba)
<div {{ $attributes->merge([
    'class' => 'grid grid-cols-1 md:grid-cols-2 gap-4 w-full',
    'style' => 'display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; width: 100%;'
]) }}>
    {{-- Kartu A: Tindakan Koreksi Sementara (Containment) - Amber/Oranye --}}
    <div class="border border-amber-300/80 bg-amber-50/25 rounded-md p-4 space-y-3 flex flex-col justify-between"
         style="border: 1px solid rgba(252, 211, 77, 0.8); background-color: rgba(254, 243, 199, 0.25); border-radius: 8px; padding: 1rem; display: flex; flex-direction: column; justify-content: space-between; box-sizing: border-box;">
        <div class="space-y-2.5" style="display: flex; flex-direction: column; gap: 0.625rem;">
            {{-- Header Kartu Koreksi --}}
            <div class="flex items-center justify-between pb-2 border-b border-amber-200/80 flex-wrap gap-1.5"
                 style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 0.5rem; border-bottom: 1px solid rgba(253, 230, 138, 0.8); gap: 0.375rem;">
                <div class="flex items-center gap-2" style="display: flex; align-items: center; gap: 0.5rem;">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0"
                          style="width: 10px; height: 10px; border-radius: 9999px; background-color: #f59e0b; flex-shrink: 0; display: inline-block;"></span>
                    <h4 class="text-xs font-bold text-neutral-900 font-sans uppercase tracking-wider"
                        style="font-size: 0.75rem; font-weight: 700; color: #18181b; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">
                        Tindakan Koreksi (Sementara)
                    </h4>
                </div>
            </div>

            {{-- Deskripsi Tindakan Koreksi --}}
            <p class="text-xs text-neutral-800 leading-relaxed min-h-[48px] whitespace-pre-line"
               style="font-size: 0.75rem; color: #27272a; line-height: 1.5; min-height: 48px; margin: 0; white-space: pre-line;">
                {{ $ba->koreksi_deskripsi ?: 'Tidak ada rincian tindakan koreksi sementara.' }}
            </p>
        </div>

        {{-- Footer: PIC & Waktu --}}
        <div class="pt-2.5 border-t border-amber-200/80 flex items-center justify-between text-[11px] text-neutral-600 flex-wrap gap-2 mt-2"
             style="padding-top: 0.625rem; border-top: 1px solid rgba(253, 230, 138, 0.8); display: flex; align-items: center; justify-content: space-between; font-size: 11px; color: #52525b; margin-top: 0.5rem;">
            <span>PIC Pelaksana (Operator/SPV): <strong class="text-neutral-800" style="color: #27272a; font-weight: 600;">{{ $ba->koreksi_pic ?: '-' }}</strong></span>
            <span>Batas Waktu Pelaksanaan: <strong class="text-neutral-800" style="color: #27272a; font-weight: 600;">{{ $ba->koreksi_waktu ?: '-' }}</strong></span>
        </div>
    </div>

    {{-- Kartu B: Tindakan Korektif Permanen (Corrective Action) - Hijau/Brand --}}
    <div class="border border-brand/35 bg-brand-tint/20 rounded-md p-4 space-y-3 flex flex-col justify-between"
         style="border: 1px solid rgba(11, 120, 64, 0.35); background-color: rgba(232, 245, 236, 0.3); border-radius: 8px; padding: 1rem; display: flex; flex-direction: column; justify-content: space-between; box-sizing: border-box;">
        <div class="space-y-2.5" style="display: flex; flex-direction: column; gap: 0.625rem;">
            {{-- Header Kartu Korektif --}}
            <div class="flex items-center justify-between pb-2 border-b border-brand/25 flex-wrap gap-1.5"
                 style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 0.5rem; border-bottom: 1px solid rgba(11, 120, 64, 0.25); gap: 0.375rem;">
                <div class="flex items-center gap-2" style="display: flex; align-items: center; gap: 0.5rem;">
                    <span class="w-2.5 h-2.5 rounded-full bg-brand shrink-0"
                          style="width: 10px; height: 10px; border-radius: 9999px; background-color: #0B7840; flex-shrink: 0; display: inline-block;"></span>
                    <h4 class="text-xs font-bold text-neutral-900 font-sans uppercase tracking-wider"
                        style="font-size: 0.75rem; font-weight: 700; color: #18181b; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">
                        Tindakan Korektif (Akar Masalah)
                    </h4>
                </div>
            </div>

            {{-- Deskripsi Tindakan Korektif --}}
            <p class="text-xs text-neutral-800 leading-relaxed min-h-[48px] whitespace-pre-line"
               style="font-size: 0.75rem; color: #27272a; line-height: 1.5; min-height: 48px; margin: 0; white-space: pre-line;">
                {{ $ba->korektif_deskripsi ?: 'Tidak ada rincian tindakan korektif permanen.' }}
            </p>
        </div>

        {{-- Footer: PIC & Waktu --}}
        <div class="pt-2.5 border-t border-brand/25 flex items-center justify-between text-[11px] text-neutral-600 flex-wrap gap-2 mt-2"
             style="padding-top: 0.625rem; border-top: 1px solid rgba(11, 120, 64, 0.25); display: flex; align-items: center; justify-content: space-between; font-size: 11px; color: #52525b; margin-top: 0.5rem;">
            <span>PIC Penanggung Jawab: <strong class="text-neutral-800" style="color: #27272a; font-weight: 600;">{{ $ba->korektif_pic ?: '-' }}</strong></span>
            <span>Target Tanggal Selesai: <strong class="text-neutral-800" style="color: #27272a; font-weight: 600;">{{ $ba->korektif_waktu ?: '-' }}</strong></span>
        </div>
    </div>
</div>
@endif
