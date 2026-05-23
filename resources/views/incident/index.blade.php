@extends('layout.app')

@section('page_title', 'Daftar Pelaporan Log Insiden Lapangan')

@section('content')
<div id="toastContainer" class="fixed top-5 right-5 z-[9999] pointer-events-none flex flex-col gap-3">
    @if(session('success'))
        <div id="flashNotification" class="pointer-events-auto bg-green-600 text-white px-5 py-3 rounded-xl text-sm font-bold shadow-2xl transition-all duration-500 transform translate-y-0 opacity-100 flex items-center gap-2 border border-green-500/30 min-w-[300px]">
            <i class="fa-solid fa-circle-check text-base flex-shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
</div>

<div id="customDeleteModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[10000] flex items-center justify-center p-4 transition-all duration-300">
    <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-gray-100 transform scale-95 transition-all duration-300 space-y-4">
        <div class="flex flex-col items-center text-center space-y-3">
            <div class="w-12 h-12 bg-red-50 text-red-600 rounded-full flex items-center justify-center border border-red-100 text-xl shadow-sm">
                <i class="fa-solid fa-triangle-exclamation animate-pulse"></i>
            </div>
            <div class="space-y-1">
                <h4 class="text-base font-bold text-gray-900">Apakah Anda Yakin?</h4>
                <p class="text-xs text-gray-500 leading-relaxed">Rekaman log insiden operasional ini akan dipindahkan ke sistem pengarsipan aman (*soft-delete*).</p>
            </div>
        </div>
        <div class="flex items-center gap-3 pt-2">
            <button onclick="closeDeleteModal()" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2.5 rounded-xl border border-gray-200 transition focus:outline-none">
                Batal
            </button>
            <button id="confirmDeleteButton" class="w-full bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-2.5 rounded-xl transition shadow-md shadow-red-600/10 focus:outline-none">
                Ya, Hapus Data
            </button>
        </div>
    </div>
</div>

