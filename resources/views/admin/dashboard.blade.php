@extends('layout.app')

@section('page_title', 'Dashboard Administrator')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-6">
    {{-- Header Sambutan --}}
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-extrabold text-gray-800">Selamat Datang, {{ auth()->user()->name }}!</h2>
            <p class="text-sm text-gray-500 mt-1">Berikut adalah ringkasan sistem operasional Greenfields saat ini secara real-time.</p>
        </div>
        <div class="hidden md:flex w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl items-center justify-center text-2xl border border-emerald-100 shadow-inner">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
    </div>

    {{-- Kartu Indikator Utama (KPI) - SEKARANG BISA DIKLIK --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div onclick="window.location.href='{{ route('admin.users.index') }}'" 
             class="cursor-pointer bg-gradient-to-br from-slate-800 to-slate-900 p-6 rounded-2xl shadow-lg border border-slate-700 relative overflow-hidden group hover:opacity-95 hover:scale-[1.01] transition-all duration-200">
            <div class="absolute right-[-10%] top-[-10%] text-slate-700/50 text-8xl group-hover:scale-110 transition-transform duration-500">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="relative z-10">
                <p class="text-slate-400 text-sm font-semibold uppercase tracking-wider mb-1" id="kpiUserTitle">Memuat...</p>
                <h3 class="text-4xl font-black text-white" id="kpiTotalUsers">
                    <i class="fa-solid fa-spinner fa-spin text-sm text-slate-500"></i>
                </h3>
                <p class="text-xs text-emerald-400 mt-2 font-medium">
                    <i class="fa-solid fa-circle-check"></i> <span id="kpiUserSubtitle">Menganalisis...</span>
                </p>
            </div>
        </div>

        <div onclick="window.location.href='{{ route('incidents.index') }}'" 
             class="cursor-pointer bg-gradient-to-br from-emerald-600 to-emerald-700 p-6 rounded-2xl shadow-lg border border-emerald-500 relative overflow-hidden group hover:opacity-95 hover:scale-[1.01] transition-all duration-200">
            <div class="absolute right-[-10%] top-[-10%] text-emerald-800/30 text-8xl group-hover:scale-110 transition-transform duration-500">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="relative z-10">
                <p class="text-emerald-100 text-sm font-semibold uppercase tracking-wider mb-1">Total Insiden Dilaporkan</p>
                <h3 class="text-4xl font-black text-white" id="kpiTotalIncidents">
                    <i class="fa-solid fa-spinner fa-spin text-sm text-emerald-300"></i>
                </h3>
                <p class="text-xs text-emerald-100 mt-2 font-medium"><i class="fa-solid fa-rotate text-emerald-200"></i> Klik untuk melihat daftar</p>
            </div>
        </div>
    </div>

    {{-- Area Grafik (Charts) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <div class="mb-4">
                <h3 class="text-base font-bold text-gray-800">Distribusi Insiden per Ruang</h3>
                <p class="text-xs text-gray-500">Klik pada batang grafik untuk melihat log insiden di ruang tersebut.</p>
            </div>
            <div class="relative h-64 w-full">
                <canvas id="roomChart"></canvas>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <div class="mb-4">
                <h3 class="text-base font-bold text-gray-800">Tingkat Keparahan (Severity)</h3>
                <p class="text-xs text-gray-500">Klik pada batang grafik untuk melihat log insiden berdasarkan urgensi.</p>
            </div>
            <div class="relative h-64 w-full">
                <canvas id="severityChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const colors = {
            emerald: 'rgba(52, 211, 153, 0.8)', emeraldBorder: 'rgb(5, 150, 105)',
            red: 'rgba(248, 113, 113, 0.8)', redBorder: 'rgb(220, 38, 38)',
            amber: 'rgba(251, 191, 36, 0.8)', amberBorder: 'rgb(217, 119, 6)',
            blue: 'rgba(96, 165, 250, 0.8)', blueBorder: 'rgb(37, 99, 235)'
        };

        // Inisialisasi Chart Ruangan
        const ctxRoom = document.getElementById('roomChart').getContext('2d');
        const roomChart = new Chart(ctxRoom, {
            type: 'bar',
            data: { labels: [], datasets: [{ label: 'Jumlah Laporan', data: [], backgroundColor: colors.emerald, borderColor: colors.emeraldBorder, borderWidth: 1, borderRadius: 6 }] },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { legend: { display: false } }, 
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
                // Logika Klik Batang Grafik Ruangan
                onClick: (e, elements, chart) => {
                    if (elements.length > 0) {
                        const index = elements[0].index;
                        const roomLabel = chart.data.labels[index];
                        // Mengarahkan ke halaman log insiden dengan membawa parameter filter ruang
                        window.location.href = "{{ route('incidents.index') }}?room=" + encodeURIComponent(roomLabel);
                    }
                }
            }
        });

        // Inisialisasi Chart Tingkat Keparahan
        const ctxSeverity = document.getElementById('severityChart').getContext('2d');
        const severityChart = new Chart(ctxSeverity, {
            type: 'bar',
            data: { labels: [], datasets: [{ label: 'Jumlah Insiden', data: [], backgroundColor: [], borderColor: [], borderWidth: 1, borderRadius: 6 }] },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { legend: { display: false } }, 
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
                // Logika Klik Batang Grafik Tingkat Keparahan
                onClick: (e, elements, chart) => {
                    if (elements.length > 0) {
                        const index = elements[0].index;
                        const severityLabel = chart.data.labels[index];
                        // Mengarahkan ke halaman log insiden dengan membawa parameter filter keparahan
                        window.location.href = "{{ route('incidents.index') }}?severity=" + encodeURIComponent(severityLabel);
                    }
                }
            }
        });

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        async function fetchDashboardStats() {
            try {
                const response = await fetch("{{ route('admin.dashboard.stats') }}", {
                    credentials: 'same-origin', 
                    headers: { 
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken 
                    }
                });
                
                if(!response.ok) throw new Error('Gagal mengambil data dari server');
                const data = await response.json();

                // 1. Update Teks KPI
                document.getElementById('kpiTotalUsers').innerText = data.total_users + " Orang";
                document.getElementById('kpiUserTitle').innerText = data.user_title;       
                document.getElementById('kpiUserSubtitle').innerText = data.user_subtitle; 
                document.getElementById('kpiTotalIncidents').innerText = data.total_incidents + " Insiden";

                // --- POSISI KODE BARU DIMULAI DI SINI ---

                // 2. Update Data Chart Ruangan
                const allRooms = ['Ruang 1', 'Ruang 2', 'Ruang 3', 'Ruang 4', 'Ruang 5'];
                const roomValues = allRooms.map(room => {
                    const found = data.room_data.find(item => item.room_id === room);
                    return found ? found.total : 0;
                });
                roomChart.data.labels = allRooms;
                roomChart.data.datasets[0].data = roomValues;
                roomChart.update();

                // 3. Update Data Chart Keparahan
                const allSeverities = ['Normal', 'Warning', 'Critical'];
                const sevValues = allSeverities.map(sev => {
                    const found = data.severity_data.find(item => item.severity_level === sev);
                    return found ? found.total : 0;
                });
                
                severityChart.data.labels = allSeverities;
                severityChart.data.datasets[0].data = sevValues;

                // Update warna agar sesuai status
                severityChart.data.datasets[0].backgroundColor = allSeverities.map(level => {
                    if(level === 'Critical') return colors.red;
                    if(level === 'Warning') return colors.amber;
                    return colors.blue;
                });
                severityChart.data.datasets[0].borderColor = allSeverities.map(level => {
                    if(level === 'Critical') return colors.redBorder;
                    if(level === 'Warning') return colors.amberBorder;
                    return colors.blueBorder;
                });
                severityChart.update();

            } catch (error) {
                console.error("Kesalahan sinkronisasi data:", error);
                document.getElementById('kpiTotalUsers').innerText = "Gagal (Error)";
                document.getElementById('kpiTotalIncidents').innerText = "Gagal (Error)";
            }
        }

        fetchDashboardStats();
        setInterval(fetchDashboardStats, 5000); 
    });
</script>
@endsection