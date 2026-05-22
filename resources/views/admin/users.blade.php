@extends('layout.app')

@section('page_title', 'Manajemen Otoritas Pengguna')

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

<div id="customDeleteModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[10000] flex items-center justify-center p-4 transition-all duration-300">
    <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-gray-100 transform scale-95 transition-all duration-300 space-y-4">
        <div class="flex flex-col items-center text-center space-y-3">
            <div class="w-12 h-12 bg-red-50 text-red-600 rounded-full flex items-center justify-center border border-red-100 text-xl shadow-sm">
                <i class="fa-solid fa-user-slash animate-pulse"></i>
            </div>
            <div class="space-y-1">
                <h4 class="text-base font-bold text-gray-900">Hapus Akun Pengguna?</h4>
                <p class="text-xs text-gray-500 leading-relaxed">Apakah Anda yakin ingin menghapus akun pengguna ini secara permanen dari pusat basis data sistem?</p>
            </div>
        </div>
        <div class="flex items-center gap-3 pt-2">
            <button onclick="closeDeleteModal()" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2.5 rounded-xl border border-gray-200 transition focus:outline-none">
                Batal
            </button>
            <button id="confirmDeleteButton" class="w-full bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-2.5 rounded-xl transition shadow-md shadow-red-600/10 focus:outline-none">
                Ya, Hapus Akun
            </button>
        </div>
    </div>
</div>

