@props(['field'])
{{-- Petunjuk format singkat di bawah field; teks dari BaIncident::FIELD_GUIDES. --}}
<p class="text-xs text-neutral-500 leading-snug font-sans">{{ \App\Models\BaIncident::FIELD_GUIDES[$field]['helper'] }}</p>
