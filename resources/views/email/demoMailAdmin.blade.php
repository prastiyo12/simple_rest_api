<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Simple Rest API</title>
</head>
<body>
    <h2>{{ $mailDataAdmin['title'] }}</h2>
    <p>A new user has been registered with the name <span style="font-weight:bold;">{{ $mailDataAdmin['name'] }}</span> and email {{ $mailDataAdmin['email'] }}. Please verify the data and take the necessary actions.</p>

    <p>Thank you.</p>
</body>
</html>