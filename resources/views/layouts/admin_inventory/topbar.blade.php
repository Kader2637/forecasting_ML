{{-- resources/views/layouts/admin_inventory/topbar.blade.php --}}
<nav class="flex items-center justify-between px-6 py-3 lg:px-8 w-full max-w-7xl mx-auto h-16">
    
    <!-- Left Side: Hamburger & Search (Search is dummy for Sneat look) -->
    <div class="flex items-center gap-4 flex-1">
       
    </div>

    <!-- Right Side: Icons & Profile -->
    <div class="flex items-center gap-4 sm:gap-6">
        <!-- Notification icon dummy -->
        <button class="text-[#697a8d] hover:text-[#696cff] relative transition-colors">
            <i class="bi bi-bell text-lg"></i>
            <span class="absolute top-0 right-0 w-2 h-2 bg-[#ff3e1d] rounded-full border border-white"></span>
        </button>

        <!-- User Profile Dropdown -->
        <div class="relative">
            <button type="button" id="user-dropdown-btn" class="flex items-center focus:outline-none">
                <!-- Avatar with online indicator -->
                <div class="relative w-10 h-10">
                    <div class="w-full h-full rounded-full bg-[#e7e7ff] text-[#696cff] flex items-center justify-center font-bold text-sm">
                        {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <span class="absolute bottom-0 right-0 w-3 h-3 bg-[#71dd37] border-2 border-white rounded-full"></span>
                </div>
            </button>

            <!-- Dropdown Menu -->
            <div id="user-dropdown-menu" class="absolute right-0 mt-3 w-56 bg-white rounded-xl sneat-shadow border border-slate-100 z-50 hidden opacity-0 transition-opacity duration-200">
                <div class="p-3 border-b border-slate-100 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-[#e7e7ff] text-[#696cff] flex items-center justify-center font-bold text-sm shrink-0">
                        {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-[0.9375rem] font-semibold text-slate-700 truncate">{{ Auth::user()->name ?? 'User' }}</p>
                        <p class="text-xs text-slate-500 truncate">Admin</p>
                    </div>
                </div>

                <div class="p-2">
                    <a href="#" class="flex items-center px-3 py-2 text-[0.9375rem] text-[#697a8d] hover:bg-slate-50 hover:text-[#696cff] rounded-md transition-colors">
                        <i class="bi bi-person mr-3 text-lg"></i> My Profile
                    </a>
                    <a href="{{ route('admin.change-password') }}" class="flex items-center px-3 py-2 text-[0.9375rem] text-[#697a8d] hover:bg-slate-50 hover:text-[#696cff] rounded-md transition-colors">
                        <i class="bi bi-gear mr-3 text-lg"></i> Settings
                    </a>
                    
                    <div class="h-px bg-slate-100 my-2"></div>

                    <form method="POST" action="{{ route('logout') }}" class="block w-full">
                        @csrf
                        <button type="submit" class="w-full flex items-center px-3 py-2 text-[0.9375rem] text-[#ff3e1d] hover:bg-[#ff3e1d]/10 rounded-md transition-colors">
                            <i class="bi bi-power mr-3 text-lg"></i> Log Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const userDropdownBtn = document.getElementById('user-dropdown-btn');
        const userDropdownMenu = document.getElementById('user-dropdown-menu');

        if (userDropdownBtn && userDropdownMenu) {
            userDropdownBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                
                if (userDropdownMenu.classList.contains('hidden')) {
                    userDropdownMenu.classList.remove('hidden');
                    // slight delay for animation
                    setTimeout(() => {
                        userDropdownMenu.classList.remove('opacity-0');
                    }, 10);
                } else {
                    userDropdownMenu.classList.add('opacity-0');
                    setTimeout(() => {
                        userDropdownMenu.classList.add('hidden');
                    }, 200);
                }
            });

            document.addEventListener('click', function(e) {
                if (!userDropdownBtn.contains(e.target) && !userDropdownMenu.contains(e.target)) {
                    userDropdownMenu.classList.add('opacity-0');
                    setTimeout(() => {
                        userDropdownMenu.classList.add('hidden');
                    }, 200);
                }
            });
        }

        // Sidebar mobile toggle
        const hamburgerBtn = document.getElementById('hamburger-btn');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const sidebarCloseBtn = document.getElementById('sidebar-close-btn');

        function toggleSidebar() {
            if (sidebar.classList.contains('-translate-x-full')) {
                // Open
                sidebar.classList.remove('-translate-x-full');
                sidebarOverlay.classList.remove('hidden');
            } else {
                // Close
                sidebar.classList.add('-translate-x-full');
                sidebarOverlay.classList.add('hidden');
            }
        }

        if (hamburgerBtn) hamburgerBtn.addEventListener('click', toggleSidebar);
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', toggleSidebar);
        if (sidebarCloseBtn) sidebarCloseBtn.addEventListener('click', toggleSidebar);
    });
</script>