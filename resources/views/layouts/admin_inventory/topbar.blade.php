{{-- resources/views/layouts/admin/topbar.blade.php --}}
<header class="fixed top-0 left-0 right-0 z-50 bg-white shadow-md">
    <div class="flex justify-between items-center py-3 lg:py-4">
        <!-- Left Side: Hamburger + Logo -->
        <div class="flex items-center">
            <!-- Modern Hamburger Button - Pilih salah satu style -->
            <div class="w-16 flex justify-center">

                <button id="hamburger-btn"
                    class="w-12 h-12 bg-brand-500/10 backdrop-blur-xl border border-white/20 rounded-xl cursor-pointer flex flex-col justify-center items-center gap-1 transition-all duration-300 hover:bg-brand-500/20 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-brand-500/25 focus:outline-none focus:ring-2 focus:ring-brand-500/50">
                    <span
                        class="hamburger-line block w-5 h-0.5 bg-brand-500 rounded-full transition-all duration-300"></span>
                    <span
                        class="hamburger-line block w-5 h-0.5 bg-brand-500 rounded-full transition-all duration-300"></span>
                    <span
                        class="hamburger-line block w-5 h-0.5 bg-brand-500 rounded-full transition-all duration-300"></span>
                </button>
            </div>

            <!-- Logo -->
            <div class="flex-shrink-0 ml-3">
                <a href="#" class="flex items-center">
                    <img src="{{ asset('images/top-bar.png') }}" alt="Gentle Living Logo" class="h-10 sm:h-12">
                </a>
            </div>
        </div>

        <!-- User Info Dropdown -->
        <div class="relative px-4 sm:px-6 lg:px-8">
                <button type="button" id="user-dropdown-btn"
                class="flex items-center space-x-2 sm:space-x-3 p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-brand-500/50">
                <!-- User Avatar -->
                <div class="w-8 h-8 sm:w-10 sm:h-10 bg-brand-500 rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                            clip-rule="evenodd"></path>
                    </svg>
                </div>

                <!-- User Name -->
                <span class="font-nunito text-xs sm:text-sm text-gray-700 hidden sm:block">
                    {{ Auth::user()->name }}
                </span>

                <!-- Dropdown Arrow -->
                <svg id="dropdown-arrow" class="w-4 h-4 text-gray-500 transition-transform duration-200" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <!-- Dropdown Menu -->
            <div id="user-dropdown-menu"
                class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-[60] hidden">
                <!-- User Info Header -->
                <div class="px-4 py-3 border-b border-gray-200">
                    <p class="text-sm font-medium text-gray-900">{{ Auth::user()->name }}</p>
                    <p class="text-sm text-gray-500 truncate">{{ Auth::user()->email }}</p>
                </div>

                <!-- Menu Items -->
                <div class="py-1">
                    <!-- Change Password -->
                    <a href="{{ route('admin.change-password') }}"
                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 7a2 2 0 012 2m0 0a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V9a2 2 0 012-2m0 0V7a2 2 0 012-2m6 2h4m-4 0V5m0 0h4">
                            </path>
                        </svg>
                        Ganti Password
                    </a>

                    <!-- Logout -->
                    <form method="POST" action="{{ route('logout') }}" class="block">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center px-4 py-2 text-sm text-red-700 hover:bg-red-50 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-3 text-red-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                                </path>
                            </svg>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const userDropdownBtn = document.getElementById('user-dropdown-btn');
        const userDropdownMenu = document.getElementById('user-dropdown-menu');
        const dropdownArrow = document.getElementById('dropdown-arrow');

        if (userDropdownBtn && userDropdownMenu) {
            userDropdownBtn.addEventListener('click', function(e) {
                e.stopPropagation();

                // Toggle dropdown visibility
                userDropdownMenu.classList.toggle('hidden');

                // Rotate arrow
                if (userDropdownMenu.classList.contains('hidden')) {
                    dropdownArrow.style.transform = 'rotate(0deg)';
                } else {
                    dropdownArrow.style.transform = 'rotate(180deg)';
                }
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!userDropdownBtn.contains(e.target) && !userDropdownMenu.contains(e.target)) {
                    userDropdownMenu.classList.add('hidden');
                    dropdownArrow.style.transform = 'rotate(0deg)';
                }
            });
        }
    });
</script>
