@extends('layouts.admin_inventory.app')

@section('title', 'Detail Transaksi #' . $details['number'])

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">
    <div class="flex items-center gap-4 mb-2">
        <a href="{{ route('admin.inventory.transaction-history') }}" class="w-10 h-10 rounded-lg bg-white border border-slate-200 text-slate-500 flex items-center justify-center hover:bg-slate-50 hover:text-slate-700 transition-colors shadow-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
                <i class="bi bi-receipt text-indigo-600"></i> Detail Transaksi
                <span class="font-mono text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded text-sm">#{{ $details['number'] }}</span>
            </h1>
            <p class="text-sm text-slate-500 mt-1">Tanggal transaksi: {{ $details['date'] }}</p>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 md:p-8">
        
        <!-- Top Overview Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-indigo-50/50 rounded-2xl p-5 border border-indigo-100/50">
                <h4 class="text-xs font-bold text-indigo-800 uppercase tracking-wider mb-2 flex items-center gap-1">
                    <i class="bi bi-person-fill"></i> Info Pelanggan
                </h4>
                <div class="font-semibold text-slate-900 text-lg">{{ $details['customer_name'] }}</div>
                <div class="text-sm text-slate-600 mt-1">WA: {{ $details['customer_whatsapp'] }}</div>
            </div>

            <div class="bg-sky-50/50 rounded-2xl p-5 border border-sky-100/50">
                <h4 class="text-xs font-bold text-sky-800 uppercase tracking-wider mb-2 flex items-center gap-1">
                    <i class="bi bi-truck"></i> Pengiriman
                </h4>
                <div class="text-sm font-semibold text-slate-800">Ekspedisi: {{ $details['shipping_courier'] }} ({{ $details['shipping_service'] }})</div>
                <div class="text-sm text-slate-600 mt-1">Status Kirim: {{ $details['shipping_status'] }}</div>
                <div class="text-sm text-slate-600 mt-1">Alamat: {{ $details['shipping_address'] }}</div>
            </div>

            <div class="bg-emerald-50/50 rounded-2xl p-5 border border-emerald-100/50">
                <h4 class="text-xs font-bold text-emerald-800 uppercase tracking-wider mb-2 flex items-center gap-1">
                    <i class="bi bi-info-circle-fill"></i> Status
                </h4>
                <div class="mb-2">
                    @php
                        $paymentStatus = strtolower($details['payment_status']);
                        $badgeClass = 'bg-yellow-100 text-yellow-800';
                        if ($paymentStatus === 'lunas' || $paymentStatus === 'paid') {
                            $badgeClass = 'bg-green-100 text-green-800';
                        } elseif ($paymentStatus === 'sebagian') {
                            $badgeClass = 'bg-blue-100 text-blue-800';
                        }
                    @endphp
                    <span class="inline-flex text-xs font-bold px-2.5 py-1 rounded-full {{ $badgeClass }}">
                        {{ strtoupper($paymentStatus) }}
                    </span>
                </div>
                <div class="text-sm font-semibold text-slate-800">Status Order: {{ $details['overall_status_label'] }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Item List -->
            <div>
                <h4 class="font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2 flex items-center gap-2">
                    <i class="bi bi-box-seam text-slate-400"></i> Item Transaksi
                </h4>
                <div class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-sm">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold text-slate-500 uppercase tracking-wider text-[10px]">Produk</th>
                                <th class="px-4 py-3 text-center font-bold text-slate-500 uppercase tracking-wider text-[10px]">Qty</th>
                                <th class="px-4 py-3 text-right font-bold text-slate-500 uppercase tracking-wider text-[10px]">Harga</th>
                                <th class="px-4 py-3 text-right font-bold text-slate-500 uppercase tracking-wider text-[10px]">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($details['items'] as $item)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-4 py-3 text-left">
                                    <div class="font-bold text-slate-900">{{ $item['name_item'] }}</div>
                                    <div class="text-xs text-slate-400 font-mono">{{ $item['code_item'] }}</div>
                                </td>
                                <td class="px-4 py-3 text-center font-mono font-semibold text-slate-700">{{ $item['qty'] }}</td>
                                <td class="px-4 py-3 text-right font-mono text-slate-500">Rp {{ number_format($item['sell_price'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-slate-900">Rp {{ number_format($item['total_amount'], 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-4 text-slate-400">Tidak ada item dalam transaksi ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Financial Summary -->
                <div class="mt-4 bg-slate-50 rounded-2xl p-5 border border-slate-100">
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between items-center text-slate-600">
                            <span>Subtotal Item</span>
                            <span class="font-mono font-semibold">Rp {{ number_format($details['subtotal'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center text-rose-600">
                            <span>Diskon</span>
                            <span class="font-mono font-semibold">-Rp {{ number_format($details['discount'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center text-slate-600">
                            <span>Ongkos Kirim</span>
                            <span class="font-mono font-semibold">Rp {{ number_format($details['shipping_cost'], 0, ',', '.') }}</span>
                        </div>
                        <div class="pt-3 mt-3 border-t border-slate-200 flex justify-between items-center">
                            <span class="font-bold text-slate-800 text-base">Grand Total</span>
                            <span class="font-mono font-black text-indigo-700 text-lg">Rp {{ number_format($details['grand_total'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment History -->
            <div>
                <h4 class="font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2 flex items-center gap-2">
                    <i class="bi bi-wallet2 text-slate-400"></i> Riwayat Pembayaran
                </h4>
                <div class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-sm">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold text-slate-500 uppercase tracking-wider text-[10px]">Waktu</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-500 uppercase tracking-wider text-[10px]">Metode</th>
                                <th class="px-4 py-3 text-right font-bold text-slate-500 uppercase tracking-wider text-[10px]">Nominal</th>
                                <th class="px-4 py-3 text-center font-bold text-slate-500 uppercase tracking-wider text-[10px]">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($details['payments'] as $payment)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-3 py-3 text-slate-600 text-xs">{{ $payment['payment_date'] }}</td>
                                <td class="px-3 py-3 font-medium text-slate-800 text-xs">{{ $payment['payment_method'] }}</td>
                                <td class="px-3 py-3 text-right font-mono font-bold text-slate-950">Rp {{ number_format($payment['amount'], 0, ',', '.') }}</td>
                                <td class="px-3 py-3 text-center">
                                    @if(strtolower($payment['payment_status']) === 'paid' || strtolower($payment['payment_status']) === 'lunas')
                                        <span class="px-2 py-0.5 rounded bg-green-100 text-green-800 font-bold text-[10px] uppercase">Lunas</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded bg-yellow-100 text-yellow-800 font-bold text-[10px] uppercase">{{ $payment['payment_status_label'] }}</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-6 text-slate-400">Tidak ada riwayat pembayaran resmi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
