<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Greenfields Operational Systems</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden font-sans bg-slate-900" 
      style="background-image: url('https://i.pinimg.com/736x/19/88/18/1988182bb29763c82b40964ce5b143b7.jpg'); background-size: cover; background-position: center;">

    <div class="absolute inset-0 bg-emerald-900/30 backdrop-blur-[2px] z-0"></div>

    <div class="bg-white/85 backdrop-blur-2xl p-8 sm:p-10 rounded-3xl shadow-2xl border border-white/50 w-full max-w-md relative z-10 transition-all">
        
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-sm border border-emerald-200">
                <i class="fa-solid fa-cow text-3xl"></i> 
            </div>
            <h2 class="text-3xl font-extrabold text-slate-800 tracking-tight">Greenfields</h2>
            <p class="text-sm text-slate-600 mt-2 font-medium">Sistem Manajemen Insiden Terintegrasi</p>
        </div>

        @if($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-r-lg mb-6 text-sm font-medium flex items-start gap-3 shadow-sm">
                <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="space-y-5">
            @csrf
            <div class="space-y-1.5">
                <label class="block text-sm font-bold text-slate-700">Email Perusahaan</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-regular fa-envelope"></i>
                    </div>
                    <input type="email" name="email" required placeholder="staf@greenfields.com" 
                        class="w-full pl-10 pr-4 py-3 bg-white/70 border border-slate-300 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-all outline-none shadow-inner">
                </div>
            </div>
            
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="block text-sm font-bold text-slate-700">Password</label>
                    <button type="button" onclick="openForgotModal()" class="text-xs font-bold text-emerald-700 hover:text-emerald-800 hover:underline transition-colors focus:outline-none">
                        Lupa Password?
                    </button>
                </div>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <input type="password" name="password" required placeholder="••••••••" 
                        class="w-full pl-10 pr-4 py-3 bg-white/70 border border-slate-300 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-all outline-none shadow-inner">
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-3 rounded-xl font-bold text-sm shadow-xl shadow-emerald-600/30 transition-all transform hover:-translate-y-0.5 focus:outline-none focus:ring-4 focus:ring-emerald-500/30">
                    Masuk Ke Dashboard <i class="fa-solid fa-arrow-right ml-1"></i>
                </button>
            </div>
        </form>
    </div>

    <div id="forgotPasswordModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4 opacity-0 transition-opacity duration-300">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden transform scale-95 transition-transform duration-300" id="modalContent">
            
            <div id="step1" class="p-8 space-y-6">
                <div class="text-center space-y-2">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-2">
                        <i class="fa-solid fa-shield-halved text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800">Lupa Password</h3>
                    <p class="text-xs text-slate-500">Masukkan email Anda. Kami akan mengirimkan kode OTP untuk verifikasi.</p>
                </div>
                <div>
                    <input type="email" id="resetEmail" placeholder="Email Anda..." class="w-full p-3 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
                </div>
                <div class="flex gap-3">
                    <button onclick="closeForgotModal()" class="w-full py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-xl text-sm transition">Batal</button>
                    <button onclick="sendOTP()" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-sm shadow-md transition">Kirim OTP</button>
                </div>
            </div>

            <div id="step2" class="p-8 space-y-6 hidden">
                <div class="text-center space-y-2">
                    <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-2 animate-pulse">
                        <i class="fa-solid fa-message text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800">Verifikasi OTP</h3>
                    <p class="text-xs text-slate-500">Masukkan 4 digit kode yang dikirim ke email Anda.</p>
                </div>
                <div class="flex justify-center gap-3">
                    <input type="text" maxlength="1" class="w-12 h-12 text-center text-xl font-bold border border-slate-200 rounded-xl focus:border-emerald-500 outline-none otp-input">
                    <input type="text" maxlength="1" class="w-12 h-12 text-center text-xl font-bold border border-slate-200 rounded-xl focus:border-emerald-500 outline-none otp-input">
                    <input type="text" maxlength="1" class="w-12 h-12 text-center text-xl font-bold border border-slate-200 rounded-xl focus:border-emerald-500 outline-none otp-input">
                    <input type="text" maxlength="1" class="w-12 h-12 text-center text-xl font-bold border border-slate-200 rounded-xl focus:border-emerald-500 outline-none otp-input">
                </div>
                <button onclick="verifyOTP()" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-sm shadow-md transition">Verifikasi</button>
            </div>

            <div id="step3" class="p-8 space-y-6 hidden">
                <div class="text-center space-y-2">
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-2">
                        <i class="fa-solid fa-key text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800">Buat Password Baru</h3>
                    <p class="text-xs text-slate-500">Pastikan password baru Anda kuat dan mudah diingat.</p>
                </div>
                <div class="space-y-4">
                    <input type="password" placeholder="Password Baru" class="w-full p-3 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
                    <input type="password" placeholder="Konfirmasi Password" class="w-full p-3 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
                </div>
                <button onclick="finishReset()" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-sm shadow-md transition">Simpan Password</button>
            </div>
        </div>
    </div>

    <div id="toastNotification" class="fixed top-5 right-5 z-[100] bg-slate-800 text-white px-5 py-3 rounded-xl shadow-2xl flex items-center gap-3 transform translate-y-[-100%] opacity-0 transition-all duration-300">
        <i class="fa-solid fa-bell text-emerald-400"></i>
        <span class="text-sm font-medium" id="toastMessage">Pesan</span>
    </div>

    <script>
        const modal = document.getElementById('forgotPasswordModal');
        const modalContent = document.getElementById('modalContent');
        const s1 = document.getElementById('step1');
        const s2 = document.getElementById('step2');
        const s3 = document.getElementById('step3');

        function openForgotModal() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modalContent.classList.remove('scale-95');
                modalContent.classList.add('scale-100');
            }, 10);
            
            s1.classList.remove('hidden');
            s2.classList.add('hidden');
            s3.classList.add('hidden');
        }

        function closeForgotModal() {
            modal.classList.add('opacity-0');
            modalContent.classList.remove('scale-100');
            modalContent.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 300);
        }

        function sendOTP() {
            const email = document.getElementById('resetEmail').value;
            if(!email) return alert('Mohon isi email terlebih dahulu!');
            showToast(`Kode OTP telah dikirim ke ${email}`);
            s1.classList.add('hidden');
            s2.classList.remove('hidden');
            document.querySelector('.otp-input').focus();
        }

        function verifyOTP() {
            showToast('OTP Berhasil Diverifikasi!', 'success');
            s2.classList.add('hidden');
            s3.classList.remove('hidden');
        }

        function finishReset() {
            showToast('Password berhasil diubah. Silahkan login!', 'success');
            closeForgotModal();
        }

        function showToast(message) {
            const toast = document.getElementById('toastNotification');
            document.getElementById('toastMessage').innerText = message;
            toast.classList.remove('translate-y-[-100%]', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
            setTimeout(() => {
                toast.classList.remove('translate-y-0', 'opacity-100');
                toast.classList.add('translate-y-[-100%]', 'opacity-0');
            }, 4000);
        }

        const otpInputs = document.querySelectorAll('.otp-input');
        otpInputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                if (e.target.value.length === 1 && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
            });
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });
        });
    </script>
</body>
</html>