<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Greenfields Operational Incident Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        @keyframes pulse-red {
            0%, 100% { background-color: rgba(254, 226, 226, 1); }
            50% { background-color: rgba(254, 202, 202, 1); }
        }
        .highlight-critical {
            animation: pulse-red 2s infinite;
            border-left: 6px solid #dc2626;
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 font-sans p-6">

    <div class="max-w-6xl mx-auto">
        <header class="flex justify-between items-center mb-6 border-b pb-4 border-gray-300">
            <div>
                <h1 class="text-2xl font-bold text-green-800">Greenfields Real-time Incident Dashboard</h1>
                <p class="text-sm text-gray-600">MVP System - Spek Ringan & Teroptimasi Tanpa ORM</p>
            </div>
            <span class="bg-green-100 text-green-800 text-xs font-semibold px-3 py-1 rounded-full border border-green-300">Infrastruktur Lokal Aktif</span>
        </header>

        @if(session('success'))
            <div class="mb-4 bg-green-500 text-white p-3 rounded shadow-sm text-sm">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-5 rounded-lg shadow-md border border-gray-200 h-fit">
                <h2 class="text-lg font-semibold mb-4 text-gray-700">Catat Insiden Baru</h2>
                <form action="{{ route('incidents.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Judul Insiden</label>
                        <input type="text" name="title" required max="150" class="w-full mt-1 p-2 border border-gray-300 rounded focus:outline-green-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Deskripsi Kendala</label>
                        <textarea name="description" rows="3" class="w-full mt-1 p-2 border border-gray-300 rounded focus:outline-green-500 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Tingkat Severity (Urgensi)</label>
                        <select name="severity_level" required class="w-full mt-1 p-2 border border-gray-300 rounded focus:outline-green-500 text-sm">
                            <option value="Normal">Normal</option>
                            <option value="Warning">Warning</option>
                            <option value="Critical">Critical (Butuh Tindakan Cepat)</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full bg-green-700 hover:bg-green-800 text-white p-2 rounded font-semibold text-sm transition shadow">Simpan Log Insiden</button>
                </form>
            </div>

            <div class="bg-white p-5 rounded-lg shadow-md border border-gray-200 md:col-span-2 overflow-x-auto">
                <h2 class="text-lg font-semibold mb-4 text-gray-700">Daftar Insiden Aktif (Terprioritaskan)</h2>
                <table class="w-full border-collapse text-left text-sm">
                    <thead>
                        <tr class="bg-gray-200 text-gray-700 uppercase text-xs border-b border-gray-300">
                            <th class="p-3">Urgensi</th>
                            <th class="p-3">Insiden</th>
                            <th class="p-3">Status</th>
                            <th class="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($incidents as $incident)
                            <tr class="{{ $incident->severity_level === 'Critical' ? 'highlight-critical' : '' }} hover:bg-gray-50">
                                <td class="p-3 font-semibold">
                                    @if($incident->severity_level === 'Critical')
                                        <span class="text-red-600 bg-red-100 px-2 py-0.5 rounded text-xs border border-red-300">⚠️ CRITICAL</span>
                                    @elseif($incident->severity_level === 'Warning')
                                        <span class="text-amber-600 bg-amber-50 px-2 py-0.5 rounded text-xs border border-amber-300">⏳ Warning</span>
                                    @else
                                        <span class="text-blue-600 bg-blue-50 px-2 py-0.5 rounded text-xs border border-blue-200">Normal</span>
                                    @endif
                                </td>
                                <td class="p-3">
                                    <div class="font-bold text-gray-900">{{ $incident->title }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $incident->description ?? 'Tidak ada deskripsi' }}</div>
                                    <div class="text-[10px] text-gray-400 mt-1">Dilaporkan: {{ $incident->created_at }}</div>
                                </td>
                                <td class="p-3">
                                    <form action="{{ route('incidents.update', $incident->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <select name="status" onchange="this.form.submit()" class="text-xs p-1 border rounded bg-white font-medium cursor-pointer">
                                            <option value="Open" {{ $incident->status == 'Open' ? 'selected' : '' }}>Open</option>
                                            <option value="In Progress" {{ $incident->status == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                                            <option value="Resolved" {{ $incident->status == 'Resolved' ? 'selected' : '' }}>Resolved</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="p-3 text-center">
                                    <form action="{{ route('incidents.destroy', $incident->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin melakukan soft-delete pada insiden ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-semibold bg-red-50 px-2 py-1 rounded border border-red-200 hover:bg-red-100">Soft-Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-4 text-center text-gray-500 bg-gray-50">Tidak ada log insiden operasional aktif saat ini. Data aman.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>