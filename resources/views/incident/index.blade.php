@extends('layout.app')

@section('page_title', 'Daftar Pelaporan Log Insiden Lapangan')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div id="flashNotification" class="bg-green-600 text-white p-3 rounded-lg text-sm font-semibold shadow-sm transition-all duration-300">
            {{ session('success') }}
        </div>
    @endif

    <div id="mainDashboardView" class="space-y-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <p class="text-sm text-gray-600">Berikut adalah daftar anomali operasional dan log aktivitas terdaftar pada database sistem.</p>
            <button onclick="switchToFormMode()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2 rounded-lg text-sm shadow transition flex items-center gap-2 flex-shrink-0">
                <i class="fa-solid fa-plus"></i> Laporkan Insiden Baru
            </button>
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
                        @if(auth()->user()->role === 'admin')
                            <th class="px-6 py-4 text-center">Aksi Admin</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-700 divide-y divide-gray-100">
                    @forelse($incidents as $incident)
                        <tr class="incident-row {{ $incident->severity_level === 'Critical' ? 'highlight-critical' : '' }} hover:bg-gray-50/50 transition" 
                            data-room="{{ $incident->room_id ?? 'Ruang 1' }}" 
                            data-severity="{{ $incident->severity_level }}"
                            data-status="{{ $incident->status ?? 'Open' }}">
                            
                            <td class="px-6 py-4 font-semibold text-gray-800 whitespace-nowrap">
                                {{ $incident->room_id ?? 'Ruang 1' }}
                            </td>
                            
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-900">{{ $incident->title }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ $incident->description ?? 'Tidak ada catatan kronologi.' }}</div>
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
                                {{ $incident->created_at }}
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

                                        <form action="{{ route('incidents.destroy', $incident->id) }}" method="POST" class="m-0" onsubmit="return confirm('Apakah Anda yakin ingin mengeksekusi soft-delete pada log insiden ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-2.5 py-1 rounded border border-red-100 transition font-medium">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr id="noDataRow">
                            <td colspan="{{ auth()->user()->role === 'admin' ? 7 : 6 }}" class="p-8 text-center text-gray-400 bg-gray-50/50 font-medium">Tidak ada log insiden operasional aktif saat ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="formReportingView" class="hidden space-y-6 max-w-2xl">
        <div class="flex items-center gap-4">
            <button type="button" onclick="switchToDashboardMode()" class="flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-gray-900 bg-white border border-gray-300 px-3 py-2 rounded-lg shadow-sm hover:bg-gray-50 transition">
                <i class="fa-solid fa-arrow-left"></i> Back
            </button>
            <h3 class="text-base font-bold text-gray-800 uppercase tracking-wider">Formulir Catat Insiden Baru</h3>
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
</div>

<script>
    function switchToFormMode() {
        document.getElementById('mainDashboardView').classList.add('hidden');
        document.getElementById('formReportingView').classList.remove('hidden');
        if(document.getElementById('flashNotification')) {
            document.getElementById('flashNotification').classList.add('hidden');
        }
    }

    function switchToDashboardMode() {
        document.getElementById('formReportingView').classList.add('hidden');
        document.getElementById('mainDashboardView').classList.remove('hidden');
    }

    // Mesin Pencarian Filter Kombinasi Klien 3 Variabel (Ruang, Severity, Status)
    function filterIncidentTable() {
        const selectedRoom = document.getElementById('filterRuang').value;
        const selectedSeverity = document.getElementById('filterSeverity').value;
        const selectedStatus = document.getElementById('filterStatus').value;
        const rows = document.querySelectorAll('.incident-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const matchRoom = (selectedRoom === 'ALL' || row.getAttribute('data-room') === selectedRoom);
            const matchSeverity = (selectedSeverity === 'ALL' || row.getAttribute('data-severity') === selectedSeverity);
            const matchStatus = (selectedStatus === 'ALL' || row.getAttribute('data-status') === selectedStatus);

            if (matchRoom && matchSeverity && matchStatus) {
                row.classList.remove('hidden');
                visibleCount++;
            } else {
                row.classList.add('hidden');
            }
        });

        // Pengelolaan tampilan baris kosong dinamis jika tidak ada data yang cocok
        const noDataRow = document.getElementById('noDataRow');
        if (visibleCount === 0) {
            if (!noDataRow) {
                const tbody = document.querySelector('#incidentTable tbody');
                const colSpanCount = {{ auth()->user()->role === 'admin' ? 7 : 6 }};
                const newRow = document.createElement('tr');
                newRow.id = 'noDataRow';
                newRow.innerHTML = `<td colspan="${colSpanCount}" class="p-6 text-center text-gray-400 bg-gray-50/50 font-medium">Tidak ada log insiden operasional yang cocok dengan kriteria filter.</td>`;
                tbody.appendChild(newRow);
            } else {
                noDataRow.classList.remove('hidden');
            }
        } else if (noDataRow) {
            noDataRow.classList.add('hidden');
        }
    }
</script>
@endsection