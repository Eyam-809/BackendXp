<!-- resources/views/emails/bienvenida.blade.php -->

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bienvenido a XPMarket</title>
</head>
<body>
    <h1>Hola {{ $user->name }},</h1>
    <p>Gracias por registrarte en <strong>XPMarket</strong>.</p>
    <p>¡Esperamos que disfrutes la experiencia!</p>

    <br>
    <p>Saludos,<br><strong>El equipo de XPMarket</strong></p>
</body>
</html>
