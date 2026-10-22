<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register Account</title>

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
    <h4 class="text-center mb-4">Register Account</h4>

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

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- USERNAME / NAME -->
        <div class="form-group position-relative">
            <i class="fa fa-user input-icon"></i>
            <input type="text"
                name="name"
                id="username"
                value="{{ old('name') }}"
                class="form-control"
                placeholder="Enter your username">

            @error('name')
            <span class="error-text">{{ $message }}</span>
            @enderror
        </div>

        <!-- EMAIL -->
        <div class="form-group position-relative">
            <i class="fa fa-envelope input-icon"></i>
            <input type="email"
                name="email"
                id="email"
                value="{{ old('email') }}"
                class="form-control"
                placeholder="Enter your email">

            @error('email')
            <span class="error-text">{{ $message }}</span>
            @enderror
        </div>

        <!-- MOBILE NUMBER -->
        <div class="form-group position-relative">
            <i class="fa fa-phone input-icon"></i>
            <input type="text"
                name="mobile_number"
                id="mobile_number"
                value="{{ old('mobile_number') }}"
                class="form-control"
                placeholder="Enter your mobile number">

            @error('mobile_number')
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

            <i class="fa fa-eye toggle-password" onclick="togglePassword('password')"></i>

            @error('password')
            <span class="error-text">{{ $message }}</span>
            @enderror
        </div>

        <!-- CONFIRM PASSWORD -->
        <div class="form-group position-relative">
            <i class="fa fa-lock input-icon"></i>
            <input type="password"
                name="password_confirmation"
                id="password_confirmation"
                class="form-control"
                placeholder="Confirm your password">

            <i class="fa fa-eye toggle-password" onclick="togglePassword('password_confirmation')"></i>

            @error('password_confirmation')
            <span class="error-text">{{ $message }}</span>
            @enderror
        </div>

        <!-- REGISTER BUTTON -->
        <button type="submit" class="btn btn-primary w-100 login-btn">
            Register
        </button>

        <div class="text-center mt-3 pt-2 border-top">
            <span style="font-size: 14px; color: #6c757d;">Already have an account?</span>
            <a href="{{ route('login') }}" class="forgot-link ml-1 font-weight-bold">
                Login
            </a>
        </div>
    </form>
</div>

<script>
    function togglePassword(fieldId) {
        const p = document.getElementById(fieldId);
        if (p) {
            p.type = p.type === "password" ? "text" : "password";
        }
    }
</script>

</body>
</html>
