<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mobile OTP Verify</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css">

    <style>
        body {
            background: #f5f6fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .otp-card {
            width: 420px;
            padding: 35px;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 15px 35px rgba(0,0,0,.15);
            text-align: center;
        }

        .otp-box {
            width: 48px;
            height: 52px;
            border-radius: 8px;
            border: 1px solid #ddd;
            text-align: center;
            font-size: 18px;
            margin: 0 5px;
        }

        .verify-btn {
            background: linear-gradient(90deg, #6a7cff, #8e5cff);
            border: none;
            height: 45px;
            border-radius: 8px;
            font-weight: 500;
        }

        .resend {
            font-size: 14px;
            color: #888;
        }
    </style>
</head>

<body>

<div class="otp-card">
    <h5>Mobile OTP Verify</h5>
    <p class="text-muted">
        Enter the 6-digit code sent to your mobile number
    </p>

    <form method="POST" action="{{ route('mobile.otp.verify') }}">
        @csrf

        <div class="d-flex justify-content-center mb-3">
            @for($i = 0; $i < 6; $i++)
                <input type="text"
                       maxlength="1"
                       class="otp-box"
                       onkeyup="nextInput(this, event)"
                       oninput="this.value=this.value.replace(/[^0-9]/g,'')">
            @endfor
        </div>

        <input type="hidden" name="otp" id="otp">

        @error('otp')
            <small class="text-danger">{{ $message }}</small>
        @enderror

        <button type="submit" class="btn btn-primary w-100 verify-btn">
            ✔ Verify OTP
        </button>

        <div class="mt-3 resend">
            Didn't receive the code?<br>
            <a href="javascript:void(0)">Resend OTP</a>
        </div>
    </form>
</div>

<script>
    function nextInput(el, e) {
        // Only allow numbers
        el.value = el.value.replace(/[^0-9]/g, '');
        
        if (el.value.length === 1 && el.nextElementSibling) {
            el.nextElementSibling.focus();
        } else if (el.value.length === 0 && e.key === 'Backspace' && el.previousElementSibling) {
            el.previousElementSibling.focus();
        }

        // Update hidden input
        let otp = '';
        document.querySelectorAll('.otp-box').forEach(i => otp += i.value);
        document.getElementById('otp').value = otp;
    }

    // Focus first input on load
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelector('.otp-box').focus();
    });
</script>

</body>
</html>