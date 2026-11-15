<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Sattva Botanical</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/sattva-logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/sattva-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/sattva-logo.png') }}">

    <link rel="preload" as="image" href="{{ asset('images/login.bg.jpg') }}">

    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Dedicated Login CSS -->
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body class="login-page-body">

    <div class="login-wrapper">
        <div class="login-header">
            <h1 class="login-header-title">Welcome back!</h1>
            <p class="login-header-subtitle">Sign in to your account to continue</p>
        </div>

        <div class="login-card">
            @yield('content')
        </div>
    </div>

    <!-- Square Info Modal for Forgot Password -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
            <div class="modal-content modal-square-content">
                <div class="modal-square-icon-box bg-success-subtle text-success">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <h4 class="modal-square-title" id="forgotPasswordModalLabel">Forgot Password</h4>
                <p class="modal-square-subtitle">Please contact your administrator to reset your password.</p>
                <div class="d-flex justify-content-center">
                    <button type="button" class="btn btn-signin px-4" style="width: auto; height: 38px; border-radius: 20px;" data-bs-dismiss="modal">Got it</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input && icon) {
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
        }
    </script>
</body>
</html>
