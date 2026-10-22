<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Two-Factor Authentication</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css">

<style>
body {
    background:#f5f6fa;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}
.card-2fa {
    width:480px;
    padding:30px;
    border-radius:12px;
    background:#fff;
    box-shadow:0 15px 35px rgba(0,0,0,.15);
    text-align:center;
}
.qr {
    width:180px;
    margin:20px auto;
}
.secret {
    color:#e91e63;
    font-weight:600;
}
</style>
</head>

<body>

<div class="card-2fa">
    <h4>Two-Factor Authentication</h4>
    <p class="text-muted">Secure your account using Google Authenticator App</p>

    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=STATIC_2FA"
         class="qr">

    <hr>

    <p><b>Account:</b> demo@example.com</p>
    <p><b>Key type:</b> Time-based</p>
    <p><b>Secret key:</b> <span class="secret">{{ session('twofa_secret') }}</span></p>

    <form method="POST" action="{{ route('twofa.verify') }}">
        @csrf

        <input type="text"
               name="code"
               class="form-control text-center mb-3"
               placeholder="Enter 6-digit code">

        @error('code')
            <small class="text-danger">{{ $message }}</small>
        @enderror

        <button class="btn btn-primary w-100">
            Proceed to Verification
        </button>
    </form>
</div>

</body>
</html>
