<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TransactionSales;
use App\Models\TransactionPayment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransactionSalesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Clean up existing transactions & payments
        DB::table('transaction_payments')->delete();
        DB::table('transaction_sales_details')->delete();
        DB::table('transaction_sales')->delete();

        // 2. Ensure basic payment methods exist
        $paymentMethods = ['Transfer Bank', 'QRIS', 'Tunai'];
        foreach ($paymentMethods as $pm) {
            DB::table('master_payment_methods')->updateOrInsert(
                ['name_payment_method' => $pm],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Get created payment method IDs
        $paymentMethodIds = DB::table('master_payment_methods')->pluck('payment_method_id')->toArray();

        // 3. Get master items & customers
        $items = DB::table('master_items')->where('status_item', 'active')->get();
        $customers = DB::table('master_customers')->get();

        if ($items->isEmpty()) {
            echo "Warning: No active master items found. Seeding skipped.\n";
            return;
        }
        if ($customers->isEmpty()) {
            echo "Warning: No master customers found. Seeding skipped.\n";
            return;
        }

        // Map items by code_item for ARIMA mapping
        $itemsByCode = $items->keyBy('code_item');

        // 4. Generate Transactions based on ARIMA actual_sales details (to align chart actual curves)
        // Check if arima_forecast_details has data
        $arimaDetails = [];
        if (DB::getSchemaBuilder()->hasTable('arima_forecast_details')) {
            $arimaDetails = DB::table('arima_forecast_details')
                ->where('data_type', 'actual')
                ->orderBy('date', 'asc')
                ->get();
        }

        $txCount = 0;
        $detailCount = 0;
        $paymentCount = 0;

        // Group ARIMA records by date so we create consolidated daily orders
        $groupedArima = [];
        $arimaProducts = [];
        foreach ($arimaDetails as $row) {
            $dateStr = substr($row->date, 0, 10);
            $groupedArima[$dateStr][] = $row;
            $arimaProducts[$row->produk] = true;
        }

        $customerArray = $customers->toArray();
        $couriers = ['JNE', 'TIKI', 'POS', 'Sicepat', 'J&T'];
        $services = ['REG', 'YES', 'OKE', 'GOKIL', 'EZ'];
        $statuses = ['pending', 'processing', 'shipped', 'delivered'];

        // Keep track of transaction dates we already seeded so we don't duplicate too heavily
        $seededDates = [];

        // 4a. Seed ARIMA Consolidated Transactions
        foreach ($groupedArima as $dateStr => $rows) {
            $validDetails = [];
            $subtotal = 0.0;

            foreach ($rows as $row) {
                $sku = $row->produk;
                $qty = (float) $row->actual_sales;

                if (!isset($itemsByCode[$sku])) {
                    continue;
                }

                $item = $itemsByCode[$sku];
                $sellPrice = (float) ($item->sellingprice_item ?? 50000);
                $costPrice = (float) ($item->costprice_item ?? ($sellPrice * 0.7));
                $rowSubtotal = $sellPrice * $qty;

                $validDetails[] = [
                    'item_id' => $item->item_id,
                    'qty' => $qty,
                    'costprice' => $costPrice,
                    'sell_price' => $sellPrice,
                    'subtotal' => $rowSubtotal,
                    'discount_amount' => 0.0,
                    'discount_percentage' => 0.0,
                    'total_amount' => $rowSubtotal,
                ];

                $subtotal += $rowSubtotal;
            }

            if (empty($validDetails)) {
                continue;
            }

            $cust = $customerArray[array_rand($customerArray)];
            $shippingCost = rand(10, 25) * 1000;
            $grandTotal = $subtotal + $shippingCost;

            // Setup a random status and payment distribution
            // 65% Lunas (paid), 15% Sebagian (partial), 20% Pending/Belum Bayar (pending)
            $payStatus = 'belum-bayar';
            $shipStatus = 'pending';
            
            $randPct = rand(1, 100);
            if ($randPct <= 65) {
                $payStatus = 'paid';
                $shipStatus = 'delivered';
            } elseif ($randPct <= 80) {
                $payStatus = 'partial';
                $shipStatus = 'shipped';
            } else {
                $payStatus = 'pending';
                $shipStatus = 'processing';
            }

            $txNumber = 'TXN-FC-' . str_replace('-', '', $dateStr) . '-' . str_pad(rand(1, 99), 2, '0', STR_PAD_LEFT);
            $txDate = Carbon::parse($dateStr . ' ' . rand(9, 17) . ':' . rand(10, 59) . ':' . rand(10, 59));

            $transaction = TransactionSales::create([
                'number' => $txNumber,
                'branch_id' => 1,
                'user_id' => 1,
                'customer_id' => $cust->customer_id,
                'sales_type_id' => 1,
                'expedition_id' => rand(1, 3),
                'date' => $txDate,
                'subtotal' => $subtotal,
                'discount_amount' => 0.0,
                'discount_percentage' => 0.0,
                'total_amount' => $subtotal,
                'shipping_cost' => $shippingCost,
                'shipping_courier' => $couriers[array_rand($couriers)],
                'shipping_service' => $services[array_rand($services)],
                'shipping_etd' => rand(1, 3) . ' hari',
                'tracking_number' => ($shipStatus === 'delivered' || $shipStatus === 'shipped') ? 'TRK' . rand(1000000000, 9999999999) : null,
                'shipping_status' => $shipStatus,
                'whatsapp' => $cust->phone_customer ?? '08123456789',
                'shipping_address' => $cust->address_customer ?? 'Alamat Ritel',
                'notes' => 'Transaksi Rantai Pasok Terintegrasi ARIMA'
            ]);

            $txCount++;
            $seededDates[$dateStr] = true;

            // Create Transaction Details
            foreach ($validDetails as $det) {
                $det['transaction_sales_id'] = $transaction->transaction_sales_id;
                $det['created_at'] = $txDate;
                $det['updated_at'] = $txDate;
                DB::table('transaction_sales_details')->insert($det);
                $detailCount++;
            }

            // Create Payments
            if ($payStatus === 'paid') {
                DB::table('transaction_payments')->insert([
                    'transaction_sales_id' => $transaction->transaction_sales_id,
                    'payment_method_id' => $paymentMethodIds[array_rand($paymentMethodIds)],
                    'amount' => $grandTotal,
                    'received_amount' => $grandTotal,
                    'change_amount' => 0.0,
                    'payment_type' => 'incoming',
                    'payment_status' => 'paid',
                    'payment_date' => $txDate,
                    'notes' => 'Pembayaran lunas terverifikasi otomatis.',
                    'created_at' => $txDate,
                    'updated_at' => $txDate,
                ]);
                $paymentCount++;
            } elseif ($payStatus === 'partial') {
                $paidAmt = round(($grandTotal * rand(40, 70)) / 100);
                DB::table('transaction_payments')->insert([
                    'transaction_sales_id' => $transaction->transaction_sales_id,
                    'payment_method_id' => $paymentMethodIds[array_rand($paymentMethodIds)],
                    'amount' => $paidAmt,
                    'received_amount' => $paidAmt,
                    'change_amount' => 0.0,
                    'payment_type' => 'incoming',
                    'payment_status' => 'paid',
                    'payment_date' => $txDate,
                    'notes' => 'Pembayaran uang muka sebagian.',
                    'created_at' => $txDate,
                    'updated_at' => $txDate,
                ]);
                $paymentCount++;
            }
        }

        // 4b. Seed Additional Standard Customers retail transactions
        for ($i = 0; $i < 30; $i++) {
            $cust = $customerArray[array_rand($customerArray)];
            $txDate = now()->subDays(rand(1, 90));
            $dateStr = $txDate->format('Y-m-d');

            if (isset($seededDates[$dateStr])) {
                continue;
            }

            $numItems = rand(1, 4);
            $validDetails = [];
            $subtotal = 0.0;

            for ($j = 0; $j < $numItems; $j++) {
                $item = $items->random();
                
                // Hindari menambahkan produk ARIMA ke transaksi retail acak agar grafik comparison actual vs predicted tetap sinkron dan paralel sempurna
                if (isset($arimaProducts[$item->code_item])) {
                    continue;
                }

                $qty = rand(1, 5);
                $sellPrice = (float) ($item->sellingprice_item ?? 50000);
                $costPrice = (float) ($item->costprice_item ?? ($sellPrice * 0.7));
                $rowSubtotal = $sellPrice * $qty;

                // Avoid duplicate items in same transaction
                $alreadyAdded = false;
                foreach ($validDetails as &$existingDet) {
                    if ($existingDet['item_id'] === $item->item_id) {
                        $existingDet['qty'] += $qty;
                        $existingDet['subtotal'] += $rowSubtotal;
                        $existingDet['total_amount'] += $rowSubtotal;
                        $alreadyAdded = true;
                        break;
                    }
                }

                if (!$alreadyAdded) {
                    $validDetails[] = [
                        'item_id' => $item->item_id,
                        'qty' => $qty,
                        'costprice' => $costPrice,
                        'sell_price' => $sellPrice,
                        'subtotal' => $rowSubtotal,
                        'discount_amount' => 0.0,
                        'discount_percentage' => 0.0,
                        'total_amount' => $rowSubtotal,
                    ];
                }
                $subtotal += $rowSubtotal;
            }

            $shippingCost = rand(10, 25) * 1000;
            $grandTotal = $subtotal + $shippingCost;

            $payStatus = 'belum-bayar';
            $shipStatus = 'pending';
            
            $randPct = rand(1, 100);
            if ($randPct <= 65) {
                $payStatus = 'paid';
                $shipStatus = 'delivered';
            } elseif ($randPct <= 80) {
                $payStatus = 'partial';
                $shipStatus = 'shipped';
            } else {
                $payStatus = 'pending';
                $shipStatus = 'processing';
            }

            $txNumber = 'TXN-RTL-' . $txDate->format('Ymd') . '-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT);

            $transaction = TransactionSales::create([
                'number' => $txNumber,
                'branch_id' => 1,
                'user_id' => 1,
                'customer_id' => $cust->customer_id,
                'sales_type_id' => 1,
                'expedition_id' => rand(1, 3),
                'date' => $txDate,
                'subtotal' => $subtotal,
                'discount_amount' => 0.0,
                'discount_percentage' => 0.0,
                'total_amount' => $subtotal,
                'shipping_cost' => $shippingCost,
                'shipping_courier' => $couriers[array_rand($couriers)],
                'shipping_service' => $services[array_rand($services)],
                'shipping_etd' => rand(1, 3) . ' hari',
                'tracking_number' => ($shipStatus === 'delivered' || $shipStatus === 'shipped') ? 'TRK' . rand(1000000000, 9999999999) : null,
                'shipping_status' => $shipStatus,
                'whatsapp' => $cust->phone_customer ?? '08123456789',
                'shipping_address' => $cust->address_customer ?? 'Alamat Ritel',
                'notes' => 'Transaksi retail eceran reguler.'
            ]);

            $txCount++;

            foreach ($validDetails as $det) {
                $det['transaction_sales_id'] = $transaction->transaction_sales_id;
                $det['created_at'] = $txDate;
                $det['updated_at'] = $txDate;
                DB::table('transaction_sales_details')->insert($det);
                $detailCount++;
            }

            // Create Payments
            if ($payStatus === 'paid') {
                DB::table('transaction_payments')->insert([
                    'transaction_sales_id' => $transaction->transaction_sales_id,
                    'payment_method_id' => $paymentMethodIds[array_rand($paymentMethodIds)],
                    'amount' => $grandTotal,
                    'received_amount' => $grandTotal,
                    'change_amount' => 0.0,
                    'payment_type' => 'incoming',
                    'payment_status' => 'paid',
                    'payment_date' => $txDate,
                    'notes' => 'Pembayaran lunas terverifikasi.',
                    'created_at' => $txDate,
                    'updated_at' => $txDate,
                ]);
                $paymentCount++;
            } elseif ($payStatus === 'partial') {
                $paidAmt = round(($grandTotal * rand(40, 70)) / 100);
                DB::table('transaction_payments')->insert([
                    'transaction_sales_id' => $transaction->transaction_sales_id,
                    'payment_method_id' => $paymentMethodIds[array_rand($paymentMethodIds)],
                    'amount' => $paidAmt,
                    'received_amount' => $paidAmt,
                    'change_amount' => 0.0,
                    'payment_type' => 'incoming',
                    'payment_status' => 'paid',
                    'payment_date' => $txDate,
                    'notes' => 'Pembayaran uang muka.',
                    'created_at' => $txDate,
                    'updated_at' => $txDate,
                ]);
                $paymentCount++;
            }
        }

        echo "Transaction sales and payments seeded successfully!\n";
        echo " - Created {$txCount} consolidated daily transaction sales records.\n";
        echo " - Created {$detailCount} transaction sales details.\n";
        echo " - Created {$paymentCount} payment transaction receipts (65% Lunas, 15% Sebagian, 20% Pending).\n";
    }
}
