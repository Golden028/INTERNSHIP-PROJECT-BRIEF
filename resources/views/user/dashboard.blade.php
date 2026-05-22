@extends('layout.app')

@section('page_title', 'Operational Center Portal')

@section('content')
<div class="bg-white p-8 rounded-xl shadow-sm border border-gray-200 text-center max-w-3xl mx-auto mt-12">
    <div class="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center text-3xl mx-auto mb-4">
        <i class="fa-solid fa-building-user"></i>
    </div>
    <h2 class="text-xl font-bold text-gray-800">Selamat Datang di Sistem Operasional</h2>
    <p class="text-sm text-gray-500 mt-2">Silakan pilih menu <strong>Log Insiden</strong> di sidebar kiri untuk memantau status kendala atau melaporkan anomali baru yang ditemukan di area produksi.</p>
</div>
@endsection