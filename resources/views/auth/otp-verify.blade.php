<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email OTP Verify</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f5f7fb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }

        .otp-card {
            width: 420px;
            background: #fff;
            border-radius: 12px;
            padding: 40px 35px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,.08);
        }

        .otp-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c2c2c;
            margin-bottom: 8px;
        }

        .otp-subtitle {
            font-size: 14px;
            color: #7a7a7a;
            margin-bottom: 30px;
        }

        .otp-inputs {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 25px;
        }

        .otp-input {
            width: 45px;
            height: 50px;
            border: 1.5px solid #ddd;
            border-radius: 8px;
            text-align: center;
            font-size: 18px;
            font-weight: 600;
        }

        .otp-input:focus {
            border-color: #6c63ff;
            outline: none;
        }

        .btn-verify {
            width: 100%;
            height: 46px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(90deg, #6a7cff, #8e5cff);
            color: #fff;
            font-size: 15px;
            font-weight: 500;
            transition: .3s;
        }

        .btn-verify:disabled {
            background: #cfcfcf;
            cursor: not-allowed;
        }

        .resend-text {
            margin-top: 18px;
            font-size: 13px;
            color: #777;
        }

        .resend-text a {
            color: #6c63ff;
            font-weight: 500;
            text-decoration: none;
        }

        .resend-text a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

<div class="otp-card">
    <div class="otp-title">Email OTP Verify</div>

    <div class="otp-subtitle">
        Enter the 6-digit code sent to your registered email
    </div>

    <form method="POST" action="{{ route('otp.verify') }}" id="otpForm">
        @csrf

        <div class="otp-inputs">
            @for($i = 0; $i < 6; $i++)
                <input type="text"
                       maxlength="1"
                       class="otp-input"
                       oninput="moveNext(this, {{ $i }})">
            @endfor
        </div>

        <input type="hidden" name="otp" id="otp">

        <button type="submit" class="btn-verify" id="verifyBtn">
            Verify OTP
        </button>

        <div class="resend-text">
            Didn’t receive the code?
            <br>
            <a href="{{ route('otp.resend') }}">Resend OTP</a>
        </div>
    </form>
</div>

<script>
    const inputs = document.querySelectorAll('.otp-input');
    const verifyBtn = document.getElementById('verifyBtn');

    function moveNext(el, index) {
        el.value = el.value.replace(/[^0-9]/g, '');

        if (el.value && index < inputs.length - 1) {
            inputs[index + 1].focus();
        }

        updateOTP();
    }

    function updateOTP() {
        let otp = '';
        inputs.forEach(input => otp += input.value);
        document.getElementById('otp').value = otp;
        verifyBtn = otp.length !== 6;
    }

    document.addEventListener('DOMContentLoaded', () => {
        inputs[0].focus();
    });
</script>

</body>
</html>
