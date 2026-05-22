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

        <div class="flex flex-row justify-between items-center bg-gray-50 px-4 py-2 border border-gray-200 rounded-xl text-xs font-medium text-gray-600">
            <div class="flex items-center gap-2">
                <span>Tampilkan</span>
                <select id="perPageSelect" onchange="changePerPage()" class="p-1.5 border border-gray-300 rounded-md bg-white focus:outline-emerald-600 font-semibold cursor-pointer">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span>data</span>
            </div>
            <div id="topPagination" class="flex items-center gap-1"></div>
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
                        @if($user->id == 1)
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-gray-400 italic bg-gray-100 px-3 py-1.5 rounded-lg border border-gray-200">
                                <i class="fa-solid fa-shield-halved"></i> Protected
                            </span>
                        @else
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
                        @endif
                    </td>
                </tr>
            @endforeach
            <tr id="noDataRow" class="hidden">
                <td colspan="5" class="p-8 text-center text-gray-400 bg-gray-50/50 font-medium">Tidak ada data pengguna yang cocok dengan kriteria filter.</td>
            </tr>
        </tbody>
    </table>
</div>

        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 bg-white p-4 rounded-xl border border-gray-200 text-xs font-medium text-gray-500 shadow-sm">
            <div id="paginationInfo">Menampilkan 0 sampai 0 dari 0 data pengguna</div>
            <div id="bottomPagination" class="flex items-center gap-1"></div>
        </div>
    </div>

    <div id="userFormReportingView" class="hidden space-y-6 max-w-xl">
        <div class="flex flex-row items-center gap-4 h-[40px]">
            <button type="button" onclick="closeFormMode()" class="flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-gray-900 bg-white border border-gray-300 px-3 py-2 rounded-lg shadow-sm hover:bg-gray-50 transition flex-shrink-0">
                <i class="fa-solid fa-arrow-left"></i> Back
            </button>
            <h3 id="formPanelTitle" class="text-sm font-bold text-gray-800 uppercase tracking-wider truncate">Formulir Tambah Pengguna</h3>
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
    // ENGINE JAVASCRIPT: PAGINATION & SIZING ENTRIES CONTROL
    let currentPage = 1;
    let rowsPerPage = 10;
    let filteredRows = [];

    document.addEventListener("DOMContentLoaded", function() {
        // Jalankan kalkulasi pembagian halaman pertama kali data dimuat
        initUserPagination();

        // Otomasi penghapusan Toast Notification
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

    function initUserPagination() {
        const allRows = Array.from(document.querySelectorAll('.user-data-row'));
        // Ambil elemen baris yang tidak diblokir/disembunyikan oleh sistem filter
        filteredRows = allRows.filter(row => !row.classList.contains('hidden-by-filter'));
        
        currentPage = 1; 
        renderUserTablePage();
    }

    function renderUserTablePage() {
        const totalRows = filteredRows.length;
        const totalPages = Math.ceil(totalRows / rowsPerPage) || 1;

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIdx = (currentPage - 1) * rowsPerPage;
        const endIdx = startIdx + rowsPerPage;

        // Sembunyikan seluruh baris data default terlebih dahulu
        document.querySelectorAll('.user-data-row').forEach(row => {
            row.classList.add('hidden');
        });

        // Hanya tampilkan data yang masuk ke range halaman aktif saat ini
        filteredRows.slice(startIdx, endIdx).forEach(row => {
            row.classList.remove('hidden');
        });

        // Perbarui teks informasi rangkuman entries data di sisi kiri bawah
        const infoStart = totalRows === 0 ? 0 : startIdx + 1;
        const infoEnd = endIdx > totalRows ? totalRows : endIdx;
        document.getElementById('paginationInfo').innerText = `Menampilkan ${infoStart} sampai ${infoEnd} dari ${totalRows} data pengguna`;

        // Gambar ulang tombol kontrol angka halaman
        renderPaginationControls('topPagination', totalPages);
        renderPaginationControls('bottomPagination', totalPages);

        // Atur penampakan notifikasi kosong bila data nihil
        const noDataRow = document.getElementById('noDataRow');
        if (totalRows === 0) {
            if (noDataRow) noDataRow.classList.remove('hidden');
        } else {
            if (noDataRow) noDataRow.classList.add('hidden');
        }
    }

    function renderPaginationControls(containerId, totalPages) {
        const container = document.getElementById(containerId);
        container.innerHTML = '';

        if (totalPages <= 1) return; // Tidak memerlukan pagination bila data muat dalam 1 halaman

        // Tombol Halaman Sebelumnya (Prev)
        const prevBtn = document.createElement('button');
        prevBtn.innerHTML = '<i class="fa-solid fa-angle-left"></i>';
        prevBtn.className = `px-2.5 py-1.5 rounded-lg border text-xs font-semibold transition ${currentPage === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed border-gray-200' : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-300'}`;
        if (currentPage !== 1) prevBtn.onclick = () => { currentPage--; renderUserTablePage(); };
        container.appendChild(prevBtn);

        // Angka-angka halaman indikator
        for (let i = 1; i <= totalPages; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.innerText = i;
            pageBtn.className = `px-3 py-1.5 rounded-lg border text-xs font-bold transition ${currentPage === i ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-300'}`;
            pageBtn.onclick = () => { currentPage = i; renderUserTablePage(); };
            container.appendChild(pageBtn);
        }

        // Tombol Halaman Berikutnya (Next)
        const nextBtn = document.createElement('button');
        nextBtn.innerHTML = '<i class="fa-solid fa-angle-right"></i>';
        nextBtn.className = `px-2.5 py-1.5 rounded-lg border text-xs font-semibold transition ${currentPage === totalPages ? 'bg-gray-100 text-gray-400 cursor-not-allowed border-gray-200' : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-300'}`;
        if (currentPage !== totalPages) nextBtn.onclick = () => { currentPage++; renderUserTablePage(); };
        container.appendChild(nextBtn);
    }

    function changePerPage() {
        rowsPerPage = parseInt(document.getElementById('perPageSelect').value);
        currentPage = 1;
        renderUserTablePage();
    }

    // UTALITAS INTEGRASI MESIN FILTER UTAMA DAN ENGINE PAGINATION
    function filterUserTable() {
        const selectedRole = document.getElementById('filterRole').value;
        const rows = document.querySelectorAll('.user-data-row');

        rows.forEach(row => {
            if (selectedRole === 'ALL' || row.getAttribute('data-role') === selectedRole) {
                row.classList.remove('hidden-by-filter');
            } else {
                row.classList.add('hidden-by-filter');
            }
        });

        // Hitung ulang subset data yang lolos pencarian peran akun
        initUserPagination();
    }

    // MODAL BOX CONFIRMATION ACTION CONTROL
    let currentDeleteFormId = null;

    function triggerDeleteModal(event, formId) {
        event.preventDefault();
        event.stopPropagation();
        
        currentDeleteFormId = formId;
        const modal = document.getElementById('customDeleteModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        setTimeout(() => {
            modal.querySelector('.transform').classList.replace('scale-95', 'scale-100');
        }, 10);
    }

    function closeDeleteModal() {
        const modal = document.getElementById('customDeleteModal');
        modal.querySelector('.transform').classList.replace('scale-100', 'scale-95');
        
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            currentDeleteFormId = null;
        }, 150);
    }

    document.getElementById('confirmDeleteButton').addEventListener('click', function() {
        if (currentDeleteFormId) {
            document.getElementById(currentDeleteFormId).submit();
        }
    });

    // FORM VISIBILITY ACTIONS MODE
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
        const modal = document.getElementById('customDeleteModal');
        if (event.target === modal) {
            closeDeleteModal();
        }
    });
</script>
@endsection