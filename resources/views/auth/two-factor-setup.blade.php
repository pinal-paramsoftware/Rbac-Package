<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Two-Factor Authentication</title>
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
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
            padding: 30px 28px;
        }

        .title {
            text-align: center;
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .subtitle {
            text-align: center;
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 25px;
        }

        .qr-box {
            display: flex;
            justify-content: center;
            margin-bottom: 22px;
        }

        .qr-box img {
            width: 190px;
            height: 190px;
        }

        .section-title {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .section-desc {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 14px;
        }

        .info {
            font-size: 13px;
            margin-bottom: 8px;
        }

        .info strong {
            font-weight: 600;
        }

        .secret-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .secret {
            color: #e11d48;
            font-size: 13px;
            word-break: break-all;
        }

        .copy-btn {
            padding: 4px 10px;
            font-size: 12px;
            border: 1px solid #c7c7c7;
            border-radius: 6px;
            background: #fff;
            cursor: pointer;
        }

        .steps {
            font-size: 13px;
            color: #374151;
            margin-top: 12px;
        }

        .steps li {
            margin-bottom: 6px;
        }

        .action-btn {
            margin-top: 22px;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: #6366f1;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .action-btn:hover {
            background: #4f46e5;
        }
    </style>
</head>
<body>

<div class="card">

    <div class="title">Two-Factor Authentication</div>
    <div class="subtitle">Secure your account using Google Authenticator App</div>

    <!-- QR CODE -->
    <div class="qr-box">
        <img src="{{ $qrCodeUrl }}" alt="QR Code">
    </div>

    <!-- MANUAL SETUP -->
    <div class="section-title">Manual setup Without Scan QR-Code</div>
    <div class="section-desc">
        If you cannot scan the QR code, enter these details manually.
    </div>

    <div class="info">
        <strong>Account:</strong> {{ $user->email }}
    </div>

    <div class="info">
        <strong>Key type:</strong> Time-based
    </div>

    <div class="secret-row">
        <strong>Secret key:</strong>
        <span id="secret" class="secret">{{ $secret }}</span>
        <button class="copy-btn" onclick="copySecret()">Copy</button>
    </div>

    <div class="section-title" style="margin-top:14px;">How to set up</div>
    <ol class="steps">
        <li>Open Google Authenticator</li>
        <li>Tap <strong>+</strong> → Choose <strong>Scan QR</strong> or <strong>Enter key</strong></li>
        <li>Add the account</li>
        <li>Enter the 6-digit code to verify</li>
    </ol>

    <button class="action-btn" onclick="goToVerify()">
        Proceed to Verification
    </button>

</div>

<script>
    function copySecret() {
        const text = document.getElementById('secret').innerText;
        navigator.clipboard.writeText(text).then(() => {
            alert('Secret key copied');
        });
    }

    function goToVerify() {
        window.location.href = "{{ route('twofa.verify.form') }}";
    }
</script>

</body>
</html>
