@props([
    'incident' => null,
    'record' => null,
])

@php
    $ba = $incident ?? $record ?? (isset($getRecord) ? $getRecord() : null);
    $video = $ba?->video;
    $gdriveId = null;

    if ($video && !empty($video->video_external_link)) {
        if (preg_match('/(?:drive\.google\.com\/(?:file\/d\/|open\?id=)|docs\.google\.com\/file\/d\/)([a-zA-Z0-9_-]+)/', $video->video_external_link, $matches)) {
            $gdriveId = $matches[1];
        }
    }
@endphp

@if($video && ($video->video_file_url || $video->video_external_link))
    <div {{ $attributes->merge(['class' => 'space-y-3 w-full']) }} style="width: 100%; display: flex; flex-direction: column; gap: 0.75rem;">
        {{-- Video Meta Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-neutral-200"
             style="display: flex; flex-direction: row; align-items: center; justify-content: space-between; gap: 0.5rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e4e4e7;">
            <div class="flex items-center gap-2" style="display: flex; align-items: center; gap: 0.5rem;">
                <span class="w-2 h-2 rounded-full bg-brand"
                      style="width: 8px; height: 8px; border-radius: 9999px; background-color: #0B7840; display: inline-block;"></span>
                <h4 class="text-xs font-semibold text-neutral-900" style="font-size: 0.75rem; font-weight: 600; color: #18181b; margin: 0;">
                    {{ $video->title ?: 'Video Penanganan & Bukti' }}
                </h4>
            </div>

            @if($video->video_external_link)
                <a href="{{ $video->video_external_link }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="inline-flex items-center gap-1.5 px-3 py-1 bg-white border border-neutral-300 hover:border-brand hover:text-brand rounded-md text-xs font-medium text-neutral-700 transition-colors shadow-2xs"
                   style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 4px 10px; background-color: #ffffff; border: 1px solid #d4d4d8; border-radius: 6px; font-size: 0.75rem; font-weight: 500; color: #27272a; text-decoration: none;">
                    <span>Buka Video Eksternal</span>
                    <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </a>
            @endif
        </div>

        {{-- Case 1: Local HTML5 Video Player --}}
        @if($video->video_file_url)
            <div class="aspect-video bg-black rounded-md overflow-hidden max-w-2xl mx-auto shadow-sm"
                 style="position: relative; width: 100%; max-width: 42rem; margin: 0 auto; aspect-ratio: 16/9; background-color: #000000; border-radius: 8px; overflow: hidden;">
                <video controls class="w-full h-full object-contain" style="width: 100%; height: 100%; object-fit: contain;">
                    <source src="{{ asset($video->video_file_url) }}" type="video/mp4">
                    Browser Anda tidak mendukung pemutar video HTML5.
                </video>
            </div>
        {{-- Case 2: Google Drive Preview Embed + Link --}}
        @elseif($gdriveId)
            <div class="space-y-3" style="display: flex; flex-direction: column; gap: 0.75rem;">
                <div class="aspect-video bg-neutral-900 rounded-md overflow-hidden max-w-2xl mx-auto shadow-sm"
                     style="position: relative; width: 100%; max-width: 42rem; margin: 0 auto; aspect-ratio: 16/9; background-color: #18181b; border-radius: 8px; overflow: hidden;">
                    <iframe src="https://drive.google.com/file/d/{{ $gdriveId }}/preview"
                            class="w-full h-full border-0"
                            style="width: 100%; height: 100%; border: 0;"
                            allow="autoplay"></iframe>
                </div>
            </div>
        {{-- Case 3: Other External Link Card --}}
        @elseif($video->video_external_link)
            <div class="p-3.5 bg-neutral-50 border border-neutral-200 rounded-md flex items-center gap-3"
                 style="padding: 0.875rem; background-color: #f4f4f5; border: 1px solid #e4e4e7; border-radius: 8px; display: flex; align-items: center; gap: 0.75rem;">
                <div class="w-8 h-8 rounded bg-white border border-neutral-200 flex items-center justify-center text-neutral-600 shrink-0"
                     style="width: 2rem; height: 2rem; border-radius: 6px; background-color: #ffffff; border: 1px solid #e4e4e7; display: flex; align-items: center; justify-content: center; color: #52525b; flex-shrink: 0;">
                    <svg style="width: 1rem; height: 1rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1" style="min-width: 0; flex: 1;">
                    <span class="text-[11px] text-neutral-500 font-sans block" style="font-size: 11px; color: #71717a; display: block;">Tautan Video Eksternal:</span>
                    <a href="{{ $video->video_external_link }}" target="_blank"
                       class="text-xs font-mono text-brand truncate block hover:underline"
                       style="font-size: 0.75rem; font-family: monospace; color: #0B7840; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block;">
                        {{ $video->video_external_link }}
                    </a>
                </div>
            </div>
        @endif
    </div>
@else
    {{-- Fallback jika video belum dilampirkan --}}
    <div class="text-center py-6 text-xs text-neutral-500 bg-neutral-50 rounded-md border border-dashed border-neutral-300 w-full"
         style="text-align: center; padding: 1.5rem 1rem; font-size: 0.75rem; color: #71717a; background-color: #fafafa; border: 1px dashed #d4d4d8; border-radius: 8px; width: 100%;">
        Tidak ada berkas atau tautan video yang dilampirkan pada pelaporan ini.
    </div>
@endif
