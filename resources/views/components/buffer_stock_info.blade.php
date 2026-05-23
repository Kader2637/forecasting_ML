@php
// Inline CSS untuk timer animasi
$styles = <<<'CSS'
<style>
    .buffer-stock-sync-badge::after {
        content: '';
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: .5;
        }
    }
    .buffer-stock-loading {
        background: #f3f4f6;
        animation: shimmer 2s infinite;
    }
    @keyframes shimmer {
        0% {
            background-position: -1000px 0;
        }
        100% {
            background-position: 1000px 0;
        }
    }
</style>
CSS;
echo $styles;
@endphp

<!-- Buffer Stock Info Modal -->
<div id="bufferStockInfoModal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full max-h-screen overflow-y-auto">
        <div class="bg-blue-100 border-b border-blue-300 px-6 py-4 sticky top-0">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-blue-900">Informasi Buffer Stock</h2>
                <button type="button" onclick="closeBufferStockInfoModal()" class="text-blue-700 hover:text-blue-900">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div class="p-6 text-slate-800 space-y-4">
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4">
                <p class="text-sm font-medium text-blue-900">Formula Perhitungan Buffer Stock:</p>
                <p class="text-xs text-blue-800 mt-2 font-mono">
                    (Max Stok Keluar Harian × Max Lead Time) – (Avg Stok Keluar Harian × Avg Lead Time)
                </p>
            </div>
            
            <div>
                <p class="text-sm font-medium text-slate-700">Komponen:</p>
                <ul class="text-sm text-slate-600 mt-2 space-y-1 list-disc list-inside">
                    <li><strong>Max Stok Keluar Harian:</strong> Persentil ke-95 dari pengeluaran stok harian</li>
                    <li><strong>Max Lead Time:</strong> 7 hari (worst case / waktu terlama pengiriman bahan baku)</li>
                    <li><strong>Avg Stok Keluar Harian:</strong> Rata-rata pengeluaran stok harian</li>
                    <li><strong>Avg Lead Time:</strong> 5.4 hari (normal / rata-rata pengiriman bahan baku)</li>
                </ul>
            </div>

            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4">
                <p class="text-xs text-yellow-800">
                    <strong>Catatan:</strong> Nilai buffer stock dihitung dari data riwayat stok keluar aktual (Finished Goods Out). 
                    Perbarui berkala untuk memastikan keakuratan manajemen inventori Anda.
                </p>
            </div>
        </div>

        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex gap-3 justify-end sticky bottom-0">
            <button type="button" onclick="closeBufferStockInfoModal()" class="px-4 py-2 rounded border border-gray-300 text-slate-700 hover:bg-gray-50">
                Close
            </button>
        </div>
    </div>
</div>

<script>
function closeBufferStockInfoModal() {
    document.getElementById('bufferStockInfoModal').classList.add('hidden');
}

function showBufferStockInfoModal() {
    document.getElementById('bufferStockInfoModal').classList.remove('hidden');
}
</script>
