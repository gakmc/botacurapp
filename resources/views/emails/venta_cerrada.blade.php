<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Venta</title>
</head>

<body style="background-color: #363636; color:aliceblue; font-family:Arial, Helvetica, sans-serif;">
    <div style="text-align: center;">

        <img src="https://botacura.cl/wp-content/uploads/2024/04/294235172_462864912512116_3346235978129441981_n-modified.png"
        alt="botacura logo" style="max-height: 200px; max-width:200px;" />

        <h3>Hola {{ $data['nombre'] }}</h3>
        <p style="text-align: center">Gracias por confiar en nosotros. <br>Adjunto, encontrarás el detalle de la venta realizada para tu reserva del dia {{ $data['fecha_visita'] }}. <br>Si tienes alguna consulta, no dudes en contactarnos.</p>
        <p style="text-align: center">Saludos,<br>El equipo de Botacura</p>
    </div><br><br><br><br>
</body>

</html>