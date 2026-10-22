<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-card {
            width: 420px;
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
        }

        .login-btn {
            background: #5b6be8;
            border-radius: 8px;
            height: 40px;
        }

        .login-btn:hover {
            background: #4a59d4;
        }

        .forgot-link {
            font-size: 14px;
            color: #5b6be8;
            text-decoration: none;
        }
    </style>
</head>

<body>

    <div class="login-card">
    <h4 class="text-center mb-4">Login</h4>

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

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- EMAIL -->
        <div class="form-group position-relative">
            <i class="fa fa-envelope input-icon"></i>
            <input type="email"
                name="email"
                value="{{ old('email') }}"
                class="form-control"
                placeholder="Enter your email">

            @error('email')
            <span class="error-text">{{ $message }}</span>
            @enderror
        </div>

        <!-- PASSWORD -->
        <div class="form-group position-relative">
            <i class="fa fa-lock input-icon"></i>
            <input type="password"
                name="password"
                id="password"
                class="form-control"
                placeholder="Enter your password">

            <i class="fa fa-eye toggle-password" onclick="togglePassword()"></i>

            @error('password')
            <span class="error-text">Please enter Password.</span>
            @enderror
        </div>

        <!-- CAPTCHA (Conditional) -->
        @php
            use Parth\LaravelRbacSuite\Models\AuthSetting;
            $captchaEnabled = AuthSetting::where('AuthCode', 'CAPTCHA')
                ->where('IsEnabled', 1)
                ->where('IsActive', 1)
                ->exists();
            $registrationEnabled = $registrationEnabled ?? AuthSetting::isRegistrationEnabled();
        @endphp
        
        @if($captchaEnabled)
        <div class="mb-2">
            <strong>Captcha Verification</strong>
        </div>

        <div class="form-group row align-items-center">
            <div class="col-6">
                <img src="{{ route('captcha') }}"
                    id="captchaImg"
                    class="img-fluid border rounded">
            </div>

            <div class="col-6">
                <input type="text"
                    name="captcha"
                    class="form-control"
                    placeholder="Enter captcha">
            </div>
        </div>

        <div class="d-flex justify-content-between mb-2">
            <a href="javascript:void(0)" onclick="refreshCaptcha()">Refresh</a>

            @error('captcha')
            <span class="error-text">Please Enter Captcha</span>
            @enderror
        </div>
        @endif

        <!-- LOGIN BUTTON -->
        <button type="submit" class="btn btn-primary w-100 login-btn">
            Login
        </button>

        <div class="d-flex justify-content-between align-items-center mt-3">
            @if($registrationEnabled)
            <a href="{{ route('register') }}" class="forgot-link register-link" id="registerLink">
                Register / Sign Up
            </a>
            @endif
            <a href="{{ route('password.request') }}" class="forgot-link ml-auto">
                Forgot password?
            </a>
        </div>
    </form>
</div>

<script>
    function togglePassword() {
        const p = document.getElementById("password");
        p.type = p.type === "password" ? "text" : "password";
    }

    @if($captchaEnabled)
    function refreshCaptcha() {
        document.getElementById('captchaImg').src =
            "{{ route('captcha') }}?" + Date.now();
    }
    @endif
</script>

</body>
</html>