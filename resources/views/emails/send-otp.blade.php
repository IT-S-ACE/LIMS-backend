<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <title>OTP</title>

</head>

<body>

<h2>Hello {{ $user->username }}</h2>

<p>Your verification code is:</p>

<h1>{{ $otp }}</h1>

<p>This code will expire in 10 minutes.</p>

<p>OTP Type : {{ ucfirst($type) }}</p>

</body>

</html>