<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Entrega de Gift Card</title>

    <style>
        @font-face {
            font-family: 'Pacifico';
            src: url("file:///{{ str_replace('\\','/', public_path('assents/fonts/pacifico/Pacifico-Regular.ttf')) }}") format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        body {
            font-family: "Roboto", sans-serif;
            font-size: 15px;
            margin: 0;
            padding: 0;
        }

        h5 {
            font-size: 25px;
            color: #039B7B;
            margin: 0;
        }

        /* Contenedor principal */
        .container {
            text-align: center;
            padding: 1rem;
        }

        /* Tarjeta */
        .card {
            max-width: 900px;
            width: 100%;
            background-color: #363636; 
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            font-size: 14px;
            display: inline-block; /* para centrar la tarjeta */
            text-align: left; /* contenido alineado a la izquierda */
        }

        /* Columnas simuladas (inline-block) */
        .left-side,
        .right-side {
            display: inline-block;
            width: 49%;
            vertical-align: top;
            box-sizing: border-box;
        }

        /* Columna izquierda */
        .left-side {
            padding: 20px;
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)),
            url("file:///{{ str_replace('\\','/', public_path('images/gc/fondo-botacura.jpeg')) }}") center/cover no-repeat;
            color: #e0f2f1
        }

        .left-side ul {
            list-style: none;
            padding-left: 0;
            margin-top: 10px;
            text-align: left;
        }

        .left-side h6 {
            font-size: 23px;
            margin: 10px 0;
            text-align: center;
        }

        /* Columna derecha */
        .right-side {
            padding: 20px;
            background-color: #e0f2f1;
        }

        .right-side h6 {
            font-size: 18px;
            margin: 0px;
        }

        .gift-title {
            font-family: 'Pacifico', cursive;
            color: #00695c;
            font-size: 25px;
            margin-bottom: 10px;
        }

        .whatsapp {
            margin-top: 40px;
        }

        .code {
            text-align: center;
            margin-top: 20px;
        }

        .code h5 {
            color: #039B7B;
            font-size: 20px;
            margin-bottom: 5px;
        }

        img.barcode {
            margin-top: 5px;
            max-width: 100%;
        }

        li{
            list-style: none;
        }
    </style>
</head>

<body>

<div class="container">
    <div class="card">
        <!-- Lado izquierdo -->
        <div class="left-side">
            <h5>BOTACURA <br>
                <small style="font-size: 16px;">Cajón del Maipo</small>
            </h5>
            <h6>{{ $programa->nombre_programa }}</h6>

            <ul>
                @php
                    $lista = ['masaje','tinaja','sauna'];
                @endphp
                @foreach ($programa->servicios as $servicio)
                    <li>{{ $servicio->nombre_servicio }}
                        @if (in_array(strtolower($servicio->nombre_servicio), $lista))
                            - ({{ $servicio->duracion }} mins)
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        <!-- Lado derecho -->
        <div class="right-side">
            <h5 class="gift-title">Gift Card &#127873;</h5>
            <p><strong>De:</strong> {{ $gc->de }}</p>
            <p><strong>Para:</strong> {{ $gc->para }}</p>
            <p><strong>Válido hasta:</strong> {{ $gc->valido }}</p>
            <p class="whatsapp">Programa tu horario al WhatsApp:</p>
            <h6><strong>+56 9 8272 0582</strong></h6>

            <div class="code">
                <h5>Código: {{ $gc->codigo }}</h5>
                <img class="barcode" src="data:image/png;base64,{{ $barcode }}" alt="Código de barras">
            </div>
        </div>
    </div>
</div>

</body>
</html>
