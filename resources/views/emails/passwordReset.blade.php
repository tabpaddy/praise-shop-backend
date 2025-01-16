<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <style>
        /* Tailwind CSS styles */
        .btn {
            background-color: #1a202c;
            color: #fff;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            display: inline-block;
        }
        .btn:hover {
            background-color: #2d3748;
        }
    </style>
</head>
<body>
    <div style="max-width: 600px; margin: 20px auto; font-family: Arial, sans-serif; line-height: 1.5;">
        <h2 style="text-align: center;">Password Reset Request</h2>
        <p>Hello, {{$name}}</p>
        <p>You requested a password reset for your account. Click the button below to reset your password:</p>
        <div style="text-align: center; margin: 20px 0;">
            <a href="{{ $resetUrl }}" class="btn">Reset Password</a>
        </div>
        <p>If you did not request this, you can safely ignore this email.</p>
        <p>Thank you,<br>The {{ config('app.name') }} Team</p>
    </div>
</body>
</html>
