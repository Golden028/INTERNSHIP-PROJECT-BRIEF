<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Greenfields Operational Systems</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-slate-900 flex items-center justify-center min-h-screen p-4">

    <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-md border border-gray-100">
        <div class="text-center mb-6">
            <h2 class="text-3xl font-bold text-green-800">Greenfields</h2>
            <p class="text-sm text-gray-500 mt-1">Sistem Manajemen Insiden Terintegrasi</p>
        </div>

        @if($errors->any())
            <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4 text-xs font-semibold">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700">Email Perusahaan</label>
                <input type="email" name="email" required placeholder="staf@greenfields.com" class="w-full mt-1 p-2.5 border border-gray-300 rounded-lg text-sm focus:outline-green-600">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" name="password" required placeholder="••••••••" class="w-full mt-1 p-2.5 border border-gray-300 rounded-lg text-sm focus:outline-green-600">
            </div>
            <button type="submit" class="w-full bg-green-700 hover:bg-green-800 text-white p-2.5 rounded-lg font-bold text-sm shadow transition-all">Masuk Ke Dashboard</button>
        </form>

        <div class="mt-6 text-center text-xs text-gray-500">
            Staf Baru Lapangan? <a href="{{ route('register') }}" class="text-green-700 font-bold hover:underline">Daftar Akun Di Sini</a>
        </div>
    </div>

</body>
</html>