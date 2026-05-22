@extends('layout.app')

@section('page_title', 'Welcome back, Administrator')

@section('content')
<div class="bg-white p-8 rounded-xl shadow-sm border border-gray-200 text-center max-w-3xl mx-auto mt-12">
    <div class="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center text-3xl mx-auto mb-4">
        <i class="fa-solid fa-user-shield"></i>
    </div>
    <h2 class="text-xl font-bold text-gray-800">Panel Utama Admin Greenfields</h2>
    <p class="text-sm text-gray-500 mt-2">Gunakan menu navigasi di bilah samping kiri untuk mulai mengelola infrastruktur data, meninjau jejak audit, atau memvalidasi log kendala lapangan.</p>
</div>
@endsection