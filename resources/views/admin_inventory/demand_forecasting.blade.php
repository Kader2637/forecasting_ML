@extends('layouts.admin_inventory.app')

@section('title', 'Demand Forecasting')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2"><i class="bi bi-graph-up"></i> Demand Forecasting</h1>
            <p class="text-gray-600">Pilih produk untuk melihat detail forecast data, perbandingan actual vs predicted, dan metrik akurasi</p>
        </div>

        <!-- Product Selector -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="product-select" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="bi bi-box-seam"></i> Pilih Produk
                    </label>
                    <select id="product-select" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="">-- Pilih Produk --</option>
                        @forelse($forecastData as $item)
                            <option value="{{ $item['produk'] }}">
                                {{ $item['code_item'] }} - {{ $item['name_item'] }}
                            </option>
                        @empty
                            <option value="" disabled>Tidak ada data produk</option>
                        @endforelse
                    </select>
                </div>
                <div class="flex items-end">
                    <button id="load-btn" type="button" disabled class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:bg-gray-400 transition-colors">
                        <i class="bi bi-arrow-repeat"></i> Muat Data
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div id="loading-state" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4 mb-8 text-center">
            <div class="flex items-center justify-center gap-2">
                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600"></div>
                <span class="text-blue-700">Memuat data...</span>
            </div>
        </div>

        <!-- Error State -->
        <div id="error-state" class="hidden bg-red-50 border border-red-200 rounded-lg p-4 mb-8 text-red-700"></div>

        <!-- Detail Section (hidden by default) -->
        <div id="detail-section" class="hidden">
            <!-- Metrics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">MAE</p>
                            <p id="mae-value" class="text-3xl font-bold text-blue-600">-</p>
                        </div>
                        <div class="text-4xl text-blue-200"><i class="bi bi-graph-up"></i></div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">RMSE</p>
                            <p id="rmse-value" class="text-3xl font-bold text-green-600">-</p>
                        </div>
                        <div class="text-4xl text-green-200"><i class="bi bi-bar-chart-line"></i></div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">MAPE (%)</p>
                            <p id="mape-value" class="text-3xl font-bold text-purple-600">-</p>
                        </div>
                        <div class="text-4xl text-purple-200"><i class="bi bi-calendar"></i></div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">ARIMA Order</p>
                            <p id="arima-order-value" class="text-3xl font-bold text-orange-600 font-mono">-</p>
                        </div>
                        <div class="text-4xl text-orange-200"><i class="bi bi-diagram-3"></i></div>
                    </div>
                </div>
            </div>

            <!-- Forecast Comparison Chart -->
            <div class="bg-white rounded-lg shadow p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="bi bi-graph-up"></i> Perbandingan Data Forecasting
                </h3>
                <div class="overflow-x-auto">
                    <canvas id="forecast-chart" style="height: 300px;"></canvas>
                </div>
            </div>


        </div>

        <!-- Empty State -->
        <div id="empty-state" class="bg-gray-50 rounded-lg border-2 border-dashed border-gray-300 p-12 text-center">
            <div class="text-gray-400 text-5xl mb-4"><i class="bi bi-inbox"></i></div>
            <p class="text-gray-600 text-lg">Pilih produk dari dropdown untuk melihat detail forecast data</p>
        </div>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>

<script>
    let chartInstance = null;
    const productSelect = document.getElementById('product-select');
    const loadBtn = document.getElementById('load-btn');
    const detailSection = document.getElementById('detail-section');
    const emptyState = document.getElementById('empty-state');
    const loadingState = document.getElementById('loading-state');
    const errorState = document.getElementById('error-state');
    // Enable/disable load button when product is selected
    productSelect.addEventListener('change', function() {
        loadBtn.disabled = this.value === '';
    });

    // Load detail data when button is clicked
    loadBtn.addEventListener('click', async function() {
        const produk = productSelect.value;
        if (!produk) return;

        loadingState.classList.remove('hidden');
        errorState.classList.add('hidden');
        detailSection.classList.add('hidden');
        emptyState.classList.add('hidden');

        try {
            const response = await fetch(`/admin/inventory/forecasting/demand-detail/${encodeURIComponent(produk)}`);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Gagal memuat data');
            }

            loadingState.classList.add('hidden');
            displayDetailData(data);
        } catch (error) {
            loadingState.classList.add('hidden');
            errorState.classList.remove('hidden');
            errorState.textContent = '❌ ' + error.message;
        }
    });

    function displayDetailData(data) {
        const summary = data.summary;
        const chartData = data.chart_data;
        const tableData = data.table_data;

        // Update metrics
        document.getElementById('mae-value').textContent = Number(summary.mae).toFixed(4);
        document.getElementById('rmse-value').textContent = Number(summary.rmse).toFixed(4);
        document.getElementById('mape-value').textContent = Number(summary.mape_percentage).toFixed(2);
        document.getElementById('arima-order-value').textContent = summary.arima_order;

        // Prepare chart data
        const labels = [];
        const trainingValues = [];
        const actualValues = [];
        const predictedValues = [];

        // Add training data
        if (chartData.training && chartData.training.length > 0) {
            chartData.training.forEach(d => {
                labels.push(d.date);
                trainingValues.push(d.value);
                actualValues.push(null);
                predictedValues.push(null);
            });
        }

        // Add actual & predicted data
        if (chartData.actual && chartData.actual.length > 0) {
            const startIdx = trainingValues.length;
            chartData.actual.forEach((d, idx) => {
                if (idx >= startIdx) labels.push(d.date);
                else labels[startIdx + idx] = d.date;
                
                if (idx >= startIdx) {
                    trainingValues.push(null);
                    actualValues.push(d.actual);
                    predictedValues.push(d.predicted);
                } else {
                    trainingValues[startIdx + idx] = null;
                    actualValues[startIdx + idx] = d.actual;
                    predictedValues[startIdx + idx] = d.predicted;
                }
            });
        }

        // Render chart
        const ctx = document.getElementById('forecast-chart').getContext('2d');
        if (chartInstance) {
            chartInstance.destroy();
        }

        chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Training Data',
                        data: trainingValues,
                        borderColor: '#9ca3af',
                        backgroundColor: 'rgba(156, 163, 175, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                    },
                    {
                        label: 'Actual Sales',
                        data: actualValues,
                        borderColor: '#f97316',
                        backgroundColor: 'rgba(249, 115, 22, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: false,
                    },
                    {
                        label: 'Predicted Sales',
                        data: predictedValues,
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        tension: 0.4,
                        fill: false,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Sales'
                        }
                    }
                }
            }
        });

        // Show detail section
        detailSection.classList.remove('hidden');
        emptyState.classList.add('hidden');
    }
</script>
@endsection

