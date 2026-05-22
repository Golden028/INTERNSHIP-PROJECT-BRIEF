@extends('layouts.app')

@section('content')
<div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 overflow-x-auto">
    <h2 class="text-base font-bold mb-4 text-slate-800 border-b pb-2">Panel Kontrol Otoritas Administratif</h2>
    <table class="w-full text-left text-xs border-collapse">
        <thead>
            <tr class="bg-slate-100 text-slate-600 uppercase text-[10px] font-bold border-b">
                <th class="p-3">Urgensi</th>
                <th class="p-3">Detail Laporan</th>
                <th class="p-3">Perbarui Status</th>
                <th class="p-3 text-center">Tindakan Korektif</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($incidents as $incident)
                <tr class="{{ $incident->severity_level === 'Critical' ? 'highlight-critical' : '' }}">
                    <td class="p-3 font-bold">
                        <span class="px-2 py-0.5 rounded text-[10px] {{ $incident->severity_level === 'Critical' ? 'text-red-700 bg-red-100' : 'text-slate-600 bg-slate-100' }}">
                            {{ $incident->severity_level }}
                        </span>
                    </td>
                    <td class="p-3">
                        <div class="font-bold text-slate-900">{{ $incident->title }}</div>
                        <div class="text-slate-500 mt-0.5">{{ $incident->description }}</div>
                    </td>
                    <td class="p-3">
                        <form action="{{ route('incidents.update', $incident->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <select name="status" onchange="this.form.submit()" class="p-1 border rounded text-xs bg-white cursor-pointer focus:outline-green-600">
                                <option value="Open" {{ $incident->status == 'Open' ? 'selected' : '' }}>Open</option>
                                <option value="In Progress" {{ $incident->status == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="Resolved" {{ $incident->status == 'Resolved' ? 'selected' : '' }}>Resolved</option>
                            </select>
                        </form>
                    </td>
                    <td class="p-3 text-center">
                        <form action="{{ route('incidents.destroy', $incident->id) }}" method="POST" onsubmit="return confirm('Jalankan soft-delete pada log ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline font-bold">Soft-Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="p-4 text-center text-slate-400">Tidak ada log anomali operasional aktif.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection