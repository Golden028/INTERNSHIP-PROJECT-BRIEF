<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Greenfields Operational MVP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes pulse-red {
            0%, 100% { background-color: rgba(254, 226, 226, 1); }
            50% { background-color: rgba(254, 202, 202, 1); }
        }
        .highlight-critical {
            animation: pulse-red 2s infinite;
            border-left: 6px solid #dc2626;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans flex h-screen overflow-hidden">

    <aside class="w-64 bg-slate-900 text-white flex flex-col justify-between flex-shrink-0">
        <div>
            <div class="p-5 text-xl font-bold tracking-wider border-b border-slate-800 flex items-center gap-3">
                <i class="fa-solid fa-leaf text-emerald-400"></i>
                <span>Greenfields</span>
            </div>
            
            <nav class="p-4 space-y-2">
                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }} font-medium transition">
                        <i class="fa-solid fa-chart-line w-5"></i> Dashboard Admin
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.users.index') ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }} font-medium transition">
                        <i class="fa-solid fa-users w-5"></i> Kelola Pengguna
                    </a>
                @else
                    <a href="{{ route('incidents.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('incidents.dashboard') ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }} font-medium transition">
                        <i class="fa-solid fa-gauge-high w-5"></i> Dashboard User
                    </a>
                @endif

                <a href="{{ route('incidents.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('incidents.index') ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }} transition">
                    <i class="fa-solid fa-triangle-exclamation w-5"></i> Log Insiden
                </a>
            </nav>
        </div>

        <div class="p-4 border-t border-slate-800 text-xs text-slate-500 bg-slate-950 font-mono text-center">
            Role: <span class="text-emerald-400 font-bold uppercase">{{ auth()->user()->role }}</span>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <header class="bg-white shadow-sm border-b border-gray-200 h-16 flex items-center justify-between px-8 z-10 flex-shrink-0">
            <div class="text-md font-semibold text-gray-800">
                @yield('page_title', 'Operational Portal')
            </div>

            <div class="flex items-center gap-6">
                <button class="relative text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-bell text-xl"></i>
                    <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"></span>
                </button>

                <div class="flex items-center gap-3 border-l border-gray-200 pl-6">
                    <div class="w-8 h-8 rounded-full bg-emerald-800 text-white flex items-center justify-center font-bold text-xs">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <div class="leading-tight hidden sm:block text-left">
                        <div class="text-sm font-semibold text-gray-800">{{ auth()->user()->name }}</div>
                        <div class="text-[10px] text-gray-400 uppercase font-bold">{{ auth()->user()->role }}</div>
                    </div>
                </div>

                <form action="{{ route('logout') }}" method="POST" class="inline m-0">
                    @csrf
                    <button type="submit" class="text-gray-400 hover:text-red-600 p-2 transition" title="Logout">
                        <i class="fa-solid fa-power-off text-lg"></i>
                    </button>
                </form>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-8">
            @yield('content')
        </main>
    </div>

</body>
</html>