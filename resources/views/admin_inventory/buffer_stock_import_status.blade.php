@extends('layouts.admin_inventory.app')

@section('title', 'Import Status - Buffer Stock CSV')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">📊 Status Import Buffer Stock dari CSV</h1>
            <p class="text-lg text-gray-600">Analisis data dari file master_items_raw_material.csv</p>
        </div>

        <!-- Data Source Info -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-md bg-purple-500 text-white">
                    📁
                </div>
                <div>
                    <p class="text-sm text-gray-600">File CSV Path</p>
                    <p class="text-lg font-semibold text-gray-900 font-mono text-sm">{{ $csvPath }}</p>
                </div>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Items</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $summary['total_materials'] }}</p>
                    </div>
                    <div class="text-4xl">📦</div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Inventory Value</p>
                        <p class="text-2xl font-bold text-purple-900">Rp {{ number_format($summary['total_inventory_value'], 0) }}</p>
                    </div>
                    <div class="text-4xl">💰</div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Items Below Buffer</p>
                        <p class="text-3xl font-bold text-red-600">{{ $summary['items_below_buffer'] }}</p>
                    </div>
                    <div class="text-4xl">⚠️</div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Avg Daily Demand</p>
                        <p class="text-2xl font-bold text-blue-900">{{ $summary['avg_daily_demand'] }}</p>
                    </div>
                    <div class="text-4xl">📈</div>
                </div>
            </div>
        </div>

        <!-- Calculation Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg shadow p-6 border border-green-200">
                <h3 class="font-bold text-green-900 mb-4">Buffer Stock</h3>
                <div class="space-y-2">
                    <p class="text-sm text-green-700">Current: <span class="font-bold">{{ $summary['avg_buffer_stock'] }}</span></p>
                    <p class="text-sm text-green-700">Proposed: <span class="font-bold">{{ $summary['avg_buffer_stock_proposed'] }}</span></p>
                    <p class="text-sm text-green-700">Mean Variance: <span class="font-bold {{ $summary['buffer_stock_variance_mean'] < 0 ? 'text-red-600' : 'text-green-600' }}">
                        {{ $summary['buffer_stock_variance_mean'] > 0 ? '+' : '' }}{{ $summary['buffer_stock_variance_mean'] }}
                    </span></p>
                </div>
            </div>

            <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg shadow p-6 border border-orange-200">
                <h3 class="font-bold text-orange-900 mb-4">Reorder Point</h3>
                <div class="space-y-2">
                    <p class="text-sm text-orange-700">Current: <span class="font-bold">{{ $summary['avg_reorder_point'] }}</span></p>
                    <p class="text-sm text-orange-700">Proposed: <span class="font-bold">{{ $summary['avg_reorder_point_proposed'] }}</span></p>
                    <p class="text-sm text-orange-700">Mean Variance: <span class="font-bold {{ $summary['reorder_point_variance_mean'] < 0 ? 'text-red-600' : 'text-green-600' }}">
                        {{ $summary['reorder_point_variance_mean'] > 0 ? '+' : '' }}{{ $summary['reorder_point_variance_mean'] }}
                    </span></p>
                </div>
            </div>

            <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-lg shadow p-6 border border-indigo-200">
                <h3 class="font-bold text-indigo-900 mb-4">Variance Analysis</h3>
                <div class="space-y-2">
                    <p class="text-sm text-indigo-700">Below Expected: <span class="font-bold text-red-600">{{ $summary['items_variance_negative'] }}</span></p>
                    <p class="text-sm text-indigo-700">Above Expected: <span class="font-bold text-green-600">{{ $summary['items_variance_positive'] }}</span></p>
                    <p class="text-sm text-indigo-700">Balanced: <span class="font-bold">{{ $summary['total_materials'] - $summary['items_variance_negative'] - $summary['items_variance_positive'] }}</span></p>
                </div>
            </div>
        </div>

        <!-- Critical Items Table -->
        @if($criticalItems->count() > 0)
        <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
            <div class="bg-red-600 text-white px-6 py-4">
                <h3 class="font-bold text-lg">🔴 Items Requiring Attention (Variance > 10)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Material Name</th>
                            <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Current Stock</th>
                            <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Buffer Stock</th>
                            <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Proposed</th>
                            <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Variance</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($criticalItems as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-semibold text-gray-900">{{ $item['material_name'] }}</td>
                            <td class="px-6 py-4 text-right text-gray-700">{{ number_format($item['current_stock'], 0) }}</td>
                            <td class="px-6 py-4 text-right text-gray-700">{{ number_format($item['buffer_stock'], 2) }}</td>
                            <td class="px-6 py-4 text-right text-gray-700">{{ number_format($item['buffer_stock_proposed'], 2) }}</td>
                            <td class="px-6 py-4 text-right font-bold {{ $item['buffer_stock_variance'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $item['buffer_stock_variance'] > 0 ? '+' : '' }}{{ number_format($item['buffer_stock_variance'], 2) }}
                            </td>
                            <td class="px-6 py-4">
                                @if($item['buffer_stock_variance'] < -10)
                                    <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium">Need Increase</span>
                                @else
                                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">Can Reduce</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Actions -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h3 class="font-bold text-lg text-gray-900 mb-4">🔄 Actions</h3>
            <div class="flex gap-4 flex-wrap">
                <button onclick="syncFromCSV()" class="px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-semibold flex items-center gap-2">
                    <span>📥</span> Sync to Database
                </button>
                <a href="{{ route('admin.inventory.buffer-stock.raw-materials') }}" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold flex items-center gap-2">
                    <span>📊</span> View Buffer Stock Dashboard
                </a>
                <a href="{{ route('admin.inventory.buffer-stock.export-analysis') }}" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold flex items-center gap-2">
                    <span>📤</span> Export as CSV
                </a>
            </div>
        </div>

        <!-- Information Section -->
        <div class="bg-blue-50 rounded-lg p-6 border border-blue-200">
            <h3 class="text-lg font-semibold text-blue-900 mb-4">📚 About This Analysis</h3>
            <div class="space-y-3 text-sm text-blue-800">
                <p>
                    <strong>Buffer Stock Calculation:</strong> (Current Stock / Lead Time Days) × Safety Factor (25%)
                </p>
                <p>
                    <strong>Reorder Point Calculation:</strong> (Lead Time Days × Average Daily Demand) + Buffer Stock
                </p>
                <p>
                    <strong>Variance:</strong> Perbedaan antara nilai yang direkomendasikan dengan nilai yang saat ini disimpan di database.
                    Nilai positif berarti buffer stock dapat dikurangi, nilai negatif berarti buffer stock perlu ditingkatkan.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function syncFromCSV() {
    if (!confirm('Sinkronisasi semua buffer stock dari CSV ke database? Ini akan menimpa nilai yang ada.')) return;
    
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Syncing...';
    
    fetch('{{ url("/admin/inventory/buffer-stock/sync-from-csv") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showNotification(`<i class="bi bi-check-lg"></i> ${data.message}`, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(`<i class="bi bi-x-lg"></i> Error: ${data.message}`, 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Sync to Database';
        }
    })
    .catch(err => {
        showNotification(`<i class="bi bi-x-lg"></i> Error: ${err.message}`, 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Sync to Database';
    });
}
</script>
@endsection
