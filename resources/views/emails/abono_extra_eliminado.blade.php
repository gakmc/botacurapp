<!DOCTYPE html>
<html>



<head>
    <title>Corrección de Abono</title>
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
            Corrección en tu reserva
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
                                line-height: 1.6;
                            ">
            Te escribimos para informarte que, por un <strong>error en nuestro registro (fe de erratas)</strong>,
            hemos eliminado un abono extra que se había ingresado por error a tu reserva.
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
            <strong>Monto del abono eliminado:</strong>
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

        <p style="
                                font-family: Arial, Helvetica, sans-serif;
                                font-size: 20px;
                                color: #FCD53F;
                            ">
            <strong>Saldo pendiente actualizado:</strong>
            ${{ number_format($saldo_pendiente, 0, '', '.') }}
        </p>

        <p style="
                                color: #f9f9f9;
                                font-family: Arial, Helvetica, sans-serif;
                                line-height: 1.8;
                                margin: 20px 0;
                                padding: 10px;
                                font-size: 16px;
                            ">
            Lamentamos cualquier inconveniente que esto pueda causar. Si el monto correspondiente a este
            abono ya fue pagado por ti y necesitas más información al respecto, o si tienes cualquier otra duda,
            no dudes en comunicarte con nosotros a través de nuestros canales de contacto:
        </p>

        <ul style="
                                list-style-type: none;
                                padding: 0;
                                text-align: center;
                                font-size: 16px;
                                font-family: Arial, Helvetica, sans-serif;
                            ">
            <li style="margin-bottom: 10px; color: #039b7b">
                <strong>WhatsApp:</strong>
                <a style="color: #039b7b; text-decoration: none;"
                    href="https://api.whatsapp.com/send/?phone=56974484112&text=Hola%2C+ten%C3%ADa+una+consulta+sobre+un+abono+extra+de+mi+reserva"
                    target="_blank">+56 9 7448 4112</a>
            </li>
            <li style="margin-bottom: 10px; color: #039b7b">
                <strong>Correo:</strong>
                <a style="color: #039b7b; text-decoration: none;" href="mailto:hola@botacura.cl">hola@botacura.cl</a>
            </li>
            <li style="margin-bottom: 10px; color: #039b7b">
                <strong>Instagram:</strong>
                <a style="color: #039b7b; text-decoration: none;"
                    href="https://www.instagram.com/botacura_cajondelmaipo/" target="_blank">@botacura_cajondelmaipo</a>
            </li>
        </ul>

        <p style="
                            font-family: Arial, Helvetica, sans-serif;
                            font-size: 20px;
                            color: #039b7b;
                        ">
            Gracias por tu comprensión. ¡Te Esperamos!
        </p>




    </div>

    <br><br><br><br>
</body>

</html>
