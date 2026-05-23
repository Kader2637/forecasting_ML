@extends('layouts.app')
@section('title', 'Beranda - Gentle Living')

@section('content')
    {{-- Modern Hero Banner with Mobile-First Design & Abstract Blobs --}}
    <section class="relative bg-gradient-to-br from-[#f0f9fa] via-white to-[#f4eff8] overflow-hidden">
        {{-- Abstract Blobs Background --}}
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0 pointer-events-none">
            <div class="absolute -top-20 -left-20 w-72 h-72 bg-[#b7e6e4] rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob"></div>
            <div class="absolute top-40 -right-20 w-72 h-72 bg-[#e8d5e8] rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob animation-delay-2000"></div>
            <div class="absolute -bottom-32 left-1/2 w-72 h-72 bg-[#fff1d6] rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob animation-delay-4000"></div>
        </div>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 min-h-screen flex items-center justify-center pt-28 pb-14 sm:pt-32 sm:pb-18 lg:pt-36 lg:pb-20 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-12 items-center w-full">

                {{-- Content Section - Mobile First --}}
                <div class="space-y-6 sm:space-y-8 lg:pr-8 text-center lg:text-left order-2 lg:order-1 relative">
                    
                    {{-- Badge/Tag --}}
                    <div class="flex justify-center lg:justify-start">
                        <div class="inline-flex items-center space-x-2 bg-white/60 backdrop-blur-md border border-white/40 shadow-sm rounded-full px-4 py-2 transform hover:-translate-y-1 transition-all duration-300">
                            <span class="w-2 h-2 sm:w-2.5 sm:h-2.5 bg-[#528b89] rounded-full animate-pulse"></span>
                            <span class="text-xs sm:text-sm lg:text-base text-[#528b89] font-nunito font-bold">
                                Pilihan #1 Ibu Indonesia
                            </span>
                        </div>
                    </div>

                    {{-- Main Headline --}}
                    <div class="space-y-4">
                        <h1 class="font-fredoka font-bold leading-tight text-4xl sm:text-5xl lg:text-6xl text-[#614DAC] drop-shadow-sm">
                            {{ $banner?->title ?? 'Judul Banner' }}
                        </h1>
                        <p class="font-nunito text-base sm:text-lg lg:text-xl leading-relaxed text-[#4D4C4C] max-w-lg mx-auto lg:mx-0">
                            {{ $banner?->body ?? 'Deskripsi Banner' }}
                        </p>
                    </div>

                    {{-- CTA Button --}}
                    <div class="pt-2">
                        <a href="{{ route('shopping') }}" target="_blank" class="inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-[#6C63FF] to-[#8C86FF] text-white font-nunito font-bold text-sm sm:text-base rounded-full shadow-lg hover:shadow-xl hover:shadow-[#6C63FF]/30 transform hover:-translate-y-1 hover:scale-105 transition-all duration-300 ease-out group">
                            VIEW PRODUCTS
                            <svg class="w-5 h-5 ml-2 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                        </a>
                    </div>
                </div>

                {{-- Image Section - Mobile First --}}
                <div class="relative z-10 order-1 lg:order-2 flex justify-center">
                    {{-- Decorative ring behind image --}}
                    <div class="absolute inset-0 bg-gradient-to-tr from-[#6C63FF]/20 to-[#42b883]/20 rounded-full blur-2xl transform scale-90"></div>
                    
                    <div class="relative rounded-3xl overflow-hidden aspect-square w-full max-w-[400px] shadow-2xl ring-4 ring-white/50 transform hover:rotate-1 transition-transform duration-500">
                        <img src="{{ $bannerProduct?->image ? asset('storage/images/homepage/' . $bannerProduct->image) : asset('images/gentleBaby.png') }}"
                            alt="Gentle Baby Product" class="w-full h-full object-cover object-center bg-white">

                        {{-- Product Info Card Floating --}}
                        <div class="absolute bottom-4 right-4 bg-white/90 backdrop-blur-md rounded-2xl p-4 shadow-xl border border-white/50 max-w-[240px] sm:max-w-xs transform hover:-translate-y-2 transition-all duration-300 group">
                            <div class="absolute -top-3 -right-3 w-8 h-8 bg-gradient-to-br from-yellow-300 to-yellow-500 rounded-full flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform">
                                <span class="text-white text-xs drop-shadow-md">⭐</span>
                            </div>

                            <h3 class="font-fredoka text-sm sm:text-base text-[#614DAC] leading-tight mb-2">
                                {{ $bannerProduct?->title ?? 'Gentle Baby' }}
                            </h3>

                            <div class="space-y-1.5">
                                @if ($bannerProduct?->body)
                                    @php
                                        $points = json_decode($bannerProduct->body, true);
                                        $pointsArray = $points['points'] ?? [];
                                    @endphp
                                    @foreach ($pointsArray as $index => $point)
                                        <div class="flex items-start">
                                            <svg class="w-4 h-4 text-[#42b883] mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            <p class="text-xs sm:text-sm text-[#4D4C4C] font-nunito leading-tight">
                                                {{ $point }}
                                            </p>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="flex items-start">
                                        <svg class="w-4 h-4 text-[#42b883] mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <p class="text-xs sm:text-sm text-[#4D4C4C] font-nunito">100% alami</p>
                                    </div>
                                    <div class="flex items-start">
                                        <svg class="w-4 h-4 text-[#42b883] mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <p class="text-xs sm:text-sm text-[#4D4C4C] font-nunito">BPOM Certified</p>
                                    </div>
                                    <div class="flex items-start">
                                        <svg class="w-4 h-4 text-[#42b883] mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <p class="text-xs sm:text-sm text-[#4D4C4C] font-nunito">Newborn Friendly</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Best Seller Section -->
    <section id="products" class="relative bg-white py-12 sm:py-16 lg:py-24">
        {{-- Decorative background elements --}}
        <div class="absolute top-0 right-0 w-64 h-64 bg-[#f0f9fa] rounded-full mix-blend-multiply filter blur-3xl opacity-60 pointer-events-none transform translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute bottom-0 left-0 w-80 h-80 bg-[#fff1d6] rounded-full mix-blend-multiply filter blur-3xl opacity-60 pointer-events-none transform -translate-x-1/2 translate-y-1/2"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 lg:gap-16 items-center">

                <!-- Title (1/4) -->
                <div class="lg:col-span-1 order-1 lg:order-1 flex flex-col justify-center text-center lg:text-left">
                    <div class="mb-8 lg:mb-0">
                        <span class="inline-block py-1 px-3 rounded-full bg-[#f4eff8] text-[#614DAC] text-sm font-bold font-nunito mb-4 tracking-wider uppercase">Hot Items 🔥</span>
                        <h2 class="font-fredoka text-3xl sm:text-4xl lg:text-5xl text-[#614DAC] mb-4 leading-tight">
                            Produk Terlaris
                        </h2>
                        <p class="font-nunito text-base sm:text-lg text-[#4D4C4C] leading-relaxed">
                            Pilihan utama dan favorit para ibu untuk menjaga kesehatan dan kenyamanan si kecil setiap harinya.
                        </p>
                        <div class="hidden lg:block mt-8">
                            <a href="{{ route('shopping') }}" class="inline-flex items-center text-[#528b89] font-bold font-nunito hover:text-[#3a5756] transition-colors group">
                                Lihat Semua Produk
                                <svg class="w-4 h-4 ml-2 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Products (3/4) -->
                <div class="lg:col-span-3 order-2 lg:order-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8 lg:gap-10">

                        @forelse($topProducts as $index => $product)
                            <!-- Dynamic Product Card {{ $index + 1 }} -->
                            <div class="group bg-white rounded-3xl border border-gray-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-[0_20px_40px_rgb(0,0,0,0.08)] transform hover:-translate-y-2 transition-all duration-500 overflow-hidden flex flex-col relative">
                                
                                {{-- Hover Glow Effect --}}
                                <div class="absolute inset-0 bg-gradient-to-b from-transparent to-[#f4eff8]/50 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>

                                <div class="p-6 flex flex-col flex-1 relative z-10">
                                    <!-- Product Image -->
                                    <div class="w-full rounded-2xl mb-6 overflow-hidden flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100/50 p-4 h-48 sm:h-52 group-hover:from-[#f0f9fa] group-hover:to-white transition-colors duration-500 relative">
                                        <img src="{{ $product->display_image }}" alt="{{ $product->display_name }}"
                                            class="w-full h-full object-contain transform group-hover:scale-110 transition-transform duration-700 ease-out drop-shadow-sm">
                                    </div>
                                    <!-- Product Info -->
                                    <div class="text-center flex-1 flex flex-col justify-between">
                                        <div>
                                            <h3 class="font-fredoka text-[#614DAC] mb-2 text-lg sm:text-xl group-hover:text-[#8C86FF] transition-colors">
                                                {{ $product->display_name }}
                                            </h3>
                                            <p class="text-sm text-[#4D4C4C] font-nunito mb-4 line-clamp-2">
                                                {{ $product->display_description }}
                                            </p>
                                        </div>
                                        <div>
                                            @if ($product->transaction_sales_details_sum_qty)
                                                <div class="inline-flex items-center justify-center px-3 py-1 rounded-full bg-green-50 text-green-600 text-xs font-nunito font-bold mb-4">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                                    {{ number_format($product->transaction_sales_details_sum_qty) }}+ Terjual
                                                </div>
                                            @endif
                                            <a href="{{ $product->shopping_url }}" target="_blank" rel="noopener noreferrer"
                                                class="w-full bg-[#614DAC] text-white font-nunito font-bold py-3 px-4 rounded-xl mt-auto hover:bg-[#8C86FF] hover:shadow-lg hover:shadow-[#8C86FF]/30 transform active:scale-95 transition-all duration-300 inline-flex items-center justify-center group/btn">
                                                Beli Sekarang
                                                <svg class="w-4 h-4 ml-2 opacity-0 -translate-x-2 group-hover/btn:opacity-100 group-hover/btn:translate-x-0 transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <!-- Fallback jika tidak ada data produk -->
                            <div class="col-span-full text-center py-12 bg-gray-50 rounded-3xl border border-dashed border-gray-200">
                                <p class="text-gray-500 font-nunito text-lg">Data produk sedang dimuat...</p>
                            </div>
                        @endforelse

                    </div>
                </div>
                
                <!-- Mobile Only Link -->
                <div class="lg:hidden col-span-1 mt-4 text-center">
                    <a href="{{ route('shopping') }}" class="inline-flex items-center justify-center px-6 py-3 border-2 border-[#528b89] text-[#528b89] rounded-full font-bold font-nunito hover:bg-[#528b89] hover:text-white transition-colors">
                        Lihat Semua Produk
                    </a>
                </div>

            </div>
        </div>
    </section>

    <!-- Product Details Section -->
    <section class="relative overflow-hidden bg-white py-12 sm:py-20">

        <!-- Gentle Baby -->
        <div class="relative px-4 sm:px-6 lg:px-8 my-12 sm:my-20">
            <!-- Background Decoration -->
            <div class="absolute left-0 top-1/2 -translate-y-1/2 w-[90%] md:w-[70%] h-[120%] bg-gradient-to-r from-[#e8f4f3] to-transparent rounded-r-[100px] z-0 pointer-events-none"></div>
            
            <div class="relative z-10 max-w-6xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <!-- Image -->
                    <div class="order-1 lg:order-1 flex justify-center relative">
                        <div class="absolute inset-0 bg-[#b7e6e4]/40 rounded-full blur-3xl transform scale-75 animate-pulse"></div>
                        <div class="w-56 h-56 sm:w-72 sm:h-72 lg:w-96 lg:h-96 bg-white/60 backdrop-blur-md rounded-[3rem] shadow-2xl p-6 sm:p-10 border border-white flex items-center justify-center transform hover:rotate-2 transition-transform duration-500 relative z-10">
                            <img src="{{ asset('images/gentleBaby.png') }}" alt="Gentle Baby" class="w-full h-full object-contain hover:scale-110 transition-transform duration-500">
                        </div>
                    </div>
                    <!-- Text -->
                    <div class="order-2 lg:order-2 text-center lg:text-left space-y-6">
                        <div class="inline-block px-4 py-1.5 rounded-full bg-[#f4eff8] text-[#614DAC] text-sm font-bold font-nunito tracking-wide">Essential Care</div>
                        <h2 class="font-fredoka text-3xl sm:text-4xl lg:text-5xl text-[#614DAC]">Gentle Baby</h2>
                        <ul class="text-base sm:text-lg text-[#4D4C4C] space-y-4 font-nunito text-left max-w-md mx-auto lg:mx-0">
                            <li class="flex items-start bg-white/50 rounded-lg p-2"><span class="text-[#42b883] mr-3 text-xl leading-none">✔</span> Keajaiban sentuhan skin-to-skin</li>
                            <li class="flex items-start bg-white/50 rounded-lg p-2"><span class="text-[#42b883] mr-3 text-xl leading-none">✔</span> 100% Bahan Alami & tidak mencemari lingkungan</li>
                            <li class="flex items-start bg-white/50 rounded-lg p-2"><span class="text-[#42b883] mr-3 text-xl leading-none">✔</span> Dimulai dari cinta seorang ibu untuk anaknya</li>
                            <li class="flex items-start bg-white/50 rounded-lg p-2"><span class="text-[#42b883] mr-3 text-xl leading-none">✔</span> Aman & Berkhasiat untuk bayi</li>
                        </ul>
                        <div class="pt-4">
                            <a href="https://shopee.co.id/gentleliving_id?page=1&sortBy=pop&tab=0" target="_blank"
                                class="inline-flex items-center justify-center px-8 py-3.5 bg-[#614DAC] text-white text-base font-bold font-nunito rounded-2xl hover:bg-[#8C86FF] hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300">
                                Eksplorasi Gentle Baby
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mamina ASI Booster -->
        <div class="relative px-4 sm:px-6 lg:px-8 my-12 sm:my-24">
            <!-- Background Decoration -->
            <div class="absolute right-0 top-1/2 -translate-y-1/2 w-[90%] md:w-[70%] h-[120%] bg-gradient-to-l from-[#fffdeb] to-transparent rounded-l-[100px] z-0 pointer-events-none"></div>
            
            <div class="relative z-10 max-w-6xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <!-- Text (Left on Desktop) -->
                    <div class="order-2 lg:order-1 text-center lg:text-left space-y-6">
                        <div class="inline-block px-4 py-1.5 rounded-full bg-[#fcf2df] text-[#e59b3f] text-sm font-bold font-nunito tracking-wide">Mom's Support</div>
                        <h2 class="font-fredoka text-3xl sm:text-4xl lg:text-5xl text-[#614DAC]">Mamina ASI Booster</h2>
                        <div class="text-base sm:text-lg text-[#4D4C4C] font-nunito space-y-4 max-w-md mx-auto lg:mx-0">
                            <p class="font-semibold text-[#528b89]">Pelancar ASI dari bahan Rimpang Alami</p>
                            <p class="leading-relaxed bg-white/60 p-4 rounded-2xl shadow-sm border border-white">Seduhan herbal dengan khasiat melancarkan ASI dengan komposisi 100% bahan alami, tanpa pemanis dan perisa tambahan yang mengganggu kesehatan.</p>
                            <div class="flex items-center gap-3 justify-center lg:justify-start">
                                <span class="px-3 py-1 bg-white rounded-full shadow-sm text-sm font-bold text-[#e59b3f]">Original</span>
                                <span class="px-3 py-1 bg-white rounded-full shadow-sm text-sm font-bold text-[#42b883]">Jeruk Nipis</span>
                                <span class="px-3 py-1 bg-white rounded-full shadow-sm text-sm font-bold text-[#ef4444]">Belimbing Wuluh</span>
                            </div>
                        </div>
                        <div class="pt-4">
                            <a href="https://shopee.co.id/maminast0re#product_list" target="_blank"
                                class="inline-flex items-center justify-center px-8 py-3.5 bg-[#e59b3f] text-white text-base font-bold font-nunito rounded-2xl hover:bg-[#f3b567] hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300">
                                Beli Mamina Sekarang
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                            </a>
                        </div>
                    </div>
                    <!-- Image (Right on Desktop) -->
                    <div class="order-1 lg:order-2 flex justify-center relative">
                        <div class="absolute inset-0 bg-[#ffe8b3]/50 rounded-full blur-3xl transform scale-75 animate-pulse" style="animation-delay: 1s;"></div>
                        <div class="w-56 h-56 sm:w-72 sm:h-72 lg:w-96 lg:h-96 bg-white/60 backdrop-blur-md rounded-[3rem] shadow-2xl p-6 sm:p-10 border border-white flex items-center justify-center transform hover:-rotate-2 transition-transform duration-500 relative z-10">
                            <img src="{{ asset('images/mamina.png') }}" alt="Mamina ASI Booster" class="w-full h-full object-contain hover:scale-110 transition-transform duration-500">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Nyam! -->
        <div class="relative px-4 sm:px-6 lg:px-8 my-12 sm:my-20">
            <!-- Background Decoration -->
            <div class="absolute left-0 top-1/2 -translate-y-1/2 w-[90%] md:w-[70%] h-[120%] bg-gradient-to-r from-[#fff0f4] to-transparent rounded-r-[100px] z-0 pointer-events-none"></div>
            
            <div class="relative z-10 max-w-6xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <!-- Image -->
                    <div class="order-1 flex justify-center relative">
                        <div class="absolute inset-0 bg-[#ffb3c6]/40 rounded-full blur-3xl transform scale-75 animate-pulse" style="animation-delay: 2s;"></div>
                        <div class="w-56 h-56 sm:w-72 sm:h-72 lg:w-96 lg:h-96 bg-white/60 backdrop-blur-md rounded-[3rem] shadow-2xl p-6 sm:p-10 border border-white flex items-center justify-center transform hover:rotate-2 transition-transform duration-500 relative z-10">
                            <img src="{{ asset('images/nyam.png') }}" alt="Nyam" class="w-full h-full object-contain hover:scale-110 transition-transform duration-500">
                        </div>
                    </div>
                    <!-- Text -->
                    <div class="order-2 text-center lg:text-left space-y-6">
                        <div class="inline-block px-4 py-1.5 rounded-full bg-[#fce8ef] text-[#ef4444] text-sm font-bold font-nunito tracking-wide">Baby Food</div>
                        <h2 class="font-fredoka text-3xl sm:text-4xl lg:text-5xl text-[#614DAC]">Nyam!</h2>
                        <div class="bg-white/50 backdrop-blur-sm p-6 rounded-3xl border border-white shadow-sm max-w-md mx-auto lg:mx-0">
                            <p class="text-base sm:text-lg text-[#4D4C4C] font-nunito leading-relaxed">
                                Dibuat menggunakan berbagai bahan pilihan dan berkualitas tinggi yang diolah dengan tangan kreatif dari seorang Ibu, sekaligus praktisi kesehatan.
                            </p>
                            <div class="mt-4 flex items-center text-[#ef4444] font-bold">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path></svg>
                                MPASI Sehat & Lezat
                            </div>
                        </div>
                        <div class="pt-4">
                            <a href="https://shopee.co.id/nyambabyfood#product_list" target="_blank"
                                class="inline-flex items-center justify-center px-8 py-3.5 bg-[#ef4444] text-white text-base font-bold font-nunito rounded-2xl hover:bg-[#f87171] hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300">
                                Pesan Nyam! Disini
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                            </a>
                        </div>
                    </div>
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
