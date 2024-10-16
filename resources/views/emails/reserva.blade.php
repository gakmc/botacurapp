<!DOCTYPE html>
<html>
    <head>
        <title>Confirmación de Reserva</title>
    </head>
    <body
        style="
            margin: 0;
            padding: 0;
            background-color: #363636;
            color: aliceblue;
        "
    >
        <div class="card">
            {{-- <div class="card-content"> --}}
                <div
                    class="encabezado"
                    style="
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                    "
                >
                    <img
                        src="https://botacura.cl/wp-content/uploads/2024/04/294235172_462864912512116_3346235978129441981_n-modified.png"
                        alt="botacura logo"
                        style="height: 200px"
                    />
                    <!-- <p class=" ">
                        Cam. Al Volcán 13274, El Manzano, San José de Maipo, Región Metropolitana
                    </p> -->

                    <h1 style="font-family: Arial, Helvetica, sans-serif">
                        ¡Reserva registrada éxitosamente!
                    </h1>
                </div>

                <div
                    class="contenido"
                    style="
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        align-items: center;
                    "
                >
                    <div class="data" style="align-items: start">
                        <p
                            style="
                                font-size: 20px;
                                color: #f9f9f9;
                                font-family: Arial, Helvetica, sans-serif;
                            "
                        >
                            Hola <strong>{{ $nombre }}</strong
                            >,
                        </p>

                        <p
                            style="
                                font-family: Arial, Helvetica, sans-serif;
                                font-size: 20px;
                                color: #f9f9f9;
                            "
                        >
                            Tu visita ha sido registrada exitosamente.
                        </p>

                        <p
                            style="
                                font-family: Arial, Helvetica, sans-serif;
                                font-size: 20px;
                                color: #f9f9f9;
                            "
                        >
                            <strong>Fecha de visita:</strong>
                            {{ $fecha_visita }}
                        </p>
                        <p
                            style="
                                font-family: Arial, Helvetica, sans-serif;
                                font-size: 20px;
                                color: #f9f9f9;
                            "
                        >
                            <strong>Cantidad de personas:</strong>
                            {{ $cantidad_personas }}
                        </p>
                        <p
                            style="
                                font-family: Arial, Helvetica, sans-serif;
                                font-size: 20px;
                                color: #f9f9f9;
                            "
                        >
                            <strong>Programa:</strong>
                            {{ $programa->nombre_programa}}
                        </p>
                    </div>

                    <div class="info" style="align-items: center">
                        <p
                            style="
                                color: #f9f9f9;
                                font-family: Arial, Helvetica, sans-serif;
                                text-align: center;
                                line-height: 1.8;
                                font-size: 16px;
                                white-space: pre-line;
                            "
                        >
                            Estaríamos OK 🙌🏻 Nos vemos pronto! 🌞
                            <strong>Te recomendamos traer:</strong>
                        </p>
                        <!-- Ajustar la lista -->
                        <ul
                            style="
                                /* list-style-type: none;  */
                                padding: 0;
                                text-align: center; 
                                font-size: 16px;
                                font-family: Arial, Helvetica, sans-serif;
                                list-style:square;
                            "
                        >
                            <li style="margin-bottom: 10px; color: #039b7b">
                                <strong>Sandalias</strong>
                            </li>
                            <li style="margin-bottom: 10px; color: #039b7b">
                                <strong>Traje de baño</strong>
                            </li>
                            <li style="margin-bottom: 10px; color: #039b7b">
                                <strong>Toalla y/o bata</strong>
                            </li>
                            <li style="margin-bottom: 10px; color: #039b7b">
                                <strong>Ropa de muda</strong>
                            </li>
                        </ul>

                        <p
                            style="
                                color: #f9f9f9;
                                font-family: Arial, Helvetica, sans-serif;
                                text-align: justify;
                                line-height: 1.8;
                                margin: 20px 0;
                                padding: 10px;
                                font-size: 16px;
                                white-space: pre-line;
                            "
                        >
                            Para los días fríos, puedes traer ropa abrigada,mantas o 
                            frazadas en especial para la mañana y fin de la tarde 🌞

                            🚨 Recordar que el ingreso es en cualquier momento a partir
                            de las 10:00 AM y que su horario de llegada debe ser 
                            antes de su hora agendada de Sauna, Tinaja o masaje 🧖🏻‍♀ 
                            para que así no existan descuento de minutos
                            en su programa, en el caso de NO presentarse en el horario,
                            no hay modificación de horario.
                            <strong>(No hay excepciones o devoluciones)</strong>

                            Infórmate de esto ☝️ y más en nuestras "<strong><a style="color: #039b7b; text-decoration: none;" href="https://drive.google.com/file/d/1Ude_RCZpFNAgVFPp0Qqj15-w3EGKlKhQ/view" target="_blank">Políticas y condiciones</a></strong>"

                            ❌<strong style="color: #F92F60;"> Recordamos que está prohibido el ingreso de alcohol al recinto. </strong>
                            🚧<strong style="color: #FCD53F;"> Solo atendemos con previa reserva </strong>
                            
                            📍 Te comparto el link con nuestra ubicación:
                            En google maps encuentras nuestra ubicación como 
                            “<strong ><a target="_blank" href="https://goo.gl/maps/Nhtf4DdQKSoGDXje7
                                " style="color: #039b7b; text-decoration: none;">Botacura</a></strong>”, esto es en el sector El Manzano,
                            antes del Puente Colorado 🌉.
                            
                            🚗 Debe dar aviso de su llegada por medio de Whatsapp,
                            para que nuestras Anfitrionas salgan a recibirlos 🤗
                        </p>
                    </div>

                    <p
                        style="
                            font-family: Arial, Helvetica, sans-serif;
                            font-size: 20px;
                            color: #039b7b;
                        "
                    >
                        Gracias por elegirnos. ¡Te Esperamos!
                    </p>
                </div>
            {{-- </div> --}}
        </div>
    </body>
</html>
