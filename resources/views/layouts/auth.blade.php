<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - {{ config('app.name', 'Sattva Botanical ERP') }}</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: #ffffff;
            border-radius: 1.25rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.25), 0 8px 10px -6px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 440px;
            padding: 2.5rem 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .login-brand-leaf {
            color: #059669;
            font-size: 2.5rem;
            display: inline-block;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="text-center mb-4">
            <div class="login-brand-leaf">
                <i class="bi bi-flower1"></i>
            </div>
            <h3 class="fw-bold text-dark mb-1">SATTVA BOTANICAL</h3>
            <p class="text-muted small">Manufacturing ERP System</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3">
                @foreach($errors->all() as $error)
                    <div><i class="bi bi-exclamation-circle me-1"></i> {{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if(session('status'))
            <div class="alert alert-success py-2 px-3 small rounded-3 mb-3">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
    </script>
</body>
</html>