<div class="space-y-6">
    <div id="mainDashboardView" class="space-y-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <p class="text-sm text-gray-600">Berikut adalah daftar anomali operasional dan log aktivitas terdaftar pada database sistem.</p>
            
            <div class="flex items-center gap-3 flex-shrink-0 w-full sm:w-auto justify-end">
                <a href="{{ route('incidents.export') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg text-sm shadow transition flex items-center gap-2 whitespace-nowrap">
                    <i class="fa-solid fa-file-excel"></i> Download Excel
                </a>
                
                <button onclick="switchToFormMode()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2 rounded-lg text-sm shadow transition flex items-center gap-2 whitespace-nowrap">
                    <i class="fa-solid fa-plus"></i> Laporkan Insiden Baru
                </button>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="filterRuang" class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Filter Lokasi Ruang</label>
                <select id="filterRuang" onchange="filterIncidentTable()" class="w-full mt-1.5 p-2 bg-slate-50 border border-gray-300 rounded-lg text-xs focus:outline-emerald-600 font-semibold text-gray-700">
                    <option value="ALL">Semua Ruang (1 - 5)</option>
                    <option value="Ruang 1">Ruang 1</option>
                    <option value="Ruang 2">Ruang 2</option>
                    <option value="Ruang 3">Ruang 3</option>
                    <option value="Ruang 4">Ruang 4</option>
                    <option value="Ruang 5">Ruang 5</option>
                </select>
            </div>
            <div>
                <label for="filterSeverity" class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Filter Tingkat Keparahan</label>
                <select id="filterSeverity" onchange="filterIncidentTable()" class="w-full mt-1.5 p-2 bg-slate-50 border border-gray-300 rounded-lg text-xs focus:outline-emerald-600 font-semibold text-gray-700">
                    <option value="ALL">Semua Tingkat Urgensi</option>
                    <option value="Normal">Normal</option>
                    <option value="Warning">Warning</option>
                    <option value="Critical">Critical</option>
                </select>
            </div>
            <div>
                <label for="filterStatus" class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Filter Status Alur Kerja</label>
                <select id="filterStatus" onchange="filterIncidentTable()" class="w-full mt-1.5 p-2 bg-slate-50 border border-gray-300 rounded-lg text-xs focus:outline-emerald-600 font-semibold text-gray-700">
                    <option value="ALL">Semua Status Kerja</option>
                    <option value="Open">Open (Belum Ditangani)</option>
                    <option value="In Progress">In Progress (Sedang Diproses)</option>
                    <option value="Resolved">Resolved (Selesai/Complete)</option>
                </select>
            </div>
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
            <table class="w-full text-left border-collapse" id="incidentTable">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-4">ID Ruang</th>
                        <th class="px-6 py-4">Judul Masalah / Kendala</th>
                        <th class="px-6 py-4">Tingkat Keparahan</th>
                        <th class="px-6 py-4">Pelapor</th>
                        <th class="px-6 py-4">Waktu Kejadian</th>
                        <th class="px-6 py-4">Status Kerja</th>
                        <th class="px-6 py-4 text-center">Opsi</th>
                        @if(auth()->user()->role === 'admin')
                            <th class="px-6 py-4 text-center">Aksi Admin</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-700 divide-y divide-gray-100">
                    @forelse($incidents as $incident)
                        <tr class="incident-row transition duration-200 border-b border-gray-100 
                            {{ $incident->severity_level === 'Critical' ? 'bg-red-50 hover:bg-red-100/80 border-l-4 border-l-red-500' : '' }}
                            {{ $incident->severity_level === 'Warning' ? 'bg-amber-50/50 hover:bg-amber-100/60 border-l-4 border-l-amber-400' : '' }}
                            {{ $incident->severity_level === 'Normal' ? 'bg-white hover:bg-gray-50 border-l-4 border-l-transparent' : '' }}" 
                            data-room="{{ $incident->room_id ?? 'Ruang 1' }}" 
                            data-severity="{{ $incident->severity_level }}"
                            data-status="{{ $incident->status ?? 'Open' }}">
                            
                            <td class="px-6 py-4 font-semibold text-gray-800 whitespace-nowrap">
                                {{ $incident->room_id ?? 'Ruang 1' }}
                            </td>
                            
                            <td class="px-6 py-4 max-w-xs break-words whitespace-normal">
                                <div class="font-bold text-gray-900 break-words">{{ $incident->title }}</div>
                                <div class="text-xs text-gray-500 mt-0.5 break-words line-clamp-2">{{ $incident->description ?? 'Tidak ada catatan kronologi.' }}</div>
                            </td>
                            
                            <td class="px-6 py-4 font-bold whitespace-nowrap">
                                @if($incident->severity_level === 'Critical')
                                    <span class="text-red-600 bg-red-100 px-2.5 py-1 rounded text-xs font-bold border border-red-200">⚠️ CRITICAL</span>
                                @elseif($incident->severity_level === 'Warning')
                                    <span class="text-amber-600 bg-amber-50 px-2.5 py-1 rounded text-xs font-semibold border border-amber-200">⏳ Warning</span>
                                @else
                                    <span class="text-blue-600 bg-blue-50 px-2.5 py-1 rounded text-xs border border-blue-200">Normal</span>
                                @endif
                            </td>
                            
                            <td class="px-6 py-4 text-gray-600 font-medium whitespace-nowrap">
                                {{ $incident->reported_by_name ?? 'Staf Lapangan' }}
                            </td>
                            
                            <td class="px-6 py-4 text-gray-400 text-xs whitespace-nowrap">
                                {{ isset($incident->created_at) ? \Carbon\Carbon::parse($incident->created_at)->translatedFormat('d M Y H:i') : '-' }}
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                @if(($incident->status ?? 'Open') === 'Open')
                                    <span class="bg-gray-100 text-gray-800 text-xs font-bold px-2.5 py-1 rounded-md border border-gray-300">Open</span>
                                @elseif($incident->status === 'In Progress')
                                    <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2.5 py-1 rounded-md border border-blue-300">In Progress</span>
                                @else
                                    <span class="bg-emerald-100 text-emerald-800 text-xs font-bold px-2.5 py-1 rounded-md border border-emerald-300">Resolved</span>
                                @endif
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-center relative">
                                <button onclick="toggleDropdown(event, 'dropdown-{{ $incident->id }}')" class="text-gray-500 hover:text-gray-800 p-1.5 rounded-full hover:bg-gray-100 transition focus:outline-none">
                                    <i class="fa-solid fa-ellipsis-vertical text-base"></i>
                                </button>
                                
                                <div id="dropdown-{{ $incident->id }}" class="hidden absolute right-12 top-2 w-32 bg-white border border-gray-200 rounded-lg shadow-2xl z-[999] py-1 overflow-hidden animate-fadeIn">
                                    <button onclick="openEditModal({{ json_encode($incident) }})" class="w-full text-left px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-slate-50 hover:text-emerald-600 flex items-center gap-2 transition">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit Log
                                    </button>
                                    
                                    <form id="delete-form-{{ $incident->id }}" action="{{ route('incidents.destroy', $incident->id) }}" method="POST" class="m-0">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" onclick="triggerDeleteModal(event, 'delete-form-{{ $incident->id }}')" class="w-full text-left px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 flex items-center gap-2 transition">
                                            <i class="fa-solid fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>

                            @if(auth()->user()->role === 'admin')
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-3">
                                        <form action="{{ route('incidents.update', $incident->id) }}" method="POST" class="m-0">
                                            @csrf
                                            @method('PUT')
                                            <select name="status" onchange="this.form.submit()" class="text-xs p-1 border border-gray-300 rounded bg-white font-medium cursor-pointer focus:outline-emerald-600 shadow-sm">
                                                <option value="Open" {{ $incident->status == 'Open' ? 'selected' : '' }}>Open</option>
                                                <option value="In Progress" {{ $incident->status == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                                                <option value="Resolved" {{ $incident->status == 'Resolved' ? 'selected' : '' }}>Resolved</option>
                                            </select>
                                        </form>

                                        <form id="admin-delete-form-{{ $incident->id }}" action="{{ route('incidents.destroy', $incident->id) }}" method="POST" class="m-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" onclick="triggerDeleteModal(event, 'admin-delete-form-{{ $incident->id }}')" class="text-xs text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-2.5 py-1 rounded border border-red-100 transition font-medium">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr id="noDataRow">
                            <td colspan="{{ auth()->user()->role === 'admin' ? 8 : 7 }}" class="p-8 text-center text-gray-400 bg-gray-50/50 font-medium">Tidak ada log insiden operasional aktif saat ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 bg-white p-4 rounded-xl border border-gray-200 text-xs font-medium text-gray-500 shadow-sm">
            <div id="paginationInfo">Menampilkan 0 sampai 0 dari 0 log operasional</div>
            <div id="bottomPagination" class="flex items-center gap-1"></div>
        </div>
    </div>

    <div id="formReportingView" class="hidden space-y-6 max-w-2xl">
        <div class="flex flex-row items-center gap-4 h-[40px]">
            <button type="button" onclick="switchToDashboardMode()" class="flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-gray-900 bg-white border border-gray-300 px-3 py-2 rounded-lg shadow-sm hover:bg-gray-50 transition flex-shrink-0">
                <i class="fa-solid fa-arrow-left"></i> Back
            </button>
            <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider truncate">Formulir Catat Insiden Baru</h3>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <form action="{{ route('incidents.store') }}" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider">ID Lokasi Ruang</label>
                    <select name="room_id" required class="w-full mt-1.5 p-2.5 bg-slate-50 border border-gray-300 rounded-lg text-sm focus:outline-emerald-600 font-medium text-gray-800">
                        <option value="Ruang 1">Ruang 1</option>
                        <option value="Ruang 2">Ruang 2</option>
                        <option value="Ruang 3">Ruang 3</option>
                        <option value="Ruang 4">Ruang 4</option>
                        <option value="Ruang 5">Ruang 5</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider">Judul Insiden / Temuan Masalah</label>
                    <input type="text" name="title" required max="150" placeholder="Misal: Malfungsi Belt Conveyor Line 3" class="w-full mt-1.5 p-2.5 border border-gray-300 rounded-lg text-sm focus:outline-emerald-600 placeholder-gray-400">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider">Deskripsi Rincian Kendala Kronologi</label>
                    <textarea name="description" rows="4" placeholder="Tulis rincian kejadian dan indikator mesin kendala di lapangan..." class="w-full mt-1.5 p-2.5 border border-gray-300 rounded-lg text-sm focus:outline-emerald-600 placeholder-gray-400"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider">Tingkat Keparahan Kendala (Severity)</label>
                    <select name="severity_level" required class="w-full mt-1.5 p-2.5 bg-slate-50 border border-gray-300 rounded-lg text-sm focus:outline-emerald-600 font-medium text-gray-800">
                        <option value="Normal">Normal</option>
                        <option value="Warning">Warning</option>
                        <option value="Critical">Critical (Butuh Tindakan Cepat)</option>
                    </select>
                </div>
                <div class="pt-2">
                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white p-3 rounded-lg font-bold text-sm shadow transition-all duration-150">
                        Kirim Laporan ke Pusat Database
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="formEditView" class="hidden space-y-6 max-w-2xl">
        <div class="flex flex-row items-center gap-4 h-[40px]">
            <button type="button" onclick="closeEditMode()" class="flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-gray-900 bg-white border border-gray-300 px-3 py-2 rounded-lg shadow-sm hover:bg-gray-50 transition flex-shrink-0">
                <i class="fa-solid fa-arrow-left"></i> Batal
            </button>
            <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider truncate">Formulir Edit Log Insiden</h3>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <form id="editForm" method="POST" class="space-y-5">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider">ID Lokasi Ruang</label>
                    <select name="room_id" id="edit_room_id" required class="w-full mt-1.5 p-2.5 bg-slate-50 border border-gray-300 rounded-lg text-sm focus:outline-emerald-600 font-medium text-gray-800">
                        <option value="Ruang 1">Ruang 1</option>
                        <option value="Ruang 2">Ruang 2</option>
                        <option value="Ruang 3">Ruang 3</option>
                        <option value="Ruang 4">Ruang 4</option>
                        <option value="Ruang 5">Ruang 5</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider">Judul Insiden / Temuan Masalah</label>
                    <input type="text" name="title" id="edit_title" required max="150" class="w-full mt-1.5 p-2.5 border border-gray-300 rounded-lg text-sm focus:outline-emerald-600">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider">Deskripsi Rincian Kendala Kronologi</label>
                    <textarea name="description" id="edit_description" rows="4" class="w-full mt-1.5 p-2.5 border border-gray-300 rounded-lg text-sm focus:outline-emerald-600"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider">Tingkat Keparahan Kendala (Severity)</label>
                    <select name="severity_level" id="edit_severity_level" required class="w-full mt-1.5 p-2.5 bg-slate-50 border border-gray-300 rounded-lg text-sm focus:outline-emerald-600 font-medium text-gray-800">
                        <option value="Normal">Normal</option>
                        <option value="Warning">Warning</option>
                        <option value="Critical">Critical</option>
                    </select>
                </div>
                <div class="pt-2">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-lg font-bold text-sm shadow transition-all duration-150">
                        Simpan Perubahan Data Log
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // ENGINE UTAMA CLIENT-SIDE PAGINATION & FILTER
    let currentPage = 1;
    let rowsPerPage = 10;
    let filteredRows = [];

    document.addEventListener("DOMContentLoaded", function() {
        // Jalankan inisialisasi pagination pertama kali halaman di-load
        initPagination();

        const notification = document.getElementById('flashNotification');
        if (notification) {
            setTimeout(() => {
                notification.classList.replace('opacity-100', 'opacity-0');
                notification.classList.replace('translate-y-0', '-translate-y-4');
                setTimeout(() => notification.remove(), 500);
            }, 3000);
        }
    });

    function initPagination() {
        // Ambil baris data yang sedang tidak tersembunyi oleh filter utama
        const allRows = Array.from(document.querySelectorAll('.incident-row'));
        filteredRows = allRows.filter(row => !row.classList.contains('hidden-by-filter'));
        
        currentPage = 1; 
        renderTablePage();
    }

    function renderTablePage() {
        const totalRows = filteredRows.length;
        const totalPages = Math.ceil(totalRows / rowsPerPage) || 1;

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIdx = (currentPage - 1) * rowsPerPage;
        const endIdx = startIdx + rowsPerPage;

        // Tampilkan/Sembunyikan baris data berdasarkan halaman aktif
        document.querySelectorAll('.incident-row').forEach(row => {
            row.classList.add('hidden'); // Sembunyikan semua dulu
        });

        filteredRows.slice(startIdx, endIdx).forEach(row => {
            row.classList.remove('hidden'); // Munculkan data di page ini
        });

        // Perbarui Info summary di bagian bawah
        const infoStart = totalRows === 0 ? 0 : startIdx + 1;
        const infoEnd = endIdx > totalRows ? totalRows : endIdx;
        document.getElementById('paginationInfo').innerText = `Menampilkan ${infoStart} sampai ${infoEnd} dari ${totalRows} log operasional`;

        // Render tombol kontrol page di bagian atas dan bawah
        renderPaginationButtons('topPagination', totalPages);
        renderPaginationButtons('bottomPagination', totalPages);

        // Atur penampakan teks "Tidak ada data" jika hasil filter kosong
        const noDataRow = document.getElementById('noDataRow');
        if (totalRows === 0) {
            if (noDataRow) noDataRow.classList.remove('hidden');
        } else {
            if (noDataRow) noDataRow.classList.add('hidden');
        }
    }

    function renderPaginationButtons(containerId, totalPages) {
        const container = document.getElementById(containerId);
        container.innerHTML = '';

        if (totalPages <= 1) return; // Tidak perlu tombol jika hanya 1 halaman

        // Tombol Prev
        const prevBtn = document.createElement('button');
        prevBtn.innerHTML = '<i class="fa-solid fa-angle-left"></i>';
        prevBtn.className = `px-2.5 py-1.5 rounded-lg border text-xs font-semibold transition ${currentPage === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed border-gray-200' : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-300'}`;
        if (currentPage !== 1) prevBtn.onclick = () => { currentPage--; renderTablePage(); };
        container.appendChild(prevBtn);

        // Angka Halaman
        for (let i = 1; i <= totalPages; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.innerText = i;
            pageBtn.className = `px-3 py-1.5 rounded-lg border text-xs font-bold transition ${currentPage === i ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-300'}`;
            pageBtn.onclick = () => { currentPage = i; renderTablePage(); };
            container.appendChild(pageBtn);
        }

        // Tombol Next
        const nextBtn = document.createElement('button');
        nextBtn.innerHTML = '<i class="fa-solid fa-angle-right"></i>';
        nextBtn.className = `px-2.5 py-1.5 rounded-lg border text-xs font-semibold transition ${currentPage === totalPages ? 'bg-gray-100 text-gray-400 cursor-not-allowed border-gray-200' : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-300'}`;
        if (currentPage !== totalPages) nextBtn.onclick = () => { currentPage++; renderTablePage(); };
        container.appendChild(nextBtn);
    }

    function changePerPage() {
        rowsPerPage = parseInt(document.getElementById('perPageSelect').value);
        currentPage = 1;
        renderTablePage();
    }

    // UPDATE MESIN FILTERING (DIINTERGRASIKAN DENGAN ENGINE PAGINATION)
    function filterIncidentTable() {
        const selectedRoom = document.getElementById('filterRuang').value;
        const selectedSeverity = document.getElementById('filterSeverity').value;
        const selectedStatus = document.getElementById('filterStatus').value;
        const rows = document.querySelectorAll('.incident-row');

        rows.forEach(row => {
            const matchRoom = (selectedRoom === 'ALL' || row.getAttribute('data-room') === selectedRoom);
            const matchSeverity = (selectedSeverity === 'ALL' || row.getAttribute('data-severity') === selectedSeverity);
            const matchStatus = (selectedStatus === 'ALL' || row.getAttribute('data-status') === selectedStatus);

            if (matchRoom && matchSeverity && matchStatus) {
                row.classList.remove('hidden-by-filter');
            } else {
                row.classList.add('hidden-by-filter');
            }
        });

        // Hitung ulang baris data yang lolos filter, lalu reset ke page 1
        initPagination();
    }

    // MODAL & NAVIGASI ACTIONS
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

    function switchToFormMode() {
        document.getElementById('mainDashboardView').classList.add('hidden');
        document.getElementById('formEditView').classList.add('hidden');
        document.getElementById('formReportingView').classList.remove('hidden');
    }

    function switchToDashboardMode() {
        document.getElementById('formReportingView').classList.add('hidden');
        document.getElementById('formEditView').classList.add('hidden');
        document.getElementById('mainDashboardView').classList.remove('hidden');
    }

    function openEditModal(incident) {
        document.getElementById('edit_room_id').value = incident.room_id || 'Ruang 1';
        document.getElementById('edit_title').value = incident.title;
        document.getElementById('edit_description').value = incident.description || '';
        document.getElementById('edit_severity_level').value = incident.severity_level;
        
        document.getElementById('editForm').action = `/incidents/${incident.id}`;
        
        document.getElementById('mainDashboardView').classList.add('hidden');
        document.getElementById('formReportingView').classList.add('hidden');
        document.getElementById('formEditView').classList.remove('hidden');
    }

    function closeEditMode() {
        switchToDashboardMode();
    }

    function toggleDropdown(event, id) {
        event.stopPropagation();
        const dropdowns = document.querySelectorAll('[id^="dropdown-"]');
        dropdowns.forEach(div => {
            if (div.id !== id) div.classList.add('hidden');
        });
        const targetDropdown = document.getElementById(id);
        targetDropdown.classList.toggle('hidden');
    }

    window.addEventListener('click', function(event) {
        const dropdowns = document.querySelectorAll('[id^="dropdown-"]');
        dropdowns.forEach(div => div.classList.add('hidden'));
        const modal = document.getElementById('customDeleteModal');
        if (event.target === modal) {
            closeDeleteModal();
        }
    });
</script>
@endsection