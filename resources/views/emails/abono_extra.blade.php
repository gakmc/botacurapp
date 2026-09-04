<!DOCTYPE html>
<html>



<head>
    <title>Confirmación de Abono</title>
</head>



<body style="
            margin: 0;
            padding: 0;
            background-color: #363636;
            color: aliceblue;
        ">



    <div class="encabezado" style="text-align: center">
        <img src="https://botacura.cl/wp-content/uploads/2024/04/294235172_462864912512116_3346235978129441981_n-modified.png"
            alt="botacura logo" style="height: 200px" />

        <h1 style="font-family: Arial, Helvetica, sans-serif">
            ¡Abono registrado éxitosamente!
        </h1>

        <p style="
                font-family: Arial, Helvetica, sans-serif;
                font-size: 20px;
                color: #f9f9f9;
            ">
            Hola <strong>{{ $nombre }}</strong>,
        </p>

        <p style="
                                font-family: Arial, Helvetica, sans-serif;
                                font-size: 20px;
                                color: #f9f9f9;
                            ">
            Hemos registrado un abono extra a tu reserva.
        </p>

        <p style="
                                font-family: Arial, Helvetica, sans-serif;
                                font-size: 20px;
                                color: #f9f9f9;
                            ">
            <strong>Fecha de visita:</strong>
            {{ $fecha_visita }}
        </p>
        <p style="
                                font-family: Arial, Helvetica, sans-serif;
                                font-size: 20px;
                                color: #f9f9f9;
                            ">
            <strong>Programa:</strong>
            {{ $programa }}
        </p>
        <p style="
                                font-family: Arial, Helvetica, sans-serif;
                                font-size: 20px;
                                color: #f9f9f9;
                            ">
            <strong>Monto abonado:</strong>
            ${{ number_format($monto, 0, '', '.') }}
        </p>
        <p style="
                                font-family: Arial, Helvetica, sans-serif;
                                font-size: 20px;
                                color: #f9f9f9;
                            ">
            <strong>Fecha del abono:</strong>
            {{ $fecha_abono }}
        </p>
        <p style="
                                font-family: Arial, Helvetica, sans-serif;
                                font-size: 20px;
                                color: #f9f9f9;
                            ">
            <strong>Método de pago:</strong>
            {{ $tipo_transaccion }}
        </p>
        @if($folio)
        <p style="
                                font-family: Arial, Helvetica, sans-serif;
                                font-size: 20px;
                                color: #f9f9f9;
                            ">
            <strong>Folio:</strong>
            {{ $folio }}
        </p>
        @endif

        @if($saldo_pendiente > 0)
        <p style="
                                font-family: Arial, Helvetica, sans-serif;
                                font-size: 20px;
                                color: #FCD53F;
                            ">
            <strong>Saldo pendiente:</strong>
            ${{ number_format($saldo_pendiente, 0, '', '.') }}
        </p>
        @else
        <p style="
                                font-family: Arial, Helvetica, sans-serif;
                                font-size: 20px;
                                color: #039b7b;
                            ">
            <strong>¡Tu reserva se encuentra totalmente pagada!</strong>
        </p>
        @endif

        <p style="
                            font-family: Arial, Helvetica, sans-serif;
                            font-size: 20px;
                            color: #039b7b;
                        ">
            Gracias por elegirnos. ¡Te Esperamos!
        </p>




    </div>

    <br><br><br><br>
</body>

</html>
