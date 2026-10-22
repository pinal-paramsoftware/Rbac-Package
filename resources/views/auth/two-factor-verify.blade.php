<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>2FA OTP Verify</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fb;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 18px 40px rgba(0,0,0,0.12);
            padding: 40px 30px;
            text-align: center;
        }

        .title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 30px;
        }

        .otp-box {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .otp-box input {
            width: 46px;
            height: 56px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 22px;
            font-weight: 600;
            text-align: center;
            outline: none;
            transition: 0.2s;
        }

        .otp-box input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,.2);
        }

        .hint {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 22px;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(90deg,#6366f1,#8b5cf6);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 10px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<div class="card">

    <div class="title">2FA OTP Verify</div>
    <div class="subtitle">Enter the 6-digit code from your Authenticate App</div>

    @error('code')
        <div class="error">{{ $message }}</div>
    @enderror

    <form method="POST" action="{{ route('twofa.verify') }}" id="otpForm">
        @csrf

        <div class="otp-box">
            @for($i = 0; $i < 6; $i++)
                <input type="text"
                       maxlength="1"
                       inputmode="numeric"
                       class="otp"
                       data-index="{{ $i }}">
            @endfor
        </div>

        <input type="hidden" name="code" id="otpValue">

        <div class="hint">
            Click on the boxes or use keyboard numbers to enter the code
        </div>

        <button type="submit" class="btn" id="verifyBtn" disabled>
            ✔ Verify Code
        </button>
    </form>

</div>

<script>
    const inputs = document.querySelectorAll('.otp');
    const hiddenInput = document.getElementById('otpValue');
    const button = document.getElementById('verifyBtn');

    inputs[0].focus();

    inputs.forEach((input, index) => {
        input.addEventListener('input', () => {
            if (!/^\d$/.test(input.value)) {
                input.value = '';
                return;
            }

            if (index < 5) inputs[index + 1].focus();
            updateCode();
        });

        input.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && input.value === '' && index > 0) {
                inputs[index - 1].focus();
            }
        });
    });

    function updateCode() {
        let code = '';
        inputs.forEach(i => code += i.value);
        hiddenInput.value = code;

        button.disabled = !(code.length === 6);
    }
</script>

</body>
</html>
