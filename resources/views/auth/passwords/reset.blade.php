<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 15px;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .15);
            background: #fff;
        }

        .form-control {
            height: 45px;
            border-radius: 8px;
            padding-left: 42px;
        }

        .input-icon {
            position: absolute;
            top: 50%;
            left: 14px;
            transform: translateY(-50%);
            color: #888;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 14px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #aaa;
        }

        .error-text {
            font-size: 13px;
            color: red;
            display: block;
            margin-top: 4px;
        }

        .login-btn {
            background: #5b6be8;
            border-color: #5b6be8;
            border-radius: 8px;
            height: 40px;
        }

        .login-btn:hover {
            background: #4a59d4;
            border-color: #4a59d4;
        }

        .forgot-link {
            font-size: 14px;
            color: #5b6be8;
            text-decoration: none;
        }

        .forgot-link:hover {
            color: #4a59d4;
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="login-card">
        <h4 class="text-center mb-3">Reset Password</h4>
        <p class="text-muted text-center mb-4" style="font-size: 14px;">
            Enter your email and new password below to reset your account password.
        </p>

        @if (session('error'))
            <div class="alert alert-danger py-2" role="alert" style="font-size: 14px;">
                {{ session('error') }}
            </div>
        @endif
        @if (session('status'))
            <div class="alert alert-success py-2" role="alert" style="font-size: 14px;">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <!-- EMAIL -->
            <div class="form-group position-relative">
                <i class="fa fa-envelope input-icon"></i>
                <input type="email"
                    name="email"
                    id="email"
                    value="{{ $email ?? old('email') }}"
                    class="form-control @error('email') is-invalid @enderror"
                    placeholder="Enter your email"
                    required
                    autocomplete="email"
                    autofocus>

                @error('email')
                <span class="error-text">{{ $message }}</span>
                @enderror
            </div>

            <!-- NEW PASSWORD -->
            <div class="form-group position-relative">
                <i class="fa fa-lock input-icon"></i>
                <input type="password"
                    name="password"
                    id="password"
                    class="form-control @error('password') is-invalid @enderror"
                    placeholder="Enter new password"
                    required
                    autocomplete="new-password">

                <i class="fa fa-eye toggle-password" onclick="togglePassword('password', this)"></i>

                @if ($errors->has('password'))
                    @foreach ($errors->get('password') as $err)
                        <span class="error-text">{{ $err }}</span>
                    @endforeach
                @endif
            </div>

            <!-- CONFIRM PASSWORD -->
            <div class="form-group position-relative">
                <i class="fa fa-lock input-icon"></i>
                <input type="password"
                    name="password_confirmation"
                    id="password-confirm"
                    class="form-control @error('password_confirmation') is-invalid @enderror"
                    placeholder="Confirm new password"
                    required
                    autocomplete="new-password">

                <i class="fa fa-eye toggle-password" onclick="togglePassword('password-confirm', this)"></i>

                @error('password_confirmation')
                <span class="error-text">{{ $message }}</span>
                @enderror
            </div>

            <!-- SUBMIT BUTTON -->
            <button type="submit" class="btn btn-primary w-100 login-btn">
                Reset Password
            </button>

            <!-- FOOTER LINK -->
            <div class="text-center mt-3 pt-2 border-top">
                <span style="font-size: 14px; color: #6c757d;">Remember your password?</span>
                <a href="{{ route('login') }}" class="forgot-link ml-1 font-weight-bold">
                    Login
                </a>
            </div>
        </form>
    </div>

    <script>
        function togglePassword(fieldId, el) {
            const p = document.getElementById(fieldId);
            if (p) {
                const isPassword = p.type === "password";
                p.type = isPassword ? "text" : "password";
                if (el) {
                    el.classList.toggle('fa-eye', !isPassword);
                    el.classList.toggle('fa-eye-slash', isPassword);
                }
            }
        }
    </script>

</body>

</html>
