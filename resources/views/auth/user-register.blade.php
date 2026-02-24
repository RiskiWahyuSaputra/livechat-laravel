<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - LiveChat</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0ea5e9 0%, #6366f1 50%, #8b5cf6 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: #fff;
            border-radius: 20px;
            padding: 48px 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
        }
        .logo { text-align: center; margin-bottom: 28px; }
        .logo-icon {
            width: 56px; height: 56px;
            background: linear-gradient(135deg, #0ea5e9, #6366f1);
            border-radius: 16px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 24px; margin-bottom: 12px;
        }
        .logo h1 { color: #0f172a; font-size: 22px; font-weight: 700; }
        .logo p  { color: #64748b; font-size: 13px; margin-top: 4px; }
        .tabs {
            display: flex; background: #f1f5f9; border-radius: 10px;
            padding: 4px; margin-bottom: 28px;
        }
        .tab {
            flex: 1; text-align: center; padding: 8px; border-radius: 8px;
            font-size: 13px; font-weight: 500; cursor: pointer;
            text-decoration: none; color: #64748b; transition: all 0.2s;
        }
        .tab.active { background: #fff; color: #6366f1; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 16px; }
        label { display: block; color: #374151; font-size: 13px; font-weight: 500; margin-bottom: 6px; }
        input[type="email"], input[type="password"], input[type="text"] {
            width: 100%; padding: 12px 16px; background: #f8fafc;
            border: 1px solid #e2e8f0; border-radius: 10px; color: #0f172a;
            font-size: 14px; font-family: 'Inter', sans-serif; outline: none; transition: border-color 0.2s;
        }
        input:focus { border-color: #6366f1; background: #fff; }
        .error-text { color: #ef4444; font-size: 12px; margin-top: 5px; }
        .btn-login {
            width: 100%; padding: 13px;
            background: linear-gradient(135deg, #0ea5e9, #6366f1);
            color: #fff; border: none; border-radius: 10px; font-size: 15px;
            font-weight: 600; font-family: 'Inter', sans-serif; cursor: pointer;
            transition: opacity 0.2s, transform 0.1s; margin-top: 8px;
        }
        .btn-login:hover { opacity: 0.9; transform: translateY(-1px); }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo">
            <div class="logo-icon">💬</div>
            <h1>LiveChat</h1>
            <p>Buat akun untuk mulai chat</p>
        </div>

        <div class="tabs">
            <a href="{{ route('user.login') }}" class="tab">Masuk</a>
            <a href="{{ route('user.register') }}" class="tab active">Daftar</a>
        </div>

        <form method="POST" action="{{ route('user.register') }}">
            @csrf
            <div class="form-group">
                <label for="name">Nama Lengkap</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}"
                       placeholder="Nama kamu" required autofocus>
                @error('name')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                       placeholder="email@kamu.com" required>
                @error('email')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Minimal 6 karakter" required>
                @error('password')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">Konfirmasi Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                       placeholder="Ulangi password" required>
            </div>

            <button type="submit" class="btn-login">Daftar & Mulai Chat</button>
        </form>
    </div>
</body>
</html>
