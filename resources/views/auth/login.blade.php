<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - ABL Attendance</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #111827;
            background: #f3f4f6;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 24px;
            background:
                linear-gradient(135deg, rgba(245, 158, 11, 0.18), transparent 38%),
                linear-gradient(315deg, rgba(16, 185, 129, 0.14), transparent 34%),
                #f9fafb;
        }

        .login-shell {
            width: min(100%, 420px);
        }

        .brand {
            margin-bottom: 24px;
        }

        .brand h1 {
            margin: 0;
            font-size: 28px;
            line-height: 1.2;
            font-weight: 750;
        }

        .brand p {
            margin: 8px 0 0;
            color: #4b5563;
            font-size: 15px;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #ffffff;
            box-shadow: 0 20px 55px rgba(15, 23, 42, 0.10);
            padding: 28px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 650;
        }

        .field {
            margin-bottom: 18px;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 11px 12px;
            font: inherit;
            outline: none;
            transition: border-color 160ms ease, box-shadow 160ms ease;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #d97706;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.20);
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            color: #374151;
            font-size: 14px;
        }

        .remember label {
            margin: 0;
            font-weight: 500;
        }

        .error {
            margin: 0 0 18px;
            border: 1px solid #fecaca;
            border-radius: 6px;
            background: #fef2f2;
            color: #991b1b;
            padding: 10px 12px;
            font-size: 14px;
        }

        button {
            width: 100%;
            border: 0;
            border-radius: 6px;
            background: #111827;
            color: #ffffff;
            cursor: pointer;
            font: inherit;
            font-weight: 700;
            padding: 12px 14px;
        }

        button:hover {
            background: #1f2937;
        }
    </style>
</head>
<body>
    <main class="login-shell">
        <section class="brand" aria-label="ABL Attendance">
            <h1>ABL Attendance</h1>
            <p>Masuk untuk membuka dashboard sesuai peran akun.</p>
        </section>

        <form class="card" method="POST" action="{{ route('login.store') }}">
            @csrf

            @if ($errors->any())
                <p class="error">{{ $errors->first() }}</p>
            @endif

            <div class="field">
                <label for="email">Email</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    autocomplete="email"
                    required
                    autofocus
                >
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <div class="remember">
                <input id="remember" name="remember" type="checkbox" value="1">
                <label for="remember">Ingat saya</label>
            </div>

            <button type="submit">Masuk</button>
        </form>
    </main>
</body>
</html>
