@extends('layout.app')

@section('page_title', 'Riwayat Audit Sistem')

@section('content')
<div class="space-y-6">

  {{-- BARIS HEADER UTAMA & TOMBOL EXPORT DOWNLOAD --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <p class="text-sm text-gray-600">Berikut adalah rekaman log aktivitas, riwayat manipulasi data, dan pelacakan audit sistem operasional.</p>
        
        <div class="flex items-center gap-3 flex-shrink-0 w-full sm:w-auto justify-end">
            <a href="{{ route('admin.audit.export') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg text-sm shadow transition flex items-center gap-2 whitespace-nowrap">
                <i class="fa-solid fa-file-excel"></i> Download Excel
            </a>
        </div>
    </div>
    
    {{-- BARIS FILTER DROPDOWN SELECT BIASA (SESUAI GAMBAR) --}}
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label Executive for="filterAction" class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Filter Jenis Aksi</label>
            <select id="filterAction" onchange="filterAuditTable()" class="w-full mt-1.5 p-2 bg-slate-50 border border-gray-300 rounded-lg text-xs focus:outline-emerald-600 font-semibold text-gray-700">
                <option value="ALL">Semua Jenis Aksi</option>
                <option value="INSERT">INSERT (Tambah Data)</option>
                <option value="UPDATE">UPDATE (Perbarui Data)</option>
                <option value="SOFT_DELETE">SOFT_DELETE (Hapus Data)</option>
            </select>
        </div>
        <div>
            <label for="filterTable" class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Filter Target Tabel</label>
            <select id="filterTable" onchange="filterAuditTable()" class="w-full mt-1.5 p-2 bg-slate-50 border border-gray-300 rounded-lg text-xs focus:outline-emerald-600 font-semibold text-gray-700">
                <option value="ALL">Semua Target Tabel</option>
                <option value="incident_logs">incident_logs</option>
                <option value="users">users</option>
            </select>
        </div>
    </div>

    {{-- KONTROL BARIS PER HALAMAN & PAGINASI ATAS --}}
    <div class="flex flex-row justify-between items-center bg-gray-50 px-4 py-2 border border-gray-200 rounded-xl text-xs font-medium text-gray-600">
        <div class="flex items-center gap-2">
            <span>Tampilkan</span>
            <select id="perPageSelect" onchange="changePerPage()" class="p-1.5 border border-gray-300 rounded-md bg-white focus:outline-emerald-600 font-semibold cursor-pointer">
                <option value="10" selected>10</option>
                <option value="20">20</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span>data</span>
        </div>
        <div id="topPagination" class="flex items-center gap-1"></div>
    </div>

    {{-- TABEL DATA UTAMA --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left border-collapse" id="auditTable">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    <th class="px-6 py-4">Waktu</th>
                    <th class="px-6 py-4">Pengguna</th>
                    <th class="px-6 py-4">Aksi</th>
                    <th class="px-6 py-4">Tabel</th>
                    <th class="px-6 py-4">Data Perubahan (Old vs New)</th>
                </tr>
            </thead>
            <tbody class="text-sm text-gray-700 divide-y divide-gray-100">
                @forelse($trails as $log)
                <tr class="trail-row hover:bg-gray-50 transition border-b border-gray-100"
                    data-action="{{ $log->action }}"
                    data-table="{{ $log->table_name }}">
                    <td class="px-6 py-4 text-xs text-gray-400 whitespace-nowrap">
                        {{ \Carbon\Carbon::parse($log->created_at)->translatedFormat('d M Y H:i:s') }}
                    </td>
                    <td class="px-6 py-4 font-bold text-gray-900">{{ $log->user_name ?? 'Sistem' }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-[10px] font-bold rounded-md uppercase 
                            {{ $log->action == 'INSERT' ? 'bg-emerald-100 text-emerald-800' : 
                               ($log->action == 'UPDATE' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800') }}">
                            {{ $log->action }}
                        </span>
                    </td>
                    <td class="px-6 py-4 font-medium text-gray-600">{{ $log->table_name }}</td>
                    <td class="px-6 py-4 text-xs font-mono bg-gray-50/50 rounded-lg max-w-sm break-all">
                        <div class="text-[10px] text-red-600 mb-1">Old: {{ $log->old_values ?? 'null' }}</div>
                        <div class="text-[10px] text-emerald-600">New: {{ $log->new_values ?? 'null' }}</div>
                    </td>
                </tr>
                @empty
                <tr id="noDataRow">
                    <td colspan="5" class="p-8 text-center text-gray-400 bg-gray-50/50 font-medium">Tidak ada rekaman data audit trail aktif saat ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    {{-- PAGINASI BAWAH & KETERANGAN SUMMARY --}}
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 bg-white p-4 rounded-xl border border-gray-200 text-xs font-medium text-gray-500 shadow-sm">
        <div id="paginationInfo">Menampilkan 0 sampai 0 dari 0 log operasional</div>
        <div id="bottomPagination" class="flex items-center gap-1"></div>
    </div>
</div>

<script>
    // ENGINE CLIENT-SIDE PAGINATION & FILTER AUDIT TRAILS
    let currentPage = 1;
    let rowsPerPage = 10;
    let filteredRows = [];

    document.addEventListener("DOMContentLoaded", function() {
        // Jalankan pemetaan filter pertama kali saat halaman dibuka
        filterAuditTable();
    });

    // Fungsi Utama Filter Data Berdasarkan Dropdown Value
    function filterAuditTable() {
        const selectedAction = document.getElementById('filterAction').value;
        const selectedTable = document.getElementById('filterTable').value;
        const rows = document.querySelectorAll('.trail-row');

        rows.forEach(row => {
            const matchAction = (selectedAction === 'ALL' || row.getAttribute('data-action') === selectedAction);
            const matchTable = (selectedTable === 'ALL' || row.getAttribute('data-table') === selectedTable);

            if (matchAction && matchTable) {
                row.classList.remove('hidden-by-filter');
            } else {
                row.classList.add('hidden-by-filter');
            }
        });

        initPagination();
    }

    // Mengatur List Data yang Lolos Filter untuk Dipaginasi
    function initPagination() {
        const allRows = Array.from(document.querySelectorAll('.trail-row'));
        filteredRows = allRows.filter(row => !row.classList.contains('hidden-by-filter'));
        
        currentPage = 1; 
        renderTablePage();
    }

    // Merender Baris Hanya Pada Halaman yang Sedang Aktif
    function renderTablePage() {
        const totalRows = filteredRows.length;
        const totalPages = Math.ceil(totalRows / rowsPerPage) || 1;

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIdx = (currentPage - 1) * rowsPerPage;
        const endIdx = startIdx + rowsPerPage;

        document.querySelectorAll('.trail-row').forEach(row => {
            row.classList.add('hidden'); // Sembunyikan semuanya dahulu
        });

        filteredRows.slice(startIdx, endIdx).forEach(row => {
            row.classList.remove('hidden'); // Tampilkan baris terpilih pada halaman ini
        });

        const infoStart = totalRows === 0 ? 0 : startIdx + 1;
        const infoEnd = endIdx > totalRows ? totalRows : endIdx;
        document.getElementById('paginationInfo').innerText = `Menampilkan ${infoStart} sampai ${infoEnd} dari ${totalRows} log riwayat audit`;

        renderPaginationButtons('topPagination', totalPages);
        renderPaginationButtons('bottomPagination', totalPages);

        // Pengkondisian Baris Data Kosong (Data Not Found)
        const noDataRow = document.getElementById('noDataRow');
        if (totalRows === 0) {
            if (noDataRow) noDataRow.classList.remove('hidden');
        } else {
            if (noDataRow) noDataRow.classList.add('hidden');
        }
    }

    // Membuat Komponen Kontrol Tombol Halaman (Prev, Number, Next)
    function renderPaginationButtons(containerId, totalPages) {
        const container = document.getElementById(containerId);
        container.innerHTML = '';

        if (totalPages <= 1) return;

        // Tombol Halaman Sebelumnya (Prev)
        const prevBtn = document.createElement('button');
        prevBtn.innerHTML = '<i class="fa-solid fa-angle-left"></i>';
        prevBtn.className = `px-2.5 py-1.5 rounded-lg border text-xs font-semibold transition ${currentPage === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed border-gray-200' : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-300'}`;
        if (currentPage !== 1) prevBtn.onclick = () => { currentPage--; renderTablePage(); };
        container.appendChild(prevBtn);

        // Penomoran Angka Dinamis
        for (let i = 1; i <= totalPages; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.innerText = i;
            pageBtn.className = `px-3 py-1.5 rounded-lg border text-xs font-bold transition ${currentPage === i ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-300'}`;
            pageBtn.onclick = () => { currentPage = i; renderTablePage(); };
            container.appendChild(pageBtn);
        }

        // Tombol Halaman Selanjutnya (Next)
        const nextBtn = document.createElement('button');
        nextBtn.innerHTML = '<i class="fa-solid fa-angle-right"></i>';
        nextBtn.className = `px-2.5 py-1.5 rounded-lg border text-xs font-semibold transition ${currentPage === totalPages ? 'bg-gray-100 text-gray-400 cursor-not-allowed border-gray-200' : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-300'}`;
        if (currentPage !== totalPages) nextBtn.onclick = () => { currentPage++; renderTablePage(); };
        container.appendChild(nextBtn);
    }

    // Mengubah Jumlah Baris Tampilan per Halaman
    function changePerPage() {
        rowsPerPage = parseInt(document.getElementById('perPageSelect').value);
        currentPage = 1;
        renderTablePage();
    }
</script>
@endsection