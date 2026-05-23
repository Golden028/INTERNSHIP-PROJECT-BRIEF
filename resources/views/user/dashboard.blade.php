@extends('layout.app')

@section('page_title', 'Dashboard Staf Lapangan')

@section('content')
<div class="space-y-6">
    {{-- Header Sambutan --}}
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-extrabold text-gray-800">Selamat Datang, {{ auth()->user()->name }}!</h2>
            <p class="text-sm text-gray-500 mt-1">Sistem operasional Greenfields siap digunakan.</p>
        </div>
        <div class="hidden md:flex w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl items-center justify-center text-2xl border border-emerald-100 shadow-inner">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
    </div>

    {{-- Aksi Cepat (Dibuat sedikit lebih ramping agar proporsional) --}}
    <div class="max-w-2xl">
        <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 p-6 rounded-2xl shadow-lg text-white flex items-center justify-between transform hover:scale-[1.01] transition-all duration-300">
            <div>
                <h3 class="text-lg font-bold">Laporkan Insiden</h3>
                <p class="text-emerald-50 text-xs mt-1">Klik di sini untuk mencatat anomali produksi baru.</p>
            </div>
            <button onclick="window.location.href='{{ route('incidents.index') }}'" 
                    class="bg-white text-emerald-700 px-6 py-2.5 rounded-lg text-sm font-bold shadow-md hover:bg-emerald-50 transition shrink-0">
                Mulai Catat
            </button>
        </div>
    </div>
</div>
@endsection