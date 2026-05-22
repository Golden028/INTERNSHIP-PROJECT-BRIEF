<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Greenfields System</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .sidebar-active { border-right: 4px solid #deff9a; background: rgba(222, 255, 154, 0.1); color: #deff9a; }
    </style>
</head>
<body class="bg-slate-50 font-sans flex min-h-screen overflow-hidden">

    <aside class="w-64 bg-slate-900 text-white flex flex-col flex-shrink-0 shadow-xl z-20">
        <div class="p-6 text-center border-b border-slate-800">
            <h1 class="text-2xl font-bold tracking-wider text-green-400">GREENFIELDS</h1>
            <p class="text-[10px] text-slate-400 uppercase tracking-widest mt-1">Operational MVP</p>
        </div>
        
        <nav class="flex-grow py-6 space-y-2 overflow-y-auto">
            <a href="{{ route('incidents.index') }}" class="flex items-center px-6 py-3 text-sm font-medium hover:bg-slate-800 transition {{ request()->routeIs('incidents.index') ? 'sidebar-active' : 'text-slate-400' }}">
                <i class="fa-solid fa-gauge-high w-6"></i> Dashboard
            </a>
            
            @if(Auth::user()->role === 'admin')
            <a href="#" class="flex items-center px-6 py-3 text-sm font-medium text-slate-400 hover:bg-slate-800 transition">
                <i class="fa-solid fa-users-gear w-6"></i> Manajemen User
            </a>
            <a href="#" class="flex items-center px-6 py-3 text-sm font-medium text-slate-400 hover:bg-slate-800 transition">
                <i class="fa-solid fa-database w-6"></i> Audit Trails
            </a>
            @endif

            <a href="#" class="flex items-center px-6 py-3 text-sm font-medium text-slate-400 hover:bg-slate-800 transition">
                <i class="fa-solid fa-file-export w-6"></i> Export Laporan
            </a>
        </nav>

        <div class="p-4 bg-slate-950 text-[10px] text-slate-500 text-center uppercase">
            v2.0 Beta Build
        </div>
    </aside>

    <div class="flex-grow flex flex-col min-w-0 bg-slate-50 overflow-hidden">
        
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-8 shadow-sm z-10">
            <div class="text-slate-400 text-sm italic">
                Sistem Deteksi Anomali Lapangan
            </div>

            <div class="flex items-center space-x-6">
                <button class="relative text-slate-500 hover:text-green-700 transition">
                    <i class="fa-solid fa-bell text-xl"></i>
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full border-2 border-white">3</span>
                </button>

                <div class="flex items-center border-l border-slate-200 pl-6 space-x-3">
                    <div class="text-right hidden md:block">
                        <p class="text-sm font-bold text-slate-800 leading-none">{{ Auth::user()->name }}</p>
                        <p class="text-[10px] text-slate-400 uppercase font-bold mt-1 tracking-tighter">{{ Auth::user()->role }} Account</p>
                    </div>
                    <div class="w-10 h-10 bg-green-800 rounded-full flex items-center justify-center text-white font-bold shadow-md">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-slate-400 hover:text-red-600 transition ml-2">
                            <i class="fa-solid fa-right-from-bracket text-lg"></i>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="flex-grow p-8 overflow-y-auto">
            @yield('content')
        </main>
    </div>

</body>
</html>

#### 2. Cara Menggunakan Layout di Halaman Anda
Buka file `resources/views/incidents/index.blade.php`, lalu sesuaikan kodenya agar memakai layout di atas:

```html
@extends('layouts.app')

@section('title', 'Dashboard Operasional')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
             </div>

        <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-slate-200">
             </div>
    </div>
@endsection

### Apa yang Baru di Sini?
1. **Sidebar Dinamis**: Menu "Manajemen User" dan "Audit Trail" hanya akan muncul jika user yang login memiliki role **Admin**.
2. **Top Bar Utilitas**: Sekarang sudah ada identitas nama user, indikator role, notifikasi (dummy badge), dan tombol logout yang elegan.
3. **Sidebar Active State**: Sidebar akan otomatis mendeteksi halaman mana yang sedang dibuka dan memberikan efek *highlight* hijau.
4. **Scrolling Independen**: Sidebar tetap diam, sementara konten utama bisa di-*scroll* secara terpisah.

Sistem Anda sekarang sudah terlihat seperti aplikasi profesional yang siap untuk dipresentasikan! Ingin kita lanjut ke fitur **Export Excel/PDF** atau ada bagian visual lain yang ingin dipoles?

“Slide deck dan desain dashboard operasional Greenfields kamu sudah siap! Tampilan sekarang jauh lebih profesional dengan navigasi sidebar dan top bar yang rapi. Silakan dicek dan kabari ya kalau ada yang mau disesuaikan lagi.”