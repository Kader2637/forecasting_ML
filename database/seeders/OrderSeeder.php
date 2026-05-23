<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\MasterItem;
use Carbon\Carbon;
use Faker\Factory as Faker;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        
        // Get existing data
        $users = User::all();
        $products = MasterItem::where('status_item', 'active')->get();
        
        if ($users->isEmpty() || $products->isEmpty()) {
            $this->command->warn('No users or products found. Please run UserSeeder and ProductSeeder first.');
            return;
        }

        $orderCount = 0;
        $orderItemCount = 0;
        
        // Order status weights (realistic distribution)
        $statusWeights = [
            'pending' => 15,      // New orders
            'confirmed' => 10,    // Confirmed orders
            'processing' => 8,    // Being processed
            'shipped' => 12,      // Shipped orders
            'delivered' => 45,    // Most orders are delivered
            'cancelled' => 10     // Some cancelled orders
        ];

        // Create orders for the last 6 months
        $startDate = Carbon::now()->subMonths(6);
        $endDate = Carbon::now();

        // Generate 50-80 orders
        $totalOrders = rand(50, 80);
        
        for ($i = 0; $i < $totalOrders; $i++) {
            $user = $users->random();
            $status = $this->weightedRandom($statusWeights);
            
            // Determine order date based on status
            $orderDate = $this->getOrderDateByStatus($status, $startDate, $endDate);
            
            // Generate realistic order data
            $shippingCost = rand(10000, 25000); // 10k-25k shipping
            $orderItems = $this->generateOrderItems($products);
            $subtotal = collect($orderItems)->sum('total_price');
            $totalAmount = $subtotal + $shippingCost;
            
            $order = Order::create([
                'order_number' => $this->generateOrderNumber($orderDate),
                'user_id' => $user->user_id,
                'total_amount' => $totalAmount,
                'status' => $status,
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'customer_phone' => $user->phone ?: $faker->phoneNumber,
                'shipping_address' => $this->generateShippingAddress($faker, $user),
                'notes' => $this->generateOrderNotes($faker),
                'order_date' => $orderDate,
                'shipping_cost' => $shippingCost,
                'shipping_courier' => $this->getRandomCourier(),
                'shipping_service' => $this->getRandomService(),
                'shipping_etd' => $this->getShippingETD($status),
                'tracking_number' => $this->generateTrackingNumber($status),
                'shipping_city_id' => rand(1, 100),
                'shipping_province_id' => rand(1, 34),
                'shipping_status' => $this->getShippingStatus($status),
                'shipping_notes' => $this->getShippingNotes($status, $faker),
                'created_at' => $orderDate,
                'updated_at' => $this->getUpdatedDate($orderDate, $status),
            ]);

            // Create order items
            foreach ($orderItems as $itemData) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'master_item_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total_price' => $itemData['total_price'],
                    'item_name' => $itemData['item_name'],
                    'item_description' => $itemData['item_description'],
                    'created_at' => $orderDate,
                    'updated_at' => $orderDate,
                ]);
                $orderItemCount++;
            }

            $orderCount++;
        }

        // Generate some specific scenario orders
        $this->createSpecialOrders($users, $products, $faker);

        $this->command->info("Created {$orderCount} orders with {$orderItemCount} order items successfully!");
        
        // Show order statistics
        $this->showOrderStatistics();
    }

    /**
     * Generate weighted random selection
     */
    private function weightedRandom(array $weights): string
    {
        $totalWeight = array_sum($weights);
        $random = rand(1, $totalWeight);
        
        $currentWeight = 0;
        foreach ($weights as $value => $weight) {
            $currentWeight += $weight;
            if ($random <= $currentWeight) {
                return $value;
            }
        }
        
        return array_key_first($weights);
    }

    /**
     * Get order date based on status
     */
    private function getOrderDateByStatus(string $status, Carbon $start, Carbon $end): Carbon
    {
        switch ($status) {
            case 'pending':
            case 'confirmed':
                // Recent orders (last 7 days)
                return Carbon::now()->subDays(rand(0, 7));
            case 'processing':
                // Orders from last 3-10 days
                return Carbon::now()->subDays(rand(3, 10));
            case 'shipped':
                // Orders from last 5-15 days
                return Carbon::now()->subDays(rand(5, 15));
            case 'delivered':
                // Orders from last 7 days to 6 months
                return Carbon::now()->subDays(rand(7, 180));
            case 'cancelled':
                // Random cancelled orders
                return Carbon::now()->subDays(rand(1, 60));
            default:
                return $start->copy()->addDays(rand(0, $start->diffInDays($end)));
        }
    }

    /**
     * Generate order items for an order
     */
    private function generateOrderItems($products): array
    {
        $itemCount = rand(1, 4); // 1-4 items per order
        $selectedProducts = $products->random($itemCount);
        $orderItems = [];

        foreach ($selectedProducts as $product) {
            $quantity = rand(1, 3);
            $unitPrice = $product->sellingprice_item ?? ($product->costprice_item * 1.5);
            $totalPrice = $unitPrice * $quantity;

            $orderItems[] = [
                'product_id' => $product->item_id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'item_name' => $product->name_item,
                'item_description' => $product->description_item ?? '',
            ];
        }

        return $orderItems;
    }

    /**
     * Generate order number
     */
    private function generateOrderNumber(Carbon $date): string
    {
        return 'ORD-' . $date->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }

    /**
     * Generate shipping address
     */
    private function generateShippingAddress($faker, $user): string
    {
        if ($user->address) {
            return $user->address . ', ' . $user->city . ', ' . $user->province;
        }
        
        return $faker->streetAddress . ', ' . $faker->city . ', ' . $faker->state;
    }

    /**
     * Generate order notes
     */
    private function generateOrderNotes($faker): ?string
    {
        $notes = [
            'Mohon dikirim dengan bubble wrap',
            'Jangan dikirim saat hujan',
            'Telepon sebelum diantar',
            'Tinggal di rumah warna putih',
            'Kirim ke kantor jam 9-17',
            'Pack dengan rapi',
            null, null, null // Some orders have no notes
        ];
        
        return $notes[array_rand($notes)];
    }

    /**
     * Get random courier
     */
    private function getRandomCourier(): string
    {
        $couriers = ['JNE', 'JNT', 'SiCepat', 'AnterAja', 'Ninja Express', 'ID Express'];
        return $couriers[array_rand($couriers)];
    }

    /**
     * Get random shipping service
     */
    private function getRandomService(): string
    {
        $services = ['REG', 'OKE', 'YES', 'Express', 'Regular', 'Economy'];
        return $services[array_rand($services)];
    }

    /**
     * Get shipping ETD based on status
     */
    private function getShippingETD(string $status): ?string
    {
        if (in_array($status, ['cancelled', 'pending'])) {
            return null;
        }
        
        $etds = ['1-2 hari', '2-3 hari', '3-5 hari', '1 hari', '2-4 hari'];
        return $etds[array_rand($etds)];
    }

    /**
     * Generate tracking number based on status
     */
    private function generateTrackingNumber(string $status): ?string
    {
        if (in_array($status, ['pending', 'confirmed', 'cancelled'])) {
            return null;
        }
        
        return strtoupper(substr(uniqid(), 0, 2)) . rand(100000000000, 999999999999);
    }

    /**
     * Get shipping status based on order status
     */
    private function getShippingStatus(string $status): ?string
    {
        switch ($status) {
            case 'pending':
            case 'confirmed':
                return 'waiting';
            case 'processing':
                return 'preparing';
            case 'shipped':
                return 'shipped';
            case 'delivered':
                return 'delivered';
            case 'cancelled':
                return 'cancelled';
            default:
                return 'waiting';
        }
    }

    /**
     * Get shipping notes based on status
     */
    private function getShippingNotes(string $status, $faker): ?string
    {
        switch ($status) {
            case 'delivered':
                $notes = [
                    'Paket telah diterima oleh penerima',
                    'Diterima oleh keluarga',
                    'Paket sudah sampai dengan selamat',
                    'Delivered successfully'
                ];
                return $notes[array_rand($notes)];
            case 'shipped':
                $notes = [
                    'Paket dalam perjalanan',
                    'Sedang dikirim oleh kurir',
                    'On the way to destination'
                ];
                return $notes[array_rand($notes)];
            case 'cancelled':
                $notes = [
                    'Dibatalkan oleh customer',
                    'Stok habis',
                    'Customer tidak bisa dihubungi'
                ];
                return $notes[array_rand($notes)];
            default:
                return null;
        }
    }

    /**
     * Get updated date based on order date and status
     */
    private function getUpdatedDate(Carbon $orderDate, string $status): Carbon
    {
        switch ($status) {
            case 'pending':
                return $orderDate->copy()->addHours(rand(1, 24));
            case 'confirmed':
                return $orderDate->copy()->addHours(rand(2, 48));
            case 'processing':
                return $orderDate->copy()->addDays(rand(1, 3));
            case 'shipped':
                return $orderDate->copy()->addDays(rand(2, 5));
            case 'delivered':
                return $orderDate->copy()->addDays(rand(3, 10));
            case 'cancelled':
                return $orderDate->copy()->addHours(rand(1, 72));
            default:
                return $orderDate;
        }
    }

    /**
     * Create some special scenario orders
     */
    private function createSpecialOrders($users, $products, $faker): void
    {
        // Large order (high value)
        $user = $users->random();
        $largeOrderItems = [];
        $selectedProducts = $products->random(5);
        $subtotal = 0;
        
        foreach ($selectedProducts as $product) {
            $quantity = rand(2, 5);
            $unitPrice = $product->sellingprice_item ?? ($product->costprice_item * 1.5);
            $totalPrice = $unitPrice * $quantity;
            $subtotal += $totalPrice;
            
            $largeOrderItems[] = [
                'product_id' => $product->item_id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'item_name' => $product->name_item,
                'item_description' => $product->description_item ?? '',
            ];
        }
        
        $largeOrder = Order::create([
            'order_number' => 'ORD-LARGE-' . strtoupper(substr(uniqid(), -6)),
            'user_id' => $user->user_id,
            'total_amount' => $subtotal + 15000,
            'status' => 'delivered',
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => $user->phone ?: $faker->phoneNumber,
            'shipping_address' => $this->generateShippingAddress($faker, $user),
            'notes' => 'Pesanan besar - mohon dikemas dengan extra hati-hati',
            'order_date' => Carbon::now()->subDays(15),
            'shipping_cost' => 15000,
            'shipping_courier' => 'JNE',
            'shipping_service' => 'Express',
            'shipping_etd' => '1-2 hari',
            'tracking_number' => 'LG' . rand(100000000000, 999999999999),
            'shipping_status' => 'delivered',
            'shipping_notes' => 'Pesanan besar telah diterima dengan baik',
            'created_at' => Carbon::now()->subDays(15),
            'updated_at' => Carbon::now()->subDays(10),
        ]);

        foreach ($largeOrderItems as $itemData) {
            OrderItem::create([
                'order_id' => $largeOrder->id,
                'master_item_id' => $itemData['product_id'],
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'total_price' => $itemData['total_price'],
                'item_name' => $itemData['item_name'],
                'item_description' => $itemData['item_description'],
                'created_at' => Carbon::now()->subDays(15),
                'updated_at' => Carbon::now()->subDays(15),
            ]);
        }
        
        $this->command->info('Created 1 special large order');
    }

    /**
     * Show order statistics
     */
    private function showOrderStatistics(): void
    {
        $totalOrders = Order::count();
        $totalRevenue = Order::where('status', 'delivered')->sum('total_amount');
        
        $statusDistribution = Order::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->orderBy('count', 'desc')
            ->get();

        $this->command->info("");
        $this->command->info("=== ORDER STATISTICS ===");
        $this->command->info("Total Orders: {$totalOrders}");
        $this->command->info("Total Revenue: Rp " . number_format($totalRevenue, 0, ',', '.'));
        $this->command->info("");
        $this->command->info("Order Status Distribution:");
        
        foreach ($statusDistribution as $stat) {
            $percentage = round(($stat->count / $totalOrders) * 100, 1);
            $status = ucfirst($stat->status);
            $this->command->info("  {$status}: {$stat->count} orders ({$percentage}%)");
        }

        // Monthly order trends
        $monthlyOrders = Order::selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as count')
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        if ($monthlyOrders->count() > 0) {
            $this->command->info("");
            $this->command->info("Monthly Order Trends (Last 6 months):");
            foreach ($monthlyOrders as $monthly) {
                $monthName = Carbon::createFromDate($monthly->year, $monthly->month, 1)->format('F Y');
                $this->command->info("  {$monthName}: {$monthly->count} orders");
            }
        }
    }
}