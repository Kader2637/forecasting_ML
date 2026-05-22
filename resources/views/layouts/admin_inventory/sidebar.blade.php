{{-- sidebar.blade.php --}}
<!-- Mobile Overlay -->
<div id="sidebar-overlay"
    class="fixed inset-0 bg-black bg-opacity-50 z-20 lg:hidden hidden transition-opacity duration-300 ease-in-out">
</div>

<!-- Sidebar -->
<div id="sidebar"
    class="sidebar-mini fixed top-16 left-0 h-[calc(100vh-4rem)] w-16 bg-brand-500 shadow-lg transform lg:transform-none -translate-x-full lg:translate-x-0 transition-all duration-300 ease-in-out z-40 overflow-hidden">

    <!-- Navigation Menu -->
    <nav class="mt-6 px-2 pb-16 overflow-y-auto h-full">
        {{-- Role Badge --}}
        @if(Auth::user() && method_exists(Auth::user(), 'hasRole'))
            <div class="sidebar-text mb-4 px-3 hidden">
                <div class="text-center py-2 rounded-lg bg-teal-600/20 border border-teal-500/30">
                    <div class="text-xs text-gray-300 mb-1">Role:</div>
                    @if(Auth::user()->hasRole('owner'))
                        <span class="inline-block px-2 py-1 bg-yellow-500 text-white text-xs font-semibold rounded-full">Owner</span>
                    @elseif(Auth::user()->hasRole('admin_inventory'))
                        <span class="inline-block px-2 py-1 bg-orange-500 text-white text-xs font-semibold rounded-full">Inventory Admin</span>
                    @elseif(Auth::user()->hasRole('production_team'))
                        <span class="inline-block px-2 py-1 bg-cyan-500 text-white text-xs font-semibold rounded-full">Production Team</span>
                    @else
                        <span class="inline-block px-2 py-1 bg-gray-500 text-white text-xs font-semibold rounded-full">Admin</span>
                    @endif
                </div>
            </div>
        @endif

        <ul class="space-y-1">
            {{-- Dashboard - Owner, Admin Inventory, Production Team --}}
            @if(Auth::user() && method_exists(Auth::user(), 'hasRole') && (Auth::user()->hasRole('owner') || Auth::user()->hasRole('admin_inventory') || Auth::user()->hasRole('production_team')))
                <!-- Dashboard -->
                <li class="relative">
                    <a href="{{ route('admin.inventory.dashboard') }}"
                        class="nav-item group flex items-center justify-start px-3 py-3 rounded-lg transition-all duration-200
                        {{ request()->routeIs('admin.inventory.dashboard') ? 'bg-teal-500 text-white shadow-sm' : 'text-gray-300 hover:bg-teal-500 hover:text-white' }}">
                        <x-heroicon-s-home class="nav-icon w-5 h-5 flex-shrink-0" />
                        <span class="sidebar-text font-medium ml-3 hidden">Dashboard</span>
                    </a>
                </li>
            @endif

            <!-- Demand Forecasting -->
                <li class="relative">
                    <a href="{{ route('admin.inventory.forecasting.demand') }}"
                        class="nav-item group flex items-center justify-start px-3 py-3 rounded-lg transition-all duration-200
                        {{ request()->routeIs('admin.inventory.forecasting.*') ? 'bg-purple-500 text-white shadow-sm' : 'text-gray-300 hover:bg-purple-500 hover:text-white' }}"
                        data-tooltip="Forecasting">
                        <span class="nav-icon text-xl flex-shrink-0"><i class="bi bi-graph-up"></i></span>
                        <span class="sidebar-text font-medium ml-3 hidden whitespace-nowrap">Forecasting</span>
                    </a>
                </li>

            {{-- Data Produk Jadi - Owner, Admin Inventory Only --}}
            @if(Auth::user() && method_exists(Auth::user(), 'hasRole') && (Auth::user()->hasRole('owner') || Auth::user()->hasRole('admin_inventory')))
                <!-- Data Produk Jadi -->
                <li class="relative">
                    <a href="{{ route('admin.inventory.finished-goods') }}"
                        class="nav-item group flex items-center justify-start px-3 py-3 rounded-lg transition-all duration-200
                        {{ request()->routeIs('admin.inventory.finished-goods') ? 'bg-red-500 text-white shadow-sm' : 'text-gray-300 hover:bg-red-500 hover:text-white' }}"
                        data-tooltip="Data Produk Jadi">
                        <span class="nav-icon text-xl flex-shrink-0"><i class="bi bi-box-seam"></i></span>
                        <span class="sidebar-text font-medium ml-3 hidden whitespace-nowrap">Produksi </span>
                    </a>
                </li>

                <!-- Buffer Stock Analysis -->
                <li class="relative">
                    <a href="{{ route('admin.inventory.buffer-stock.raw-materials') }}"
                        class="nav-item group flex items-center justify-start px-3 py-3 rounded-lg transition-all duration-200
                        {{ request()->routeIs('admin.inventory.buffer-stock.*') ? 'bg-blue-500 text-white shadow-sm' : 'text-gray-300 hover:bg-blue-500 hover:text-white' }}"
                        data-tooltip="Buffer Stock">
                        <span class="nav-icon text-xl flex-shrink-0"><i class="bi bi-bar-chart-line"></i></span>
                        <span class="sidebar-text font-medium ml-3 hidden whitespace-nowrap">Bahan Baku</span>
                    </a>
                </li>

                <!-- Stock Opname -->
                <li class="relative">
                    <a href="{{ route('admin.inventory.stock-opname') }}"
                        class="nav-item group flex items-center justify-start px-3 py-3 rounded-lg transition-all duration-200
                        {{ request()->routeIs('admin.inventory.stock-opname') ? 'bg-orange-500 text-white shadow-sm' : 'text-gray-300 hover:bg-orange-500 hover:text-white' }}"
                        data-tooltip="Stock Opname">
                        <span class="nav-icon text-xl flex-shrink-0"><i class="bi bi-clipboard-data"></i></span>
                        <span class="sidebar-text font-medium ml-3 hidden whitespace-nowrap">Stock Opname</span>
                    </a>
                </li>
            @endif

            {{-- Production Overview - Owner, Production Team Only --}}
            @if(Auth::user() && method_exists(Auth::user(), 'hasRole') && (Auth::user()->hasRole('owner') || Auth::user()->hasRole('production_team')))
                <!-- Production Overview -->
                <li class="relative">
                    <a href="{{ route('admin.inventory.production.overview') }}"
                        class="nav-item group flex items-center justify-start px-3 py-3 rounded-lg transition-all duration-200
                        {{ request()->routeIs('admin.inventory.production.*') ? 'bg-green-500 text-white shadow-sm' : 'text-gray-300 hover:bg-green-500 hover:text-white' }}"
                        data-tooltip="Production">
                        <span class="nav-icon text-xl flex-shrink-0"><i class="bi bi-tools"></i></span>
                        <span class="sidebar-text font-medium ml-3 hidden whitespace-nowrap">Production</span>
                    </a>
                </li>
            @endif
        </ul>
    </nav>
</div>