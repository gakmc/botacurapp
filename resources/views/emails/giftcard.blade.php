{{-- @component('mail::message')
# Introduction

The body of your message.

@component('mail::button', ['url' => ''])
Button Text
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent --}}


<!DOCTYPE html>
<html>



<head>
    <title>Entrega de Gift Card</title>
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
            ¡Gift Card registrada éxitosamente!
        </h1>

        <p style="
                font-family: Arial, Helvetica, sans-serif;
                font-size: 20px;
                color: #f9f9f9;
            ">
            Hola <strong>{{$gc->de}}</strong>,
        </p>

        <p style="
                                font-family: Arial, Helvetica, sans-serif;
                                font-size: 20px;
                                color: #f9f9f9;
                            ">
            Hemos generado de forma exitosa la Gift Card que solicitaste.
            
        </p>

        <p style="font-family: Arial, Helvetica, sans-serif; font-size: 20px; color: #f9f9f9; ">
            Adjunto, encontrarás la Gift Card mencionada, la cual puedes compartir con el destinatario.
        </p>

        <p style="color: #f9f9f9; font-family: Arial, Helvetica, sans-serif; /* text-align: justify; */ line-height: 1.8; margin: 20px 0; padding: 10px; font-size: 16px; white-space: pre-line;">

            ❌<strong style="color: #F92F60;"> Recordamos que está prohibido el ingreso de alcohol al
                recinto. </strong>
            🚧<strong style="color: #FCD53F;"> Solo atendemos con previa reserva </strong>

            💵<strong style="color: green"> No hay devoluciones, sin excepción</strong>

            Infórmate de estas y todas nuestras "<strong><a style="color: #039b7b; text-decoration: none;"
                    href="https://drive.google.com/file/d/1Ude_RCZpFNAgVFPp0Qqj15-w3EGKlKhQ/view"
                    target="_blank"> Políticas y condiciones </a></strong>"
        </p>


        <p style="
                            font-family: Arial, Helvetica, sans-serif;
                            font-size: 20px;
                            color: #039b7b;
                        ">
            Gracias por elegirnos.
        </p>




    </div>

    <br><br><br><br>
</body>

</html>

