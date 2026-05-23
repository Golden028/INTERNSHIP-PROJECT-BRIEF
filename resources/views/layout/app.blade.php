<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Greenfields Operational MVP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes pulse-red {
            0%, 100% { background-color: rgba(254, 226, 226, 1); }
            50%       { background-color: rgba(254, 202, 202, 1); }
        }
        .highlight-critical { animation: pulse-red 2s infinite; border-left: 6px solid #dc2626; }
        #notifDropdown { width: 480px; }
        #notifList { max-height: 360px; overflow-y: auto; scrollbar-width: thin; scrollbar-color: #d1d5db transparent; }
        #notifList::-webkit-scrollbar { width: 4px; }
        #notifList::-webkit-scrollbar-track { background: transparent; }
        #notifList::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
        @keyframes badge-pop {
            0% { transform: scale(0.5); opacity: 0; }
            70% { transform: scale(1.2); }
            100% { transform: scale(1); opacity: 1; }
        }
        .badge-pop { animation: badge-pop 0.35s ease forwards; }
        .notif-unread { background-color: #f0fdf4; border-left: 3px solid #10b981; }
        .notif-read   { background-color: #fff; border-left: 3px solid transparent; }
        .notif-item   { transition: background 0.15s; cursor: pointer; }
        .notif-item:hover { background-color: #f8fafc !important; }
        @keyframes slide-down {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .notif-slide { animation: slide-down 0.18s ease; }
    </style>
</head>
<body class="bg-gray-100 font-sans flex h-screen overflow-hidden">

    <aside class="w-64 bg-slate-900 text-white flex flex-col justify-between flex-shrink-0">
        <div>
            <div class="p-5 text-xl font-bold tracking-wider border-b border-slate-800 flex items-center gap-3">
                <i class="fa-solid fa-leaf text-emerald-400"></i>
                <span>Greenfields</span>
            </div>
            <nav class="p-4 space-y-2">
                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }} font-medium transition">
                        <i class="fa-solid fa-chart-line w-5"></i> Dashboard Admin
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.users.index') ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }} font-medium transition">
                        <i class="fa-solid fa-users w-5"></i> Kelola Pengguna
                    </a>
                @else
                    <a href="{{ route('incidents.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('incidents.dashboard') ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }} font-medium transition">
                        <i class="fa-solid fa-gauge-high w-5"></i> Dashboard User
                    </a>
                @endif
                <a href="{{ route('incidents.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('incidents.index') ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }} transition">
                    <i class="fa-solid fa-triangle-exclamation w-5"></i> Log Insiden
                </a>
                <a href="{{ route('notifications.page') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('notifications.page') ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }} transition">
                    <i class="fa-solid fa-bell w-5"></i> Notifikasi
                    <span id="sidebarNotifBadge" class="hidden ml-auto bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full leading-none"></span>
                </a>
            </nav>
        </div>
        <div class="p-4 border-t border-slate-800 text-xs text-slate-500 bg-slate-950 font-mono text-center">
            Role: <span class="text-emerald-400 font-bold uppercase">{{ auth()->user()->role }}</span>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <header class="bg-white shadow-sm border-b border-gray-200 h-16 flex items-center justify-between px-8 z-10 flex-shrink-0">
            <div class="text-md font-semibold text-gray-800">@yield('page_title', 'Operational Portal')</div>
            <div class="flex items-center gap-5">

                {{-- TOMBOL NOTIFIKASI --}}
                <div class="relative" id="notifWrapper">
                    <button id="notifToggle" onclick="toggleNotifDropdown()"
                            class="relative text-gray-400 hover:text-gray-700 p-2 rounded-full hover:bg-gray-100 transition focus:outline-none" title="Notifikasi">
                        <i class="fa-solid fa-bell text-xl"></i>
                        <span id="notifBadge" class="hidden absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 bg-red-500 text-white text-[10px] font-bold rounded-full items-center justify-center leading-none badge-pop">0</span>
                    </button>

                    <div id="notifDropdown" class="hidden notif-slide absolute right-0 top-12 bg-white border border-gray-200 rounded-2xl shadow-2xl z-[9999] overflow-hidden" style="width:480px; display:none; flex-direction:column;">
                        {{-- Header --}}
                        <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100 bg-gray-50/80 flex-shrink-0">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-bell text-emerald-600 text-sm"></i>
                                <span class="text-sm font-bold text-gray-800">Notifikasi</span>
                                <span id="notifCountLabel" class="hidden text-[10px] font-bold bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">0 baru</span>
                            </div>
                            <a href="{{ route('notifications.page') }}" onclick="closeNotifDropdown()"
                               class="text-[11px] font-semibold text-emerald-600 hover:text-emerald-800 hover:underline transition flex items-center gap-1">
                                Lihat semua <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                        {{-- List --}}
                        <div id="notifList" class="divide-y divide-gray-100">
                            <div id="notifEmpty" class="flex flex-col items-center justify-center py-10 text-gray-400 gap-2">
                                <i class="fa-regular fa-bell-slash text-3xl"></i>
                                <p class="text-xs font-medium">Belum ada notifikasi</p>
                            </div>
                        </div>
                        {{-- Footer --}}
                        <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/50 flex items-center justify-between flex-shrink-0">
                            <span class="text-[11px] text-gray-400">Menampilkan 10 terbaru</span>
                            <a href="{{ route('notifications.page') }}" onclick="closeNotifDropdown()"
                               class="text-[11px] font-semibold text-emerald-600 hover:underline flex items-center gap-1">
                                <i class="fa-solid fa-list text-[10px]"></i> Semua notifikasi
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Avatar --}}
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 border-l border-gray-200 pl-5 group cursor-pointer select-none">
                    @if(isset(auth()->user()->avatar) && auth()->user()->avatar != null)
                        <img src="{{ asset('avatars/' . auth()->user()->avatar) }}" class="w-8 h-8 rounded-full object-cover shadow-sm ring-2 ring-emerald-500/20 group-hover:scale-105 transition-all">
                    @else
                        <div class="w-8 h-8 rounded-full bg-emerald-800 text-white flex items-center justify-center font-bold text-xs group-hover:bg-emerald-700 transition-all shadow-sm">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                    @endif
                    <div class="leading-tight hidden sm:block text-left">
                        <div class="text-sm font-semibold text-gray-800 group-hover:text-emerald-600 transition-all">{{ auth()->user()->name }}</div>
                        <div class="text-[10px] text-gray-400 uppercase font-bold">{{ auth()->user()->role }}</div>
                    </div>
                </a>
                <form action="{{ route('logout') }}" method="POST" class="inline m-0">
                    @csrf
                    <button type="submit" class="text-gray-400 hover:text-red-600 p-2 transition" title="Logout">
                        <i class="fa-solid fa-power-off text-lg"></i>
                    </button>
                </form>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-8">@yield('content')</main>
    </div>

    <script>
    (() => {
        'use strict';
        const FETCH_URL    = '{{ route("notifications.fetch") }}';
        const READ_URL_TPL = '/notifications/read/';
        const POLL_MS      = 30000;
        const CSRF         = document.querySelector('meta[name="csrf-token"]').content;
        let notifData = [], unreadCount = 0, dropdownOpen = false;

        const TYPE_META = {
            incident_new:    { icon: 'fa-triangle-exclamation', color: 'text-red-500',     bg: 'bg-red-50' },
            incident_edit:   { icon: 'fa-pen-to-square',        color: 'text-blue-500',    bg: 'bg-blue-50' },
            incident_delete: { icon: 'fa-trash',                color: 'text-rose-500',    bg: 'bg-rose-50' },
            incident_status: { icon: 'fa-rotate',               color: 'text-indigo-500',  bg: 'bg-indigo-50' },
            user_add:        { icon: 'fa-user-plus',            color: 'text-emerald-600', bg: 'bg-emerald-50' },
            user_edit:       { icon: 'fa-user-pen',             color: 'text-amber-500',   bg: 'bg-amber-50' },
            user_delete:     { icon: 'fa-user-slash',           color: 'text-red-600',     bg: 'bg-red-50' },
        };
        const TYPE_URL = {
            incident_new: '/incidents', incident_edit: '/incidents',
            incident_delete: '/incidents', incident_status: '/incidents',
            user_add: '/admin/users', user_edit: '/admin/users', user_delete: '/admin/users',
        };

        async function fetchNotifications() {
            try {
                const res  = await fetch(FETCH_URL + '?limit=10', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const json = await res.json();
                notifData   = json.notifications || [];
                unreadCount = json.unread_count  || 0;
                renderBadge();
                if (dropdownOpen) renderList();
            } catch (e) {}
        }

        function renderBadge() {
            const badge = document.getElementById('notifBadge');
            const label = document.getElementById('notifCountLabel');
            const sb    = document.getElementById('sidebarNotifBadge');
            if (unreadCount > 0) {
                const txt = unreadCount > 99 ? '99+' : unreadCount;
                badge.textContent = txt; badge.classList.remove('hidden'); badge.classList.add('flex');
                label.textContent = txt + ' baru'; label.classList.remove('hidden');
                if (sb) { sb.textContent = txt; sb.classList.remove('hidden'); }
            } else {
                badge.classList.add('hidden'); badge.classList.remove('flex');
                label.classList.add('hidden');
                if (sb) sb.classList.add('hidden');
            }
        }

        function renderList() {
            const list  = document.getElementById('notifList');
            const empty = document.getElementById('notifEmpty');
            if (!notifData.length) {
                list.innerHTML = ''; list.appendChild(empty); empty.classList.remove('hidden'); return;
            }
            empty.classList.add('hidden');
            const frag = document.createDocumentFragment();
            notifData.forEach(n => {
                const meta    = TYPE_META[n.type] || { icon: 'fa-bell', color: 'text-gray-500', bg: 'bg-gray-50' };
                const destUrl = TYPE_URL[n.type]  || '/notifications';
                const div     = document.createElement('div');
                div.className = `notif-item flex gap-3 px-5 py-3.5 ${n.is_read ? 'notif-read' : 'notif-unread'}`;
                div.dataset.id = n.id; div.dataset.read = n.is_read ? '1' : '0';
                div.innerHTML = `
                    <div class="flex-shrink-0 w-9 h-9 rounded-full ${meta.bg} flex items-center justify-center mt-0.5">
                        <i class="fa-solid ${meta.icon} ${meta.color} text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-xs font-bold text-gray-800 leading-snug">${escHtml(n.title)}</p>
                            ${!n.is_read ? '<span class="flex-shrink-0 w-2 h-2 bg-emerald-500 rounded-full mt-1"></span>' : ''}
                        </div>
                        <p class="text-[11px] text-gray-500 mt-0.5 leading-relaxed">${escHtml(n.body || '')}</p>
                        <div class="flex items-center gap-1.5 mt-1">
                            <i class="fa-regular fa-clock text-[10px] text-gray-400"></i>
                            <span class="text-[10px] text-gray-400 font-medium">${escHtml(n.time_ago)}</span>
                        </div>
                    </div>
                `;
                div.addEventListener('click', async () => { await markOneRead(n.id, div); window.location.href = destUrl; });
                frag.appendChild(div);
            });
            list.innerHTML = ''; list.appendChild(frag);
        }

        async function markOneRead(id, el) {
            if (el && el.dataset.read === '1') return;
            try {
                await fetch(READ_URL_TPL + id, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' } });
                if (el) { el.dataset.read = '1'; el.classList.replace('notif-unread','notif-read'); el.querySelector('.bg-emerald-500')?.remove(); }
                if (unreadCount > 0) { unreadCount--; renderBadge(); }
                const found = notifData.find(n => n.id === id); if (found) found.is_read = true;
            } catch (e) {}
        }

        function toggleNotifDropdown() {
            const dd = document.getElementById('notifDropdown');
            dropdownOpen = !dropdownOpen;
            if (dropdownOpen) { dd.style.display = 'flex'; renderList(); }
            else              { dd.style.display = 'none'; }
        }
        function closeNotifDropdown() {
            document.getElementById('notifDropdown').style.display = 'none';
            dropdownOpen = false;
        }
        document.addEventListener('click', e => {
            if (dropdownOpen && !document.getElementById('notifWrapper').contains(e.target)) closeNotifDropdown();
        });
        function escHtml(str) {
            return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        window.toggleNotifDropdown = toggleNotifDropdown;
        window.closeNotifDropdown  = closeNotifDropdown;
        document.addEventListener('DOMContentLoaded', () => { fetchNotifications(); setInterval(fetchNotifications, POLL_MS); });
    })();
    </script>
</body>
</html>