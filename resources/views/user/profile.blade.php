@extends('layout.app')

@section('page_title', 'Pengaturan Profil Akun Anda')

@section('content')
<div id="toastContainer" class="fixed top-5 right-5 z-[9999] pointer-events-none flex flex-col gap-3">
    @if(session('success'))
        <div id="flashSuccess" class="pointer-events-auto bg-green-600 text-white px-5 py-3 rounded-xl text-sm font-bold shadow-2xl transition-all duration-500 transform translate-y-0 opacity-100 flex items-center gap-2 border border-green-500/30 min-w-[300px]">
            <i class="fa-solid fa-circle-check text-base flex-shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div id="flashError" class="pointer-events-auto bg-red-600 text-white px-5 py-3 rounded-xl text-sm font-bold shadow-2xl transition-all duration-500 transform translate-y-0 opacity-100 flex items-center gap-2 border border-red-500/30 min-w-[300px]">
            <i class="fa-solid fa-circle-exclamation text-base flex-shrink-0"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif
</div>

<div class="max-w-6xl mx-auto space-y-6">

    <div id="uploadPhotoModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[10000] flex items-center justify-center p-4 transition-all duration-300">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-100 transform scale-95 transition-all duration-300 space-y-4">
            <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                <h4 class="text-base font-bold text-gray-900">Perbarui Foto Profil</h4>
                <button type="button" onclick="closeUploadModal()" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="{{ route('profile.upload_photo') }}" method="POST" enctype="multipart/form-data" class="space-y-4 m-0">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Pilih Berkas Foto Baru</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-xl hover:border-emerald-500 transition-colors cursor-pointer relative bg-gray-50/50">
                        <div class="space-y-2 text-center pointer-events-none">
                            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mx-auto text-xl border border-emerald-100">
                                <i class="fa-solid fa-cloud-arrow-up animate-bounce"></i>
                            </div>
                            <div class="text-xs text-gray-600">
                                <span class="font-bold text-emerald-600 hover:underline">Pilih File Gambar</span> atau seret ke sini
                            </div>
                            <p class="text-[10px] text-gray-400 font-medium">Mendukung format PNG, JPG, JPEG (Maks. 2MB)</p>
                        </div>
                        <input type="file" name="profile_photo" required accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="previewSelectedFile(event)">
                    </div>
                </div>

                <div id="filePreviewArea" class="hidden text-xs bg-slate-50 border border-gray-200 p-2.5 rounded-lg font-medium text-gray-700 items-center gap-2">
                    <i class="fa-solid fa-image text-emerald-600 text-sm"></i>
                    <span id="fileNameTarget" class="truncate flex-1">nama_file.png</span>
                </div>

                <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
                    <button type="button" onclick="closeUploadModal()" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2.5 rounded-xl border border-gray-200 transition">
                        Batal
                    </button>
                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-2.5 rounded-xl transition shadow-md shadow-emerald-600/10">
                        Unggah & Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch">
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col justify-between">
            <div class="p-6 bg-gray-50 border-b border-gray-200">
                <h3 class="text-base font-bold text-gray-900">Identitas Karyawan</h3>
                <p class="text-xs text-gray-500">Ringkasan visual dan statistik hak akses sistem operasional.</p>
            </div>

            <div class="p-6 flex-1 flex flex-col items-center justify-center space-y-5">
                <div onclick="openUploadModal()" class="relative group cursor-pointer select-none">
                    @if(isset($user->avatar) && $user->avatar != null)
                        <img src="{{ asset('storage/avatars/' . $user->avatar) }}" class="w-24 h-24 rounded-full object-cover shadow-md ring-4 ring-emerald-50 transition-all duration-300 group-hover:scale-105">
                    @else
                        <div class="w-24 h-24 bg-emerald-800 text-white rounded-full flex items-center justify-center text-2xl font-bold shadow-md ring-4 ring-emerald-50 transition-all duration-300 group-hover:scale-105">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                    @endif
                    <div class="absolute inset-0 bg-slate-900/50 text-white rounded-full opacity-0 group-hover:opacity-100 flex items-center justify-center transition-all duration-300 scale-100 group-hover:scale-105 shadow-md">
                        <div class="text-center">
                            <i class="fa-solid fa-camera text-xl block mb-0.5 animate-pulse"></i>
                            <span class="text-[9px] font-bold uppercase tracking-wider block">Ganti Foto</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-1 text-center">
                    <h4 class="text-base font-bold text-gray-900 leading-tight">{{ $user->name }}</h4>
                    <p class="text-xs text-gray-500 font-medium break-all">{{ $user->email }}</p>
                </div>

                <div class="w-full pt-1">
                    <span class="inline-block w-full px-3 py-2 text-[10px] font-bold rounded-xl uppercase tracking-wider text-center border {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-800 border-purple-200' : 'bg-blue-100 text-blue-800 border-blue-200' }}">
                        <i class="{{ $user->role === 'admin' ? 'fa-solid fa-user-shield' : 'fa-solid fa-user-gear' }} mr-1.5 text-xs"></i>
                        {{ $user->role }} Otoritas
                    </span>
                </div>
            </div>

            <div class="p-4 bg-gray-50/50 border-t border-gray-100 text-left space-y-2">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Statistik Pusat Data</div>
                <div class="flex justify-between items-center text-xs text-gray-600 font-medium">
                    <span>Tanggal Registrasi Sesi:</span>
                    <span class="font-bold text-gray-800">{{ isset($user->created_at) ? date('d M Y', strtotime($user->created_at)) : '-' }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden lg:col-span-2 flex flex-col justify-between">
            <div class="p-6 bg-gray-50 border-b border-gray-200">
                <h3 class="text-base font-bold text-gray-900">Formulir Pembaruan Kredensial</h3>
                <p class="text-xs text-gray-500">Ubah konfigurasi identitas nama, email resmi, dan proteksi kata sandi akun Anda.</p>
            </div>

            <div class="p-6 flex-1">
                <form action="{{ route('profile.update') }}" method="POST" class="space-y-5 m-0">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider">Nama Lengkap</label>
                        <input type="text" name="name" required max="100" value="{{ old('name', $user->name) }}" class="w-full mt-1.5 p-2.5 border border-gray-300 rounded-lg text-sm focus:outline-emerald-600 placeholder-gray-400 font-medium text-gray-800">
                        @error('name') <p class="text-red-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider">Alamat Email Resmi Perusahaan</label>
                        <input type="email" name="email" required max="150" value="{{ old('email', $user->email) }}" class="w-full mt-1.5 p-2.5 border border-gray-300 rounded-lg text-sm focus:outline-emerald-600 placeholder-gray-400 font-medium text-gray-800">
                        @error('email') <p class="text-red-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                    </div>

                    <div class="border-t border-gray-100 pt-4">
                        <p class="text-xs text-emerald-800 font-medium bg-emerald-50/70 p-3 border border-emerald-200/50 rounded-lg mb-2 flex items-start gap-2.5">
                            <i class="fa-solid fa-circle-info text-base text-emerald-600 mt-0.5 flex-shrink-0"></i>
                            <span>Biarkan kolom kata sandi di bawah ini **tetap kosong** jika Anda tidak berniat untuk mengganti pasword log masuk sistem saat ini.</span>
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider">Kata Sandi Baru (Password Baru)</label>
                            <input type="password" name="password" placeholder="Isi hanya jika ingin ganti..." class="w-full mt-1.5 p-2.5 border border-gray-300 rounded-lg text-sm focus:outline-emerald-600">
                            @error('password') <p class="text-red-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider">Konfirmasi Kata Sandi Baru</label>
                            <input type="password" name="password_confirmation" placeholder="Ulangi input sandi baru..." class="w-full mt-1.5 p-2.5 border border-gray-300 rounded-lg text-sm focus:outline-emerald-600">
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-100 flex justify-end">
                        <button type="submit" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-lg font-bold text-sm shadow transition-all duration-150 flex items-center justify-center gap-2">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan Profil
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const successNotif = document.getElementById('flashSuccess');
        const errorNotif = document.getElementById('flashError');
        
        if (successNotif) {
            setTimeout(() => {
                successNotif.classList.replace('opacity-100', 'opacity-0');
                successNotif.classList.replace('translate-y-0', '-translate-y-4');
                setTimeout(() => successNotif.remove(), 500);
            }, 3000);
        }
        
        if (errorNotif) {
            setTimeout(() => {
                errorNotif.classList.replace('opacity-100', 'opacity-0');
                errorNotif.classList.replace('translate-y-0', '-translate-y-4');
                setTimeout(() => errorNotif.remove(), 500);
            }, 3000);
        }
    });

    function openUploadModal() {
        const modal = document.getElementById('uploadPhotoModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        setTimeout(() => {
            modal.querySelector('.transform').classList.replace('scale-95', 'scale-100');
        }, 10);
    }

    function closeUploadModal() {
        const modal = document.getElementById('uploadPhotoModal');
        modal.querySelector('.transform').classList.replace('scale-100', 'scale-95');
        
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.getElementById('filePreviewArea').classList.replace('flex', 'hidden');
        }, 150);
    }

    function previewSelectedFile(event) {
        const input = event.target;
        const previewArea = document.getElementById('filePreviewArea');
        const nameTarget = document.getElementById('fileNameTarget');
        
        if (input.files && input.files[0]) {
            nameTarget.innerText = input.files[0].name;
            previewArea.classList.remove('hidden');
            previewArea.classList.add('flex');
        }
    }

    window.addEventListener('click', function(event) {
        const modal = document.getElementById('uploadPhotoModal');
        if (event.target === modal) {
            closeUploadModal();
        }
    });
</script>
@endsection