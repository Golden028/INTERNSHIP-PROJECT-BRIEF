@extends('layout.app')

@section('page_title', 'Pusat Notifikasi')

@section('content')
<div class="space-y-6">

    {{-- Header & Aksi --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <p class="text-sm text-gray-600">Riwayat seluruh aktivitas dan notifikasi sistem yang masuk ke akun Anda.</p>
        <button onclick="markAllReadPage()"
                class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2 rounded-lg text-sm shadow transition flex items-center gap-2 whitespace-nowrap">
            <i class="fa-solid fa-check-double"></i> Tandai Semua Dibaca
        </button>
    </div>

    {{-- Filter --}}
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Filter Tipe</label>
            <select id="filterType" onchange="filterAndPaginate()" class="w-full mt-1.5 p-2 bg-slate-50 border border-gray-300 rounded-lg text-xs focus:outline-emerald-600 font-semibold text-gray-700">
                <option value="ALL">Semua Tipe</option>
                <option value="incident_new">Laporan Insiden Baru</option>
                <option value="incident_edit">Edit Insiden</option>
                <option value="incident_delete">Hapus Insiden</option>
                <option value="incident_status">Perubahan Status</option>
                <option value="user_add">Tambah Pengguna</option>
                <option value="user_edit">Edit Pengguna</option>
                <option value="user_delete">Hapus Pengguna</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Filter Status Baca</label>
            <select id="filterRead" onchange="filterAndPaginate()" class="w-full mt-1.5 p-2 bg-slate-50 border border-gray-300 rounded-lg text-xs focus:outline-emerald-600 font-semibold text-gray-700">
                <option value="ALL">Semua Status</option>
                <option value="unread">Belum Dibaca</option>
                <option value="read">Sudah Dibaca</option>
            </select>
        </div>
        <div class="flex items-end">
            <button onclick="resetFilter()" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2 px-4 rounded-lg border border-gray-200 transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-rotate-left"></i> Reset Filter
            </button>
        </div>
    </div>

    {{-- Entries control + Pagination atas --}}
    <div class="flex flex-row justify-between items-center bg-gray-50 px-4 py-2 border border-gray-200 rounded-xl text-xs font-medium text-gray-600">
        <div class="flex items-center gap-2">
            <span>Tampilkan</span>
            <select id="perPageSelect" onchange="changePerPage()" class="p-1.5 border border-gray-300 rounded-md bg-white focus:outline-emerald-600 font-semibold cursor-pointer">
                <option value="10">10</option>
                <option value="20">20</option>
                <option value="50">50</option>
            </select>
            <span>data</span>
        </div>
        <div id="topPagination" class="flex items-center gap-1"></div>
    </div>

    {{-- Tabel Notifikasi --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    <th class="px-6 py-4 w-10"></th>
                    <th class="px-6 py-4">Notifikasi</th>
                    <th class="px-6 py-4 whitespace-nowrap">Tipe</th>
                    <th class="px-6 py-4 whitespace-nowrap">Waktu</th>
                    <th class="px-6 py-4 text-center whitespace-nowrap">Status</th>
                    <th class="px-6 py-4 text-center whitespace-nowrap">Aksi</th>
                </tr>
            </thead>
            <tbody id="notifTableBody" class="text-sm text-gray-700 divide-y divide-gray-100">
                {{-- Diisi JS --}}
            </tbody>
        </table>
        <div id="notifTableEmpty" class="hidden p-10 text-center text-gray-400 text-sm font-medium bg-gray-50/50">
            <i class="fa-regular fa-bell-slash text-3xl mb-2 block"></i>
            Tidak ada notifikasi yang sesuai dengan filter.
        </div>
    </div>

    {{-- Info + Pagination bawah --}}
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 bg-white p-4 rounded-xl border border-gray-200 text-xs font-medium text-gray-500 shadow-sm">
        <div id="paginationInfo">Memuat data...</div>
        <div id="bottomPagination" class="flex items-center gap-1"></div>
    </div>

</div>

{{-- Toast container --}}
<div id="toastContainer" class="fixed top-5 right-5 z-[9999] pointer-events-none flex flex-col gap-3"></div>

<script>
(() => {
    'use strict';

    const FETCH_URL    = '{{ route("notifications.fetch") }}';
    const READ_URL_TPL = '/notifications/read/';
    const READ_ALL_URL = '{{ route("notifications.read_all") }}';
    const CSRF         = document.querySelector('meta[name="csrf-token"]').content;

    const TYPE_LABEL = {
        incident_new:    'Laporan Baru',
        incident_edit:   'Edit Insiden',
        incident_delete: 'Hapus Insiden',
        incident_status: 'Ubah Status',
        user_add:        'Tambah User',
        user_edit:       'Edit User',
        user_delete:     'Hapus User',
    };
    const TYPE_COLOR = {
        incident_new:    'bg-red-100 text-red-700',
        incident_edit:   'bg-blue-100 text-blue-700',
        incident_delete: 'bg-rose-100 text-rose-700',
        incident_status: 'bg-indigo-100 text-indigo-700',
        user_add:        'bg-emerald-100 text-emerald-700',
        user_edit:       'bg-amber-100 text-amber-700',
        user_delete:     'bg-red-100 text-red-700',
    };
    const TYPE_ICON = {
        incident_new:    'fa-triangle-exclamation text-red-500',
        incident_edit:   'fa-pen-to-square text-blue-500',
        incident_delete: 'fa-trash text-rose-500',
        incident_status: 'fa-rotate text-indigo-500',
        user_add:        'fa-user-plus text-emerald-600',
        user_edit:       'fa-user-pen text-amber-500',
        user_delete:     'fa-user-slash text-red-600',
    };
    const TYPE_URL = {
        incident_new: '/incidents', incident_edit: '/incidents',
        incident_delete: '/incidents', incident_status: '/incidents',
        user_add: '/admin/users', user_edit: '/admin/users', user_delete: '/admin/users',
    };

    let allData     = [];
    let filtered    = [];
    let currentPage = 1;
    let rowsPerPage = 10;

    // ── Fetch semua data ────────────────────────────────────────────────────
    async function loadAll() {
        try {
            const res  = await fetch(FETCH_URL + '?limit=500', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const json = await res.json();
            allData = json.notifications || [];
            filterAndPaginate();
        } catch (e) {
            showToast('Gagal memuat notifikasi.', 'error');
        }
    }

    // ── Filter ──────────────────────────────────────────────────────────────
    function filterAndPaginate() {
        const fType = document.getElementById('filterType').value;
        const fRead = document.getElementById('filterRead').value;

        filtered = allData.filter(n => {
            const matchType = fType === 'ALL' || n.type === fType;
            const matchRead = fRead === 'ALL'
                || (fRead === 'unread' && !n.is_read)
                || (fRead === 'read'   &&  n.is_read);
            return matchType && matchRead;
        });

        currentPage = 1;
        renderTable();
    }

    // ── Render tabel ────────────────────────────────────────────────────────
    function renderTable() {
        const tbody = document.getElementById('notifTableBody');
        const empty = document.getElementById('notifTableEmpty');
        const total = filtered.length;
        const totalPages = Math.ceil(total / rowsPerPage) || 1;
        if (currentPage > totalPages) currentPage = totalPages;

        const start = (currentPage - 1) * rowsPerPage;
        const end   = Math.min(start + rowsPerPage, total);
        const slice = filtered.slice(start, end);

        if (!slice.length) {
            tbody.innerHTML = '';
            empty.classList.remove('hidden');
            document.getElementById('paginationInfo').textContent = 'Tidak ada data';
            renderPagination('topPagination', totalPages);
            renderPagination('bottomPagination', totalPages);
            return;
        }
        empty.classList.add('hidden');

        const frag = document.createDocumentFragment();
        slice.forEach(n => {
            const tr   = document.createElement('tr');
            const icon = TYPE_ICON[n.type]  || 'fa-bell text-gray-400';
            const lbl  = TYPE_LABEL[n.type] || n.type;
            const clr  = TYPE_COLOR[n.type] || 'bg-gray-100 text-gray-600';
            const dest = TYPE_URL[n.type]   || '#';

            tr.className = `hover:bg-gray-50/60 transition ${!n.is_read ? 'bg-emerald-50/40' : ''}`;
            tr.dataset.id   = n.id;
            tr.dataset.read = n.is_read ? '1' : '0';

            tr.innerHTML = `
                <td class="px-6 py-4">
                    <i class="fa-solid ${icon} text-base"></i>
                </td>
                <td class="px-6 py-4 max-w-xs">
                    <div class="font-bold text-gray-900 text-sm leading-snug flex items-center gap-2">
                        ${!n.is_read ? '<span class="inline-block w-2 h-2 bg-emerald-500 rounded-full flex-shrink-0"></span>' : ''}
                        ${escHtml(n.title)}
                    </div>
                    <div class="text-xs text-gray-500 mt-0.5 leading-relaxed">${escHtml(n.body || '')}</div>
                    <div class="text-[10px] text-gray-400 mt-1 flex items-center gap-1">
                        <i class="fa-regular fa-user text-[9px]"></i> ${escHtml(n.triggered_by_name || '-')}
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-[10px] font-bold rounded-md uppercase tracking-wide ${clr}">${lbl}</span>
                </td>
                <td class="px-6 py-4 text-xs text-gray-400 whitespace-nowrap">${escHtml(n.time_ago)}</td>
                <td class="px-6 py-4 text-center whitespace-nowrap">
                    ${n.is_read
                        ? '<span class="text-[10px] font-semibold text-gray-400 bg-gray-100 px-2 py-1 rounded-md">Dibaca</span>'
                        : '<span class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-1 rounded-md">Baru</span>'
                    }
                </td>
                <td class="px-6 py-4 text-center whitespace-nowrap">
                    <button onclick="goToNotif(${n.id}, '${dest}', this.closest('tr'))"
                            class="text-xs font-semibold text-emerald-600 hover:text-emerald-800 hover:underline mr-3 transition">
                        <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i> Buka
                    </button>
                    ${!n.is_read ? `<button onclick="markRowRead(${n.id}, this.closest('tr'))" class="text-xs font-semibold text-blue-500 hover:text-blue-700 hover:underline transition">Tandai dibaca</button>` : ''}
                </td>
            `;
            frag.appendChild(tr);
        });

        tbody.innerHTML = '';
        tbody.appendChild(frag);

        // Info
        document.getElementById('paginationInfo').textContent =
            `Menampilkan ${start + 1} sampai ${end} dari ${total} notifikasi`;

        renderPagination('topPagination', totalPages);
        renderPagination('bottomPagination', totalPages);
    }

    // ── Pagination buttons ──────────────────────────────────────────────────
    function renderPagination(containerId, totalPages) {
        const container = document.getElementById(containerId);
        container.innerHTML = '';
        if (totalPages <= 1) return;

        const mkBtn = (label, page, disabled, active) => {
            const btn = document.createElement('button');
            btn.innerHTML = label;
            btn.className = `px-2.5 py-1.5 rounded-lg border text-xs font-bold transition ${
                active   ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' :
                disabled ? 'bg-gray-100 text-gray-400 cursor-not-allowed border-gray-200' :
                           'bg-white text-gray-700 hover:bg-gray-50 border-gray-300'
            }`;
            if (!disabled && !active) btn.onclick = () => { currentPage = page; renderTable(); };
            return btn;
        };

        container.appendChild(mkBtn('<i class="fa-solid fa-angle-left"></i>', currentPage - 1, currentPage === 1, false));
        for (let i = 1; i <= totalPages; i++) {
            container.appendChild(mkBtn(i, i, false, i === currentPage));
        }
        container.appendChild(mkBtn('<i class="fa-solid fa-angle-right"></i>', currentPage + 1, currentPage === totalPages, false));
    }

    // ── Aksi tombol ─────────────────────────────────────────────────────────
    async function goToNotif(id, url, tr) {
        await markRowRead(id, tr);
        window.location.href = url;
    }

    async function markRowRead(id, tr) {
        if (!tr || tr.dataset.read === '1') return;
        try {
            await fetch(READ_URL_TPL + id, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' } });
            tr.dataset.read = '1';
            tr.classList.remove('bg-emerald-50/40');
            // Update data lokal
            const found = allData.find(n => n.id === id);
            if (found) found.is_read = true;
            // Update badge global
            if (window.updateGlobalBadge) window.updateGlobalBadge(-1);
            renderTable();
        } catch (e) {}
    }

    async function markAllReadPage() {
        try {
            await fetch(READ_ALL_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' } });
            allData.forEach(n => n.is_read = true);
            if (window.updateGlobalBadge) window.updateGlobalBadge(0, true);
            filterAndPaginate();
            showToast('Semua notifikasi telah ditandai dibaca.', 'success');
        } catch (e) {}
    }

    function resetFilter() {
        document.getElementById('filterType').value = 'ALL';
        document.getElementById('filterRead').value = 'ALL';
        filterAndPaginate();
    }

    function changePerPage() {
        rowsPerPage = parseInt(document.getElementById('perPageSelect').value);
        currentPage = 1;
        renderTable();
    }

    // ── Toast helper ────────────────────────────────────────────────────────
    function showToast(msg, type = 'success') {
        const c   = document.getElementById('toastContainer');
        const div = document.createElement('div');
        const cls = type === 'success' ? 'bg-green-600 border-green-500/30' : 'bg-red-600 border-red-500/30';
        div.className = `pointer-events-auto ${cls} text-white px-5 py-3 rounded-xl text-sm font-bold shadow-2xl flex items-center gap-2 border min-w-[280px]`;
        div.innerHTML = `<i class="fa-solid ${type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'}"></i><span>${msg}</span>`;
        c.appendChild(div);
        setTimeout(() => { div.style.opacity = '0'; div.style.transition = 'opacity 0.4s'; setTimeout(() => div.remove(), 400); }, 3000);
    }

    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // Expose ke global agar topbar bisa update badge
    window.updateGlobalBadge = (delta, reset = false) => {
        const badge = document.getElementById('notifBadge');
        const sb    = document.getElementById('sidebarNotifBadge');
        let count   = parseInt(badge.textContent) || 0;
        if (reset) count = 0; else count = Math.max(0, count + delta);
        if (count > 0) {
            const txt = count > 99 ? '99+' : count;
            badge.textContent = txt; badge.classList.remove('hidden'); badge.classList.add('flex');
            if (sb) { sb.textContent = txt; sb.classList.remove('hidden'); }
        } else {
            badge.classList.add('hidden'); badge.classList.remove('flex');
            if (sb) sb.classList.add('hidden');
        }
    };

    window.markAllReadPage  = markAllReadPage;
    window.goToNotif        = goToNotif;
    window.markRowRead      = markRowRead;
    window.changePerPage    = changePerPage;
    window.filterAndPaginate = filterAndPaginate;
    window.resetFilter      = resetFilter;

    document.addEventListener('DOMContentLoaded', loadAll);
})();
</script>
@endsection