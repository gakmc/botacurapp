<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>Gift Card N.º {{ $gc->id }}</title>

    {{-- <style>

        @font-face {
            font-family: 'Pacifico';
            src: url("{{ base_path('public/assents/fonts/pacifico/Pacifico-Regular.ttf')}}") format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        body{
            font-family:"Roboto", sans-serif;
            font-size: 15px;
        }

        h5{
            font-size: 25px;
            color: #039B7B;
        }


        /* Contenedor principal */
        .container {
            display: flex;
            justify-content: center;
            padding: 1rem;
        }

        /* Tarjeta */
        .card {
            max-width: 900px;
            width: 100%;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
            display: flex;
            flex-direction: row;
        }

        /* Columna izquierda - fondo blanco + imagen oscura arriba */
        .left-side {
            flex: 1 1 50%;
            padding: 20px;
            color: white;
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('{{ public_path('images/gc/fondo-botacura.jpeg') }}') center/cover no-repeat;
            display: flex;
            flex-direction: column;
            justify-content: flex-start; /* o center si quieres centrado vertical */
        }

        .left-side ul {
            list-style: none;
            padding-left: 0;
            margin-top: 10px;
            text-align: left;
        }

        .left-side h6 {
            font-size: 23px;
            margin: 0;
        }

        /* Columna derecha - fondo verde claro */
        .right-side {
            flex: 1 1 50%;
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
        }

        .whatsapp {
            margin-top: 40px;
        }

        .code {
            text-align: center;
            margin-top: 20px;
        }

        /* Responsivo */
        @media (max-width: 768px) {
            .card {
                flex-direction: column;
            }
        }
    </style> --}}

        <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            margin: 30px;
            margin-top: 0px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            max-height: 120px;
        }

        .info-table, .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .info-table td {
            padding: 4px 0;
        }

        .items-table th, .items-table td {
            border: 1px solid #ccc;
            padding: 8px;
        }

        .items-table th {
            background-color: #f2f2f2;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .highlight {
            color: #039B7B;
            font-weight: bold;
        }

        .enlaces {
            color: #777;
            text-decoration: none;
        }

        @page {
            margin: 50px 30px;
            footer: footer;
        }
    </style>

</head>

<body>

    {{-- <div class="row">


        <div class="col s12 m10 offset-m1 l8 offset-l2">
            <div class="card z-depth-3"
                style="overflow: hidden; border-radius: 15px; background: linear-gradient(to right, #fff 50%, #00897b1a 50%);">
                <div class="row no-margin" style="display: flex; flex-wrap: wrap;">
                    <!-- Lado izquierdo -->
                    <div class="col s12 m6"
                        style="padding: 20px; background-image: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('{{ asset('images/gc/fondo-botacura.jpeg') }}'); background-size: cover; background-position: center;">
                        <h5 style="font-size:20px; font-weight: bold; color: #039B7B;">BOTACURA<br><small style="font-size: 16px;">Cajón
                                del Maipo</small></h5>
                        <h6 class="white-text center" style="margin-top: 20px; font-size:25px">
                            {{ $programa->nombre_programa }}</h6>
                        <ul class="white-text" style="padding-left: 0; list-style: none;">
                            @php
                            $lista = ['masaje','tinaja','sauna'];
                            @endphp
                            @foreach ($programa->servicios as $servicio)
                            <li>✔️ {{$servicio->nombre_servicio}}
                                @if (in_array(strtolower($servicio->nombre_servicio),$lista))
                                - ({{ $servicio->duracion }} mins)
                                @endif</li>

                            @endforeach

                        </ul>
                    </div>

                    <!-- Lado derecho -->
                    <div class="col s12 m6" style="padding: 20px;">
                        <h5 style="font-family: 'Pacifico', cursive; color: #00695c;">Gift Card 🎁</h5>
                        <p><strong>De:</strong> {{$gc->de}}</p>
                        <p><strong>Para:</strong> {{$gc->para}}</p>
                        <p><strong>Válido hasta:</strong>{{$gc->valido}}</p>
                        <p style="margin-top: 40px;">Programa tu horario al WhatsApp:</p>
                        <h6><strong>+56 9 8272 0582</strong></h6>

                        <div class="center">

                            <h5 style="margin-top: 20px;">Código: {{ $gc->codigo }}</h5>

                            <img src="data:image/png;base64,{{ $barcode }}" alt="Código de barras">

                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div> --}}


    <div class="header">
        <img src="https://botacura.cl/wp-content/uploads/2024/04/logo.png" alt="Logo Botacura" class="logo">
        <p>Cam. Al Volcán 13274, El Manzano, San José de Maipo, Región Metropolitana</p>
        <p>Centro de relajación y descanso</p>
    </div>


        <table class="info-table">
        <tr>
            <td class="text-left"><strong>De:</strong> {{ $gc->de }}</td>
            <td class="text-right"><strong>Para:</strong> {{ $gc->para }}</td>
        </tr>
        <tr>
            <td class="text-left"><strong>Correo:</strong> {{ $gc->correo }}</td>
            <td class="text-right"><strong>Validez hasta:</strong> {{ $gc->valido }}</td>
        </tr>
    </table>


    <div class="container">
    <div class="card">

        <!-- Lado derecho -->
        <div class="right-side">
            <h5 class="gift-title">Gift Card 🎁</h5>
            <p><strong>De:</strong> {{$gc->de}}</p>
            <p><strong>Para:</strong> {{$gc->para}}</p>
            <p><strong>Válido hasta:</strong> {{$gc->valido}}</p>
            <p class="whatsapp">Programa tu horario al WhatsApp:</p>
            <h6><strong>+56 9 8272 0582</strong></h6>

                        <ul>
                    @php
                        $lista = ['masaje','tinaja','sauna'];
                    @endphp
                    @foreach ($programa->servicios as $servicio)
                        <li>✔️ {{$servicio->nombre_servicio}}
                            @if (in_array(strtolower($servicio->nombre_servicio),$lista))
                                - ({{ $servicio->duracion }} mins)
                            @endif
                        </li>
                    @endforeach
                {{-- <li>✔️ Masajes</li> --}}
            </ul>

            <div class="code">
                <h5>Código: {{$gc->codigo}}</h5>
                <img src="data:image/png;base64,{{ $barcode }}" alt="Código de barras">
            </div>
        </div>
    </div>
</div>


</body>

</html>