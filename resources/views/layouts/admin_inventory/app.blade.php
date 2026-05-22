<!DOCTYPE html>
<html class="scroll-smooth" lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin - Gentle Living')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-tab.png?v=1') }}">
    <meta property="og:image" content="{{ asset('images/logo-tab.png') }}">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="{{ asset('css/bootstrap-icons.min.css') }}">

    <!-- Vite CSS & JS (includes Tailwind) -->
    <script src="{{  asset('js/tailwind.js')  }}"></script>
    <style type="text/tailwindcss">
        @theme {
            --color-brand-50: #f0f9f9;
            --color-brand-100: #d9f2f1;
            --color-brand-200: #b7e6e4;
            --color-brand-300: #87d4d0;
            --color-brand-400: #56bbb6;
            --color-brand-500: #528b89;
            --color-brand-600: #446b6a;
            --color-brand-700: #3a5756;
            --color-brand-800: #324947;
            --color-brand-900: #2d3e3d;

            --color-success-50: #f0fdf4;
            --color-success-500: #22c55e;
            --color-success-600: #16a34a;

            --color-warning-50: #fffbeb;
            --color-warning-500: #f59e0b;
            --color-warning-600: #d97706;

            --color-danger-50: #fef2f2;
            --color-danger-500: #ef4444;
            --color-danger-600: #dc2626;

            --font-fredoka: "Fredoka One", cursive;
            --font-nunito: "Nunito", sans-serif;
            --font-instrument: "Instrument Sans", ui-sans-serif, system-ui, sans-serif;

            --shadow-gentle: 0 1px 3px 0 rgba(0, 0, 0, 0.1),
                0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-gentle-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1),
                0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
    </style>

    <!-- Admin Styles -->
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">

    <!-- Local Fonts -->
    <style>
        @font-face {
            font-family: 'Fredoka One';
            src: url('{{ asset('assets/fonts/fredoka-v17-latin/fredoka-v17-latin-regular.woff2') }}') format('woff2');
            font-weight: 400;
            font-display: swap;
        }
        @font-face {
            font-family: 'Nunito';
            src: url('{{ asset('assets/fonts/nunito-sans-v19-latin/nunito-sans-v19-latin-regular.woff2') }}') format('woff2');
            font-weight: 400;
            font-display: swap;
        }
    </style>

    <!-- JavaScript -->
    <script src="{{ asset('js/carousel.js') }}"></script>
    <script src="{{ asset('js/sidebar.js') }}"></script>
</head>

<body>
    {{-- Admin Top Bar --}}
    @include('layouts.admin_inventory.topbar')

    <!-- Sidebar -->
    @include('layouts.admin_inventory.sidebar')

    <!-- Success/Error Messages -->
    @if (session('success'))
        <div class="fixed top-16 sm:top-20 left-1/2 transform -translate-x-1/2 z-40 w-full max-w-md px-4">
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-3 rounded-r-lg shadow-lg"
                id="successAlert">
                <div class="flex items-center">
                    <x-heroicon-s-check-circle class="w-5 h-5 mr-2" />
                    <span style="font-family: 'Nunito', sans-serif;"
                        class="text-sm font-medium">{{ session('success') }}</span>
                </div>
            </div>
        </div>
        <script>
            setTimeout(function() {
                const alert = document.getElementById('successAlert');
                if (alert) {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translate(-50%, -100%)';
                    setTimeout(() => alert.remove(), 300);
                }
            }, 3000);
        </script>
    @endif

    {{-- Main Content --}}
    <main id="main-content" class="main-content transition-all duration-300 ease-in-out pt-16 lg:ml-16">
        @yield('content')
    </main>

    <!-- Footer Section -->
    <footer id="footer-content" class="main-content transition-all duration-300 ease-in-out lg:ml-16">
        @include('layouts.footer')
    </footer>

    <!-- Global Notification Container -->
    <div id="globalNotificationContainer" class="fixed top-20 right-4 z-[9999] space-y-3 max-w-md w-full"></div>

    <script>
        // Global notification system with Bootstrap Icons support
        function showNotification(message, type = 'info', duration = 3000) {
            const container = document.getElementById('globalNotificationContainer');
            const notification = document.createElement('div');
            
            let bgColor = 'bg-blue-100';
            let textColor = 'text-blue-800';
            let borderColor = 'border-blue-300';
            
            if (type === 'success') {
                bgColor = 'bg-emerald-100';
                textColor = 'text-emerald-800';
                borderColor = 'border-emerald-300';
            } else if (type === 'error') {
                bgColor = 'bg-red-100';
                textColor = 'text-red-800';
                borderColor = 'border-red-300';
            } else if (type === 'warning') {
                bgColor = 'bg-amber-100';
                textColor = 'text-amber-800';
                borderColor = 'border-amber-300';
            }
            
            notification.className = `${bgColor} ${textColor} ${borderColor} border rounded-lg shadow-lg p-4 animate-slide-in`;
            notification.innerHTML = message;
            notification.style.animation = 'slideIn 0.3s ease-in-out';
            
            container.appendChild(notification);
            
            if (duration > 0) {
                setTimeout(() => {
                    notification.style.animation = 'slideOut 0.3s ease-in-out forwards';
                    setTimeout(() => notification.remove(), 300);
                }, duration);
            }
        }

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(400px);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>

    @stack('scripts')
</body>

</html>
