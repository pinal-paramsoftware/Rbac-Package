<!DOCTYPE html>
<html>
<head>
    <title>Your OTP Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .otp-code {
            font-size: 32px;
            font-weight: bold;
            text-align: center;
            letter-spacing: 10px;
            margin: 20px 0;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 8px;
            color: #333;
        }
        .info-box {
            background: #e8f4ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Hello {{ $user->name }},</h2>
        
        <p>Your OTP code for authentication is:</p>
        
        <div class="otp-code">{{ $otp }}</div>
        
        <div class="info-box">
            <p><strong>Important Information:</strong></p>
            <ul>
                <li>This OTP will expire in <strong>{{ $expiry }} minutes</strong></li>
                <li>You have <strong>{{ $attempts }} attempts</strong> to enter the correct OTP</li>
                <li>Do not share this code with anyone</li>
            </ul>
        </div>
        
        <p>If you didn't request this OTP, please ignore this email or contact support.</p>
        
        <p>Best regards,<br>Support Team</p>
    </div>
</body>
</html>