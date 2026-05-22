@extends('layout.app')

@section('page_title', 'Manajemen Otoritas Pengguna')

@section('content')
<div class="space-y-6">
    
    @if(session('success'))
        <div id="flashSuccess" class="bg-green-600 text-white p-3 rounded-lg text-sm font-semibold shadow-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div id="flashError" class="bg-red-600 text-white p-3 rounded-lg text-sm font-semibold shadow-sm">{{ session('error') }}</div>
    @endif

    <div id="mainUserDashboardView" class="space-y-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <p class="text-sm text-gray-600 font-medium">Kelola hak akses staf lapangan operasional dan administrator Greenfields.</p>
            <button onclick="openCreateMode()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2 rounded-lg text-sm shadow transition flex items-center gap-2 flex-shrink-0">
                <i class="fa-solid fa-user-plus"></i> Tambah Pengguna Baru
            </button>
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
                            <td class="px-6 py-4 text-gray-400 text-xs whitespace-nowrap">{{ $user->created_at }}</td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-4">
                                    <button type="button" 
                                            onclick="openEditMode('{{ $user->id }}', '{{ $user->name }}', '{{ $user->email }}', '{{ $user->role }}')" 
                                            class="text-xs font-bold text-emerald-600 hover:text-emerald-800 hover:underline">
                                        Edit
                                    </button>
                                    
                                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="m-0" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun pengguna ini secara permanen dari pusat sistem?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-bold text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-2 py-1 rounded border border-red-100 transition">Delete</button>
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
                <div id="methodContainer"></div> <div>
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
    // Pemicu Mode Tambah Data Baru
    function openCreateMode() {
        document.getElementById('mainUserDashboardView').classList.add('hidden');
        document.getElementById('userFormReportingView').classList.remove('hidden');
        
        document.getElementById('formPanelTitle').innerText = "Formulir Tambah Pengguna Baru";
        document.getElementById('submitFormBtn').innerText = "Simpan Data Pengguna Baru";
        
        // Reset isi fields form
        document.getElementById('dynamicUserForm').action = "{{ route('admin.users.store') }}";
        document.getElementById('methodContainer').innerHTML = "";
        document.getElementById('inputName').value = "";
        document.getElementById('inputEmail').value = "";
        document.getElementById('inputPassword').value = "";
        document.getElementById('inputRole').value = "user";
        
        // Tampilkan field password
        document.getElementById('passwordFieldWrapper').classList.remove('hidden');
        document.getElementById('inputPassword').setAttribute('required', 'required');
    }

    // Pemicu Mode Edit Pengguna Lama
    function openEditMode(id, name, email, role) {
        document.getElementById('mainUserDashboardView').classList.add('hidden');
        document.getElementById('userFormReportingView').classList.remove('hidden');
        
        document.getElementById('formPanelTitle').innerText = "Formulir Sunting / Edit Data Pengguna";
        document.getElementById('submitFormBtn').innerText = "Perbarui Data Akun";
        
        // Atur Rute Aksi menuju PUT Update
        document.getElementById('dynamicUserForm').action = `/admin/users/${id}`;
        document.getElementById('methodContainer').innerHTML = `<input type="hidden" name="_method" value="PUT">`;
        
        // Isi otomatis value field dengan data lama
        document.getElementById('inputName').value = name;
        document.getElementById('inputEmail').value = email;
        document.getElementById('inputRole').value = role;
        
        // Sembunyikan field password (karena edit profil tidak merubah sandi di fungsionalitas ini)
        document.getElementById('passwordFieldWrapper').classList.add('hidden');
        document.getElementById('inputPassword').removeAttribute('required');
    }

    // Menutup Form dan Kembali ke Tabel Utama
    function closeFormMode() {
        document.getElementById('userFormReportingView').classList.add('hidden');
        document.getElementById('mainUserDashboardView').classList.remove('hidden');
    }

    // Fitur Filter Sisi Klien Ramah Memori RAM (NFR-1)
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