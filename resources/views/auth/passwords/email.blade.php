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
            Enter your email address to receive a password reset link.
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

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <!-- EMAIL -->
            <div class="form-group position-relative">
                <i class="fa fa-envelope input-icon"></i>
                <input type="email"
                    name="email"
                    id="email"
                    value="{{ old('email') }}"
                    class="form-control @error('email') is-invalid @enderror"
                    placeholder="Enter your email"
                    required
                    autocomplete="email"
                    autofocus>

                @error('email')
                <span class="error-text">{{ $message }}</span>
                @enderror
            </div>

            <!-- SUBMIT BUTTON -->
            <button type="submit" class="btn btn-primary w-100 login-btn">
                Send Password Reset Link
            </button>

            @php
                use Parth\LaravelRbacSuite\Models\AuthSetting;
                $registrationEnabled = $registrationEnabled ?? (class_exists(AuthSetting::class) && method_exists(AuthSetting::class, 'isRegistrationEnabled') ? AuthSetting::isRegistrationEnabled() : false);
            @endphp

            <div class="text-center mt-3 pt-2 border-top">
                <span style="font-size: 14px; color: #6c757d;">Remember your password?</span>
                <a href="{{ route('login') }}" class="forgot-link ml-1 font-weight-bold">
                    Login
                </a>
                @if($registrationEnabled)
                    <span class="mx-1" style="color: #ccc;">•</span>
                    <a href="{{ route('register') }}" class="forgot-link">
                        Register
                    </a>
                @endif
            </div>
        </form>
    </div>

</body>
</html>
