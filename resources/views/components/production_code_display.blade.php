<!-- 
Production Code Display Component
Path: resources/views/components/production_code_display.blade.php

Usage di blade:
@include('components.production_code_display', ['productionCode' => $finishedGoodsIn->production_code])

atau langsung di template:
<div class="text-lg font-semibold text-indigo-600">
    Kode Produksi: {{ $finishedGoodsIn->production_code ?? 'N/A' }}
</div>
-->

<div class="inline-flex items-center gap-2 px-3 py-2 bg-indigo-50 border border-indigo-200 rounded-lg">
    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
    <span class="text-sm font-medium text-slate-600">Kode Produksi:</span>
    <span class="text-lg font-bold text-indigo-600">{{ $productionCode ?? 'N/A' }}</span>
</div>