<div class="space-y-6">
    <div id="mainUserDashboardView" class="space-y-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <p class="text-sm text-gray-600 font-medium">Kelola hak akses staf lapangan operasional dan administrator Greenfields.</p>
            
            <div class="flex items-center gap-3 flex-shrink-0 w-full sm:w-auto justify-end">
                <a href="{{ route('admin.users.export') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg text-sm shadow transition flex items-center gap-2 whitespace-nowrap">
                    <i class="fa-solid fa-file-excel"></i> Download Excel
                </a>

                <button onclick="openCreateMode()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2 rounded-lg text-sm shadow transition flex items-center gap-2 whitespace-nowrap">
                    <i class="fa-solid fa-user-plus"></i> Tambah Pengguna Baru
                </button>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 max-w-xs">
            <label for="filterRole" class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Saring Menurut Klasifikasi Peran</label>
            <select id="filterRole" onchange="filterUserTable()" class="w-full mt-1.5 p-2 bg-slate-50 border border-gray-300 rounded-lg text-xs font-bold focus:outline-emerald-600 text-gray-700">
                <option value="ALL">Semua Peran Akun</option>
                <option value="admin">Administrator</option>
                <option value="user">User Staf Lapangan</option>
            </select>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="w-full text-left border-collapse" id="userManagementTable">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-4">Nama Lengkap</th>
                        <th class="px-6 py-4">Email Akun</th>
                        <th class="px-6 py-4">Peran Hak Akses</th>
                        <th class="px-6 py-4">Waktu Pendaftaran</th>
                        <th class="px-6 py-4 text-center">Tindakan Otoritas</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-700 divide-y divide-gray-100">
                    @foreach($users as $user)
                        <tr class="user-data-row hover:bg-gray-50/50 transition" data-role="{{ $user->role }}">
                            <td class="px-6 py-4 font-bold text-gray-900">{{ $user->name }}</td>
                            <td class="px-6 py-4 text-gray-600 font-medium">{{ $user->email }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 text-[10px] font-bold rounded-md uppercase tracking-wider {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-blue-100 text-blue-800 border border-blue-200' }}">
                                    {{ $user->role }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-400 text-xs whitespace-nowrap">
                                {{ isset($user->created_at) ? \Carbon\Carbon::parse($user->created_at)->translatedFormat('d-m-Y H:i') : '-' }}
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-4">
                                    <button type="button" 
                                            onclick="openEditMode('{{ $user->id }}', '{{ $user->name }}', '{{ $user->email }}', '{{ $user->role }}')" 
                                            class="text-xs font-bold text-emerald-600 hover:text-emerald-800 hover:underline">
                                        Edit
                                    </button>
                                    
                                    <form id="delete-user-form-{{ $user->id }}" action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="m-0">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" onclick="triggerDeleteModal(event, 'delete-user-form-{{ $user->id }}')" class="text-xs font-bold text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-2 py-1 rounded border border-red-100 transition">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div id="userFormReportingView" class="hidden space-y-6 max-w-xl">
        <div class="flex items-center gap-4">
            <button type="button" onclick="closeFormMode()" class="flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-gray-900 bg-white border border-gray-300 px-3 py-2 rounded-lg shadow-sm hover:bg-gray-50 transition">
                <i class="fa-solid fa-arrow-left"></i> Back
            </button>
            <h3 id="formPanelTitle" class="text-base font-bold text-gray-800 uppercase tracking-wider">Formulir Tambah Pengguna</h3>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <form id="dynamicUserForm" action="{{ route('admin.users.store') }}" method="POST" class="space-y-4">
                @csrf
                <div id="methodContainer"></div> 
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider">Nama Lengkap Karyawan</label>
                    <input type="text" id="inputName" name="name" required max="100" placeholder="Masukkan nama lengkap..." class="w-full mt-1.5 p-2.5 border border-gray-300 rounded-lg text-sm focus:outline-emerald-600">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider">Alamat Email Resmi Perusahaan</label>
                    <input type="email" id="inputEmail" name="email" required max="150" placeholder="nama@greenfields.co.id" class="w-full mt-1.5 p-2.5 border border-gray-300 rounded-lg text-sm focus:outline-emerald-600">
                </div>

                <div id="passwordFieldWrapper">
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider">Kata Sandi Akun Baru (Password)</label>
                    <input type="password" id="inputPassword" name="password" placeholder="Minimal 6 karakter..." class="w-full mt-1.5 p-2.5 border border-gray-300 rounded-lg text-sm focus:outline-emerald-600">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider">Hak Akses Sistem (Peran)</label>
                    <select id="inputRole" name="role" required class="w-full mt-1.5 p-2.5 bg-slate-50 border border-gray-300 rounded-lg text-sm focus:outline-emerald-600 font-semibold text-gray-800">
                        <option value="user">User (Staf Operasional Lapangan)</option>
                        <option value="admin">Admin (Otoritas Penuh Manajemen)</option>
                    </select>
                </div>

                <div class="pt-2">
                    <button type="submit" id="submitFormBtn" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white p-3 rounded-lg font-bold text-sm shadow transition">
                        Simpan Data Pengguna
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Variabel global untuk merekam ID Form Target yang akan dieksekusi hapus
    let currentDeleteFormId = null;

    // FUNGSI MEMBUKA CUSTOM MODAL DELETE
    function triggerDeleteModal(event, formId) {
        event.preventDefault();
        event.stopPropagation();
        
        currentDeleteFormId = formId; // Simpan ID form target
        
        const modal = document.getElementById('customDeleteModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // Animasi pop-up membesar lembut (Scale Effect)
        setTimeout(() => {
            modal.querySelector('.transform').classList.replace('scale-95', 'scale-100');
        }, 10);
    }

    // FUNGSI MENUTUP CUSTOM MODAL DELETE
    function closeDeleteModal() {
        const modal = document.getElementById('customDeleteModal');
        modal.querySelector('.transform').classList.replace('scale-100', 'scale-95');
        
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            currentDeleteFormId = null;
        }, 150);
    }

    // Hubungkan aksi klik tombol konfirmasi di modal ke submit form asli Laravel
    document.getElementById('confirmDeleteButton').addEventListener('click', function() {
        if (currentDeleteFormId) {
            document.getElementById(currentDeleteFormId).submit();
        }
    });

    // AUTOMATION TIMEOUT UNTUK TOAST NOTIFICATION (SUCCESS & ERROR)
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

    function openCreateMode() {
        document.getElementById('mainUserDashboardView').classList.add('hidden');
        document.getElementById('userFormReportingView').classList.remove('hidden');
        document.getElementById('formPanelTitle').innerText = "Formulir Tambah Pengguna Baru";
        document.getElementById('submitFormBtn').innerText = "Simpan Data Pengguna Baru";
        
        document.getElementById('dynamicUserForm').action = "{{ route('admin.users.store') }}";
        document.getElementById('methodContainer').innerHTML = "";
        document.getElementById('inputName').value = "";
        document.getElementById('inputEmail').value = "";
        document.getElementById('inputPassword').value = "";
        document.getElementById('inputRole').value = "user";
        
        document.getElementById('passwordFieldWrapper').classList.remove('hidden');
        document.getElementById('inputPassword').setAttribute('required', 'required');
    }

    function openEditMode(id, name, email, role) {
        document.getElementById('mainUserDashboardView').classList.add('hidden');
        document.getElementById('userFormReportingView').classList.remove('hidden');
        document.getElementById('formPanelTitle').innerText = "Formulir Sunting / Edit Data Pengguna";
        document.getElementById('submitFormBtn').innerText = "Perbarui Data Akun";
        
        document.getElementById('dynamicUserForm').action = `/admin/users/${id}`;
        document.getElementById('methodContainer').innerHTML = `<input type="hidden" name="_method" value="PUT">`;
        
        document.getElementById('inputName').value = name;
        document.getElementById('inputEmail').value = email;
        document.getElementById('inputRole').value = role;
        
        document.getElementById('passwordFieldWrapper').classList.add('hidden');
        document.getElementById('inputPassword').removeAttribute('required');
    }

    function closeFormMode() {
        document.getElementById('userFormReportingView').classList.add('hidden');
        document.getElementById('mainUserDashboardView').classList.remove('hidden');
    }

    window.addEventListener('click', function(event) {
        // Menutup modal jika area backdrop blur di luar kotak putih diklik
        const modal = document.getElementById('customDeleteModal');
        if (event.target === modal) {
            closeDeleteModal();
        }
    });

    function filterUserTable() {
        const selectedRole = document.getElementById('filterRole').value;
        const rows = document.querySelectorAll('.user-data-row');

        rows.forEach(row => {
            if (selectedRole === 'ALL' || row.getAttribute('data-role') === selectedRole) {
                row.classList.remove('hidden');
            } else {
                row.classList.add('hidden');
            }
        });
    }
</script>
@endsection