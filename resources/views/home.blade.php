@extends('layouts.app')
@section('title', 'Beranda - Gentle Living')

@section('content')
    {{-- Modern Hero Banner with Mobile-First Design --}}
    <section class="relative bg-[#444444] overflow-hidden">
        <div
            class="container mx-auto px-4 sm:px-6 lg:px-8 min-h-screen flex items-center justify-center pt-28 pb-14 sm:pt-32 sm:pb-18 lg:pt-36 lg:pb-20">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8 xl:gap-12 items-center w-full">

                {{-- Content Section - Mobile First --}}
                <div
                    class="space-y-4 sm:space-y-6 lg:space-y-8 lg:pr-4 xl:pr-8 z-10 relative order-2 lg:order-1 text-center lg:text-left">

                    {{-- Badge/Tag - Mobile Centered --}}
                    <div class="flex justify-center lg:justify-start">
                        <div
                            class="inline-flex items-center shadow-lg space-x-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-full px-3 py-1.5 sm:px-4 sm:py-2 lg:px-6 lg:py-3">
                            <span
                                class="w-1.5 h-1.5 sm:w-2 sm:h-2 lg:w-3 lg:h-3 bg-[#B4EBE6] rounded-full animate-pulse"></span>
                            <span class="text-xs sm:text-sm lg:text-base text-[#B4EBE6] font-nunito font-bold">
                                Pilihan #1 Ibu Indonesia
                            </span>
                        </div>
                    </div>

                    {{-- Main Headline --}}
                    <div class="space-y-2 sm:space-y-3 lg:space-y-4">
                        <h1 class="font-fredoka font-bold leading-tight text-2xl sm:text-3xl lg:text-4xl xl:text-5xl 2xl:text-6xl max-w-lg mx-auto lg:mx-0"
                            style="color: #EF9F9B;">
                            {{ $banner?->title ?? 'Judul Banner' }}
                        </h1>
                    </div>

                    {{-- Description --}}
                    <div>
                        <p class="font-nunito text-sm sm:text-base lg:text-lg xl:text-xl leading-relaxed max-w-md lg:max-w-lg mx-auto lg:mx-0"
                            style="color: #B4EBE6;">
                            {{ $banner?->body ?? 'Deskripsi Banner' }}
                        </p>
                    </div>

                    {{-- CTA Button --}}
                    <div class="pt-2 sm:pt-4">
                        <a href="{{ route('shopping') }}"
                            target="_blank" class="inline-block px-6 py-3 sm:px-8 sm:py-4 lg:px-10 lg:py-4 bg-white text-gray-800 font-nunito font-bold text-xs sm:text-sm lg:text-base rounded-full shadow-lg hover:shadow-2xl hover:bg-gray-100 transform hover:scale-105 hover:-translate-y-1 transition-all duration-300 ease-in-out">
                            VIEW PRODUCTS
                        </a>
                    </div>
                </div>

                {{-- Image Section - Mobile First - Optimized --}}
                <div class="relative z-10 order-1 lg:order-2">
                    {{-- Main Product Image Container - Ukuran dikembalikan ke semula --}}
                    <div class="rounded-2xl overflow-hidden aspect-square w-full max-w-[400px] mx-auto shadow-xl">
                        <img src="{{ $bannerProduct?->image ? asset('storage/images/homepage/' . $bannerProduct->image) : asset('images/gentleBaby.png') }}"
                            alt="Gentle Baby Product" class="w-full h-full object-cover object-center">

                        {{-- Product Info Card - Ukuran dikembalikan ke semula --}}
                        <div
                            class="absolute bottom-2 right-2 sm:bottom-3 sm:right-3 lg:bottom-4 lg:right-4 xl:bottom-6 xl:right-6 bg-white/95 backdrop-blur-sm rounded-lg sm:rounded-xl lg:rounded-2xl p-2 sm:p-2.5 lg:p-3 xl:p-4 shadow-xl max-w-[240px] sm:max-w-[260px] lg:max-w-xs xl:max-w-sm">

                            {{-- Star Badge - Ukuran dikembalikan ke semula --}}
                            <div
                                class="absolute -top-1.5 -right-1.5 sm:-top-2 sm:-right-2 lg:-top-3 lg:-right-3 w-5 h-5 sm:w-6 sm:h-6 lg:w-8 lg:h-8 bg-yellow-100 rounded-full flex items-center justify-center shadow-lg">
                                <span class="text-yellow-600 text-[10px] sm:text-xs lg:text-sm">⭐</span>
                            </div>

                            <div class="space-y-1 sm:space-y-1.5 lg:space-y-2 xl:space-y-3">
                                <h3
                                    class="font-nunito text-[10px] sm:text-xs lg:text-sm xl:text-lg font-bold text-gray-800 leading-tight">
                                    {{ $bannerProduct?->title ?? 'Gentle Baby' }}
                                </h3>

                                {{-- Feature List - Ukuran dikembalikan ke semula --}}
                                <div class="space-y-0.5 sm:space-y-1 lg:space-y-1.5">
                                    @if ($bannerProduct?->body)
                                        @php
                                            $points = json_decode($bannerProduct->body, true);
                                            $pointsArray = $points['points'] ?? [];
                                        @endphp
                                        @foreach ($pointsArray as $index => $point)
                                            {{-- Tampilkan semua points di semua ukuran layar --}}
                                            <div class="flex items-start">
                                                <svg class="w-2.5 h-2.5 sm:w-3 sm:h-3 lg:w-4 lg:h-4 xl:w-5 xl:h-5 text-green-600 mr-1 sm:mr-1.5 lg:mr-2 flex-shrink-0 mt-0.5"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                <p
                                                    class="text-[9px] sm:text-[10px] lg:text-xs xl:text-sm text-gray-700 font-nunito leading-tight">
                                                    {{ $point }}
                                                </p>
                                            </div>
                                        @endforeach
                                    @else
                                        {{-- Default points - Ukuran dikembalikan ke semula --}}
                                        <div class="flex items-start">
                                            <svg class="w-2.5 h-2.5 sm:w-3 sm:h-3 lg:w-4 lg:h-4 xl:w-5 xl:h-5 text-green-600 mr-1 sm:mr-1.5 lg:mr-2 flex-shrink-0 mt-0.5"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <p
                                                class="text-[9px] sm:text-[10px] lg:text-xs xl:text-sm text-gray-700 font-nunito">
                                                100% alami</p>
                                        </div>
                                        <div class="flex items-start">
                                            <svg class="w-2.5 h-2.5 sm:w-3 sm:h-3 lg:w-4 lg:h-4 xl:w-5 xl:h-5 text-green-600 mr-1 sm:mr-1.5 lg:mr-2 flex-shrink-0 mt-0.5"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <p
                                                class="text-[9px] sm:text-[10px] lg:text-xs xl:text-sm text-gray-700 font-nunito">
                                                BPOM Certified</p>
                                        </div>
                                        <div class="flex items-start sm:flex">
                                            <svg class="w-2.5 h-2.5 sm:w-3 sm:h-3 lg:w-4 lg:h-4 xl:w-5 xl:h-5 text-green-600 mr-1 sm:mr-1.5 lg:mr-2 flex-shrink-0 mt-0.5"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <p
                                                class="text-[9px] sm:text-[10px] lg:text-xs xl:text-sm text-gray-700 font-nunito">
                                                Newborn Friendly</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Best Seller Section -->
    <section id="products" class="bg-white py-6 sm:py-10 lg:py-12 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 lg:gap-12">

                <!-- Products (3/4) -->
                <div class="lg:col-span-3 order-2 lg:order-1">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">

                        @forelse($topProducts as $index => $product)
                            <!-- Dynamic Product Card {{ $index + 1 }} -->
                            <div
                                class="bg-gradient-to-b from-white to-blue-50 rounded-xl border border-blue-300 shadow-md hover:shadow-lg transform hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col">
                                <div class="p-4 sm:p-5 flex flex-col flex-1">
                                    <!-- Product Image -->
                                    <div
                                        class="w-full rounded-lg mb-3 sm:mb-4 overflow-hidden flex items-center justify-center bg-gray-50 p-2 sm:p-3 h-40 sm:h-44 lg:h-48">
                                        <img src="{{ $product->display_image }}" alt="{{ $product->display_name }}"
                                            class="w-full h-full object-contain transition-transform duration-300 hover:scale-105">
                                    </div>
                                    <!-- Product Info -->
                                    <div class="text-center flex-1 flex flex-col justify-between">
                                        <div>
                                            <h3 class="font-fredoka text-[#614DAC] mb-1 sm:mb-2 text-base sm:text-lg">
                                                {{ $product->display_name }}
                                            </h3>
                                            <p class="text-xs sm:text-sm text-[#4D4C4C] font-nunito mb-3">
                                                {{ $product->display_description }}
                                            </p>
                                            @if ($product->transaction_sales_details_sum_qty)
                                                <p class="text-xs text-green-600 font-nunito font-semibold mb-3">
                                                    {{ number_format($product->transaction_sales_details_sum_qty) }}+
                                                    Terjual
                                                </p>
                                            @endif
                                        </div>
                                        <a href="{{ $product->shopping_url }}" target="_blank" rel="noopener noreferrer"
                                            class="w-full bg-[#785576] text-white font-nunito font-semibold py-2 sm:py-2.5 px-3 text-xs sm:text-sm rounded-lg mt-auto hover:opacity-90 inline-block text-center">
                                            Beli
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <!-- Fallback jika tidak ada data produk -->
                            <div class="col-span-full text-center py-8">
                                <p class="text-gray-500 font-nunito">Data produk sedang dimuat...</p>
                            </div>
                        @endforelse

                    </div>
                </div>

                <!-- Title (1/4) -->
                <div class="lg:col-span-1 order-1 lg:order-2 flex flex-col justify-center">
                    <div class="text-center">
                        <h1 class="font-fredoka text-2xl sm:text-3xl lg:text-4xl text-[#614DAC] mb-2">
                            Produk Terlaris
                        </h1>
                        <p class="font-nunito text-sm sm:text-base lg:text-lg text-[#4D4C4C] leading-relaxed">
                            Produk yang selalu menjadi favorit ibu hingga saat ini
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Product Details Section -->
    <section class="relative overflow-hidden bg-white to-blue-100/30">

        <!-- Gentle Living -->
        <div class="relative sm:py-10 lg:py-14 px-4 sm:px-6 lg:px-8 my-8 sm:my-12 lg:my-20">
            <!-- Background - Hidden di mobile, visible di desktop -->
            <div
                class="hidden lg:block absolute right-0 top-0 bottom-0 w-[calc(100%-320px)] bg-[#FFFDEB] rounded-l-[100px] shadow-md z-0">
            </div>

            <!-- Mobile Background -->
            <div class="lg:hidden absolute inset-0 bg-gradient-to-br from-[#FFFDEB]/20 to-[#FFFDEB]/40 rounded-2xl z-0">
            </div>

            <!-- Konten - Mobile first, Desktop optimized -->
            <div class="relative z-10 max-w-6xl mx-auto">
                <!-- Mobile Layout (Stack) -->
                <div class="lg:hidden space-y-6 text-center py-6">
                    <!-- Gambar Produk Mobile -->
                    <div class="flex justify-center">
                        <div
                            class="w-32 h-32 sm:w-40 sm:h-40 bg-white rounded-xl shadow-md flex items-center justify-center overflow-hidden">
                            <img src="{{ asset('images/gentleBaby.png') }}" alt="Gentle Baby"
                                class="w-full h-full object-contain transition-transform duration-300 hover:scale-105">
                        </div>
                    </div>

                    <!-- Detail Produk Mobile -->
                    <div>
                        <h2 class="font-fredoka text-xl sm:text-2xl text-[#614DAC] mb-3">Gentle Baby</h2>
                        <ul class="text-sm sm:text-base text-[#4D4C4C] space-y-2 font-nunito text-left max-w-sm mx-auto">
                            <li class="flex items-start"><span class="text-green-600 mr-2">✔</span> Keajaiban sentuhan
                                skin-to-skin</li>
                            <li class="flex items-start"><span class="text-green-600 mr-2">✔</span> 100% Bahan Alami &
                                tidak mencemari lingkungan</li>
                            <li class="flex items-start"><span class="text-green-600 mr-2">✔</span> Dimulai dari cinta
                                seorang ibu untuk anaknya</li>
                            <li class="flex items-start"><span class="text-green-600 mr-2">✔</span> Aman & Berkhasiat
                                untuk bayi</li>
                        </ul>
                        <div class="mt-6">
                            <a href="https://shopee.co.id/gentleliving_id?page=1&sortBy=pop&tab=0" target="_blank"
                                class="inline-block bg-[#785576] text-white text-sm font-semibold font-nunito px-6 py-3 rounded-lg hover:shadow-md transition">
                                Lihat Produk →
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Desktop Layout (Side by side) -->
                <div class="hidden lg:grid lg:grid-cols-[auto_1fr] items-center gap-12 pl-28">
                    <!-- Gambar Produk Desktop -->
                    <div>
                        <div
                            class="w-60 h-60 bg-white rounded-xl shadow-md flex items-center justify-center overflow-hidden">
                            <img src="{{ asset('images/gentleBaby.png') }}" alt="Gentle Baby"
                                class="w-full h-full object-contain transition-transform duration-300 hover:scale-105">
                        </div>
                    </div>

                    <!-- Detail Produk Desktop -->
                    <div class="text-left">
                        <h2 class="font-fredoka text-4xl text-[#614DAC] mb-2">Gentle Baby</h2>
                        <ul class="text-lg text-[#4D4C4C] space-y-3 font-nunito">
                            <li class="flex items-start"><span class="text-green-600 mr-2">✔</span> Keajaiban sentuhan
                                skin-to-skin</li>
                            <li class="flex items-start"><span class="text-green-600 mr-2">✔</span> 100% Bahan Alami &
                                tidak mencemari lingkungan</li>
                            <li class="flex items-start"><span class="text-green-600 mr-2">✔</span> Dimulai dari cinta
                                seorang ibu untuk anaknya</li>
                            <li class="flex items-start"><span class="text-green-600 mr-2">✔</span> Aman & Berkhasiat
                                untuk bayi</li>
                        </ul>
                        <div class="mt-8">
                            <a href="https://shopee.co.id/gentleliving_id?page=1&sortBy=pop&tab=0" target="_blank"
                                class="bg-[#785576] text-white text-sm font-semibold font-nunito px-6 py-3 rounded-lg hover:shadow-md transition">
                                Lihat Produk →
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mamina ASI Booster -->
        <div class="relative sm:py-10 lg:py-14 px-4 sm:px-6 lg:px-8 my-8 sm:my-12 lg:my-20">
            <!-- Background - Hidden di mobile, visible di desktop -->
            <div
                class="hidden lg:block absolute left-0 top-0 bottom-0 w-[calc(100%-320px)] bg-[#FFFDEB] rounded-r-[100px] shadow-md z-0">
            </div>

            <!-- Mobile Background -->
            <div class="lg:hidden absolute inset-0 bg-gradient-to-bl from-[#FFFDEB]/20 to-[#FFFDEB]/40 rounded-2xl z-0">
            </div>

            <!-- Konten - Mobile first, Desktop optimized -->
            <div class="relative z-10 max-w-6xl mx-auto">
                <!-- Mobile Layout (Stack) -->
                <div class="lg:hidden space-y-6 text-center py-6">
                    <!-- Gambar Produk Mobile -->
                    <div class="flex justify-center">
                        <div
                            class="w-32 h-32 sm:w-40 sm:h-40 bg-white rounded-xl shadow-md flex items-center justify-center overflow-hidden">
                            <img src="{{ asset('images/mamina.png') }}" alt="Mamina ASI Booster"
                                class="w-full h-full object-contain transition-transform duration-300 hover:scale-105">
                        </div>
                    </div>

                    <!-- Detail Produk Mobile -->
                    <div>
                        <h2 class="font-fredoka text-xl sm:text-2xl text-[#614DAC] mb-3">Mamina ASI Booster</h2>
                        <div class="text-sm sm:text-base text-[#4D4C4C] font-nunito space-y-3 max-w-sm mx-auto">
                            <p>Pelancar ASI dari bahan Rimpang Alami</p>
                            <p>Seduhan herbal dengan khasiat melancarkan ASI dengan komposisi 100% bahan alami, tanpa
                                pemanis dan perisa.</p>
                            <p>Tersedia 3 varian: <span class="font-medium">Original, Jeruk Nipis</span> dan <span
                                    class="font-medium">Belimbing Wuluh</span>.</p>
                        </div>
                        <div class="mt-6">
                            <a href="https://shopee.co.id/maminast0re#product_list" target="_blank"
                                class="inline-block bg-[#785576] text-white text-sm font-semibold font-nunito px-6 py-3 rounded-lg hover:shadow-md transition">
                                Lihat Produk →
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Desktop Layout (Side by side) -->
                <div class="hidden lg:grid lg:grid-cols-[1fr_auto] items-center gap-12 pr-28">
                    <div class="text-left">
                        <h2 class="font-fredoka text-4xl text-[#614DAC] mb-2">Mamina ASI Booster</h2>
                        <p class="text-lg text-[#4D4C4C font-nunito mb-4">
                            Pelancar ASI dari bahan Rimpang Alami
                        </p>
                        <p class="text-lg text-[#785576] font-nunito mb-4">
                            Seduhan herbal dengan khasiat melancarkan ASI dengan komposisi 100% bahan alami, tanpa pemanis
                            dan perisa.
                        </p>
                        <p class="text-lg text-[#4D4C4C] font-nunito mb-8">
                            Tersedia 3 varian: <span class="font-medium">Original, Jeruk Nipis</span> dan <span
                                class="font-medium">Belimbing Wuluh</span>.
                        </p>
                        <a href="https://shopee.co.id/maminast0re#product_list" target="_blank"
                            class="bg-[#785576] text-white text-sm font-semibold px-6 py-3 rounded-lg hover:shadow-md transition">
                            Lihat Produk →
                        </a>
                    </div>
                    <div>
                        <div
                            class="w-60 h-60 bg-white rounded-xl shadow-md flex items-center justify-center overflow-hidden">
                            <img src="{{ asset('images/mamina.png') }}" alt="Mamina ASI Booster"
                                class="w-full h-full object-contain transition-transform duration-300 hover:scale-105">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Nyam! -->
        <div class="relative sm:py-10 lg:py-14 px-4 sm:px-6 lg:px-8 my-8 sm:my-12 lg:my-20">
            <!-- Background - Hidden di mobile, visible di desktop -->
            <div
                class="hidden lg:block absolute right-0 top-0 bottom-0 w-[calc(100%-320px)] bg-[#FFFDEB] rounded-l-[100px] shadow-md z-0">
            </div>

            <!-- Mobile Background -->
            <div class="lg:hidden absolute inset-0 bg-gradient-to-br from-[#FFFDEB]/20 to-[#FFFDEB]/40 rounded-2xl z-0">
            </div>

            <!-- Konten - Mobile first, Desktop optimized -->
            <div class="relative z-10 max-w-6xl mx-auto">
                <!-- Mobile Layout (Stack) -->
                <div class="lg:hidden space-y-6 text-center py-6">
                    <!-- Gambar Produk Mobile -->
                    <div class="flex justify-center">
                        <div
                            class="w-32 h-32 sm:w-40 sm:h-40 bg-white rounded-xl shadow-md flex items-center justify-center overflow-hidden">
                            <img src="{{ asset('images/nyam.png') }}" alt="Nyam"
                                class="w-full h-full object-contain transition-transform duration-300 hover:scale-105">
                        </div>
                    </div>

                    <!-- Detail Produk Mobile -->
                    <div>
                        <h2 class="font-fredoka text-xl sm:text-2xl text-[#614DAC] mb-3">Nyam!</h2>
                        <p class="text-sm sm:text-base text-[#4D4C4C] font-nunito leading-relaxed max-w-sm mx-auto mb-6">
                            Dibuat menggunakan berbagai bahan pilihan dan berkualitas tinggi yang diolah dengan tangan
                            kreatif dari seorang Ibu, sekaligus praktisi kesehatan sehingga menghasilkan sebuah produk
                            Makanan Pendamping ASI yang sehat, padat gizi serta memiliki citra rasa rumahan yang lezat.
                        </p>
                        <div class="mt-6">
                            <a href="https://shopee.co.id/nyambabyfood#product_list" target="_blank"
                                class="inline-block bg-[#785576] text-white text-sm font-semibold font-nunito px-6 py-3 rounded-lg hover:shadow-md transition">
                                Lihat Produk →
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Desktop Layout (Side by side) -->
                <div class="hidden lg:grid lg:grid-cols-[auto_1fr] items-center gap-12 pl-28">
                    <div>
                        <div
                            class="w-60 h-60 bg-white rounded-xl shadow-md flex items-center justify-center overflow-hidden">
                            <img src="{{ asset('images/nyam.png') }}" alt="Nyam"
                                class="w-full h-full object-contain transition-transform duration-300 hover:scale-105">
                        </div>
                    </div>
                    <div class="text-left">
                        <h2 class="font-fredoka text-4xl text-[#614DAC] mb-2">Nyam!</h2>
                        <p class="text-lg text-[#4D4C4C] font-nunito mb-8 leading-relaxed">
                            Dibuat menggunakan berbagai bahan pilihan dan berkualitas tinggi yang diolah dengan tangan
                            kreatif dari seorang Ibu, sekaligus praktisi kesehatan sehingga menghasilkan sebuah produk
                            Makanan Pendamping ASI yang sehat, padat gizi serta memiliki citra rasa rumahan yang lezat.
                        </p>
                        <a href="https://shopee.co.id/nyambabyfood#product_list" target="_blank"
                            class="bg-[#785576] text-white text-sm font-semibold px-6 py-3 rounded-lg hover:shadow-md transition">
                            Lihat Produk →
                        </a>
                    </div>
                </div>
            </div>

    </section>

    <!-- Lebih dari Sekedar Produk Section -->
    <section class="py-14 sm:py-10 bg-white mt-2 sm:mt-4">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center">

            <!-- Section Header -->
            <div class="mb-8 sm:mb-12">
                <h2 class="font-fredoka text-2xl sm:text-3xl lg:text-4xl text-[#614DAC] mb-2">
                    {{ $informationMain?->title ?? 'Lebih dari Sekedar Produk' }}
                </h2>
                <p
                    class="font-nunito text-sm sm:text-base lg:text-lg text-[#4D4C4C] leading-relaxed max-w-3xl lg:max-w-4xl mx-auto">
                    {{ $informationMain?->body ?? 'Kami percaya pada kekuatan sentuhan dan bahan-bahan alami untuk merawat si kecil dengan lembut.' }}
                </p>
            </div>

            <!-- Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 sm:gap-8">

                <!-- Card 1 -->
                <div class="bg-white rounded-xl shadow-md p-5 flex items-center gap-4">
                    <div class="w-10 h-10 flex items-center justify-center rounded-full bg-[#F8F5F8] shadow-sm">
                        <x-heroicon-s-check-circle class="w-6 h-6 text-green-600" />
                    </div>
                    <p class="font-nunito text-sm sm:text-base lg:text-lg text-[#4D4C4C]">
                        {{ $information1?->title ?? '100% Bahan Alami' }}
                    </p>
                </div>

                <!-- Card 2 -->
                <div class="bg-white rounded-xl shadow-md p-5 flex items-center gap-4">
                    <div class="w-10 h-10 flex items-center justify-center rounded-full bg-[#F8F5F8] shadow-sm">
                        <x-heroicon-s-check-circle class="w-6 h-6 text-green-600" />
                    </div>
                    <p class="font-nunito text-sm sm:text-base lg:text-lg text-[#4D4C4C]">
                        {{ $information2?->title ?? 'Teruji Secara Klinis' }}
                    </p>
                </div>

                <!-- Card 3 -->
                <div class="bg-white rounded-xl shadow-md p-5 flex items-center gap-4">
                    <div class="w-10 h-10 flex items-center justify-center rounded-full bg-[#F8F5F8] shadow-sm">
                        <x-heroicon-s-check-circle class="w-6 h-6 text-green-600" />
                    </div>
                    <p class="font-nunito text-sm sm:text-base lg:text-lg text-[#4D4C4C]">
                        {{ $information3?->title ?? 'Aman untuk Kulit Sensitif' }}
                    </p>
                </div>

            </div>
        </div>
    </section>

    <!-- Testimonial Section -->
    <section class="py-12 sm:py-16 bg-white mt-8 sm:mt-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Hero Testimonial Image with Overlay -->
            <div class="relative h-64 sm:h-80 rounded-lg overflow-hidden">
                <!-- Foto Customer -->
                <img src="{{ asset('images/banner/profil2.jpg') }}" alt="Customer" class="w-full h-full object-cover">

                <!-- Overlay Text + Button -->
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center px-4">
                    <h2 class="font-fredoka text-2xl sm:text-3xl lg:text-4xl text-white mb-4">
                        Telah dipercaya {{ number_format($customerCount, 0, ',', '.') }}+ Ibu
                    </h2>
                    <a href="#"
                        class="bg-[#785576] text-white text-sm font-semibold px-6 py-3 rounded-lg hover:shadow-md transition">
                        Selengkapnya
                    </a>
                </div>
            </div>

            <!-- Dynamic Testimonial from Database -->
            @if ($bestReview)
                <div class="mt-8 flex items-start gap-4">
                    <!-- Avatar dengan Rating Stars -->
                    <div class="flex-shrink-0">
                        <div
                            class="w-14 h-14 flex items-center justify-center rounded-full bg-gray-100 text-[#4D4C4C] mb-2">
                            <x-heroicon-s-user class="w-8 h-8" />
                        </div>
                        <!-- Rating Stars -->
                        <div class="flex justify-center">
                            @for ($i = 1; $i <= 5; $i++)
                                @if ($i <= $bestReview->rating)
                                    <svg class="w-3 h-3 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                        <path
                                            d="M10 15l-5.878 3.09 1.123-6.545L0 6.91l6.564-.955L10 0l3.436 5.955L20 6.91l-5.245 4.635L15.878 18z" />
                                    </svg>
                                @else
                                    <svg class="w-3 h-3 text-gray-300 fill-current" viewBox="0 0 20 20">
                                        <path
                                            d="M10 15l-5.878 3.09 1.123-6.545L0 6.91l6.564-.955L10 0l3.436 5.955L20 6.91l-5.245 4.635L15.878 18z" />
                                    </svg>
                                @endif
                            @endfor
                        </div>
                    </div>

                    <!-- Testimonial Text -->
                    <div>
                        <p class="font-nunito text-[#4D4C4C] italic leading-relaxed text-sm sm:text-base mb-3">
                            "{{ $bestReview->comment }}"
                        </p>
                        {{-- <p class="font-fredoka text-[#4D4C4C] text-sm sm:text-base">
                            {{ $bestReview->customer ? $bestReview->customer->name : ($bestReview->user ? $bestReview->user->name : 'Customer') }}
                        </p> --}}
                        <p class="font-nunito text-gray-500 text-xs mt-1">
                            {{ $bestReview->created_at->format('d M Y') }}
                        </p>
                    </div>
                </div>
            @else
                <!-- Fallback Static Testimonial -->
                <div class="mt-8 flex items-start gap-4">
                    <!-- Avatar dari Heroicons -->
                    <div
                        class="w-14 h-14 flex items-center justify-center rounded-full bg-gray-100 text-[#4D4C4C] flex-shrink-0">
                        <x-heroicon-s-user class="w-8 h-8" />
                    </div>

                    <!-- Testimonial Text -->
                    <div>
                        <p class="font-nunito text-[#4D4C4C] italic leading-relaxed text-sm sm:text-base mb-3">
                            “Hari ini Fatihyah masuk angin, muntah, dan mual. Trus inget punya Tummy Calmer.
                            Langsung dioles-oles ke perut Alhamdulillah langsung terkentut-kentut dan lega katanya.
                            Makasih Gentle Baby!”
                        </p>
                        <p class="font-fredoka text-[#4D4C4C] text-sm sm:text-base">
                            Mom Firda Amalia
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-12 sm:py-16 bg-white mt-4 ">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Section Header -->
            <div class="text-center mb-8 sm:mb-12">
                <h2 class="font-fredoka text-2xl sm:text-3xl lg:text-4xl text-[#614DAC] mb-2">
                    Frequently Asked Questions
                </h2>
                <p
                    class="font-nunito text-sm sm:text-base lg:text-lg text-[#4D4C4C] max-w-xl lg:max-w-2xl mx-auto px-4 sm:px-0">
                    Temukan jawaban untuk pertanyaan yang sering diajukan seputar produk dan layanan kami
                </p>
            </div>

            <!-- FAQ Items -->
            <div class="space-y-3 sm:space-y-4">
                @if ($faqs && $faqs->count() > 0)
                    @foreach ($faqs as $faq)
                        <!-- FAQ Item -->
                        <div class="border border-gray-200 rounded-lg bg-white shadow-sm">
                            <button
                                class="faq-toggle w-full px-4 sm:px-6 py-3 sm:py-4 text-left font-nunito font-medium text-[#4D4C4C] flex justify-between items-center hover:brightness-90 transition-colors">
                                <span class="text-sm sm:text-base lg:text-lg pr-2">{{ $faq->title }}</span>
                                <svg class="faq-icon w-4 sm:w-5 h-4 sm:h-5 text-[#4D4C4C] transform transition-transform flex-shrink-0"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7">
                                    </path>
                                </svg>
                            </button>
                            <div class="faq-content hidden px-4 sm:px-6 pb-3 sm:pb-4">
                                <p class="text-[#72C7B4] leading-relaxed text-sm sm:text-base">{{ $faq->body }}</p>
                            </div>
                        </div>
                    @endforeach
                @else
                    <!-- Default FAQ Item jika tidak ada data di database -->
                    <div class="border border-gray-200 rounded-lg bg-white shadow-sm">
                        <button
                            class="faq-toggle w-full px-4 sm:px-6 py-3 sm:py-4 text-left font-nunito font-medium text-[#4D4C4C] flex justify-between items-center transition-colors">
                            <span class="text-sm sm:text-base lg:text-lg pr-2">Apakah produk Gentle Baby aman untuk bayi
                                yang baru lahir?</span>
                            <svg class="faq-icon w-4 sm:w-5 h-4 sm:h-5 text-[#4D4C4C] transform transition-transform flex-shrink-0"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                                </path>
                            </svg>
                        </button>
                        <div class="faq-content hidden px-4 sm:px-6 pb-3 sm:pb-4">
                            <p class="text-[#72C7B4] leading-relaxed text-sm sm:text-base">Ya, produk Gentle Baby
                                diformulasikan khusus untuk bayi dari usia 0 bulan. Menggunakan 100% bahan alami yang aman.
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const faqToggles = document.querySelectorAll('.faq-toggle');

            faqToggles.forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const content = this.nextElementSibling;
                    const icon = this.querySelector('.faq-icon');

                    // Toggle content
                    if (content.classList.contains('hidden')) {
                        content.classList.remove('hidden');
                        icon.style.transform = 'rotate(180deg)';
                    } else {
                        content.classList.add('hidden');
                        icon.style.transform = 'rotate(0deg)';
                    }

                    // Close other FAQs
                    faqToggles.forEach(otherToggle => {
                        if (otherToggle !== this) {
                            const otherContent = otherToggle.nextElementSibling;
                            const otherIcon = otherToggle.querySelector('.faq-icon');
                            otherContent.classList.add('hidden');
                            otherIcon.style.transform = 'rotate(0deg)';
                        }
                    });
                });
            });
        });
    </script>

@endsection
