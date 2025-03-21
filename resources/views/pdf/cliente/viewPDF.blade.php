<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">


    <title>Visita {{$nombre}}</title>

</head>

<body>
    <style>
        body{
            font-family: Arial, Helvetica, sans-serif;
        }

        .logo{
            height: 100px;
            /* align-items: center;
            justify-content: center;
            margin: 0;
            padding: 0; */
        }
        #text{
            color: #039B7B;
        }
    </style>

    <div class="container">
        <div class="row valign-wrapper">

            <div class="col s4 left-align">
                <img class="logo" src="https://botacura.cl/wp-content/uploads/2024/04/294235172_462864912512116_3346235978129441981_n-modified.png"
                    alt="botacura logo"/>

            </div>
        
        
                <div class="col s8 right-align">
                    
                    <h5 class="">Cliente {{$nombre}}</h5>
                    <h6 class="">Fecha Visita: {{$fecha_visita}}</h6>
                </div>
        </div>
    </div>




    <div class="collection">

        <a href="" class="collection-item active " style="background-color: #039B7B;"><h6>Reserva:</h6></a>

        <div class="collection-item" style="display: flex;">

            <p class="align-wrapper">
                <strong id="text">Programa: </strong>{{$programa}}
            </p>
            <p class="align-wrapper">
                <strong id="text">Cantidad de Personas: </strong>{{$personas}} {{($personas > 1) ? 'personas' : 'persona'}}
            </p>
            <p class="align-wrapper">
                <strong id="text">Cantidad de Masajes: </strong>{{ is_null($cantidadMasajes) ? 'No Aplica' : (($cantidadMasajes > 1) ? 'masajes' : 'masaje') }}
            </p>
            
            <p class="align-wrapper">
                <strong id="text">Observación: </strong>{{is_null($observacion) ? 'No registra' : $observacion}}
            </p>
        </div>

        <br>
        <a  class="collection-item active " style="background-color: #039B7B;"><h6>Venta:</h6></a>
        <div class="collection-item" style="display: flex; flex-direction:row;">

            <p class="align-wrapper">
                <strong id="text">Valor Programa: </strong>${{number_format($valorPrograma*$personas, 0, '','.')}}
            </p>
            <p class="align-wrapper">
                <strong id="text">Valor Abono: </strong>${{number_format($abono, 0, '','.')}}
            </p>
            <p class="align-wrapper">
                <strong id="text">Tipo Transaccion: </strong>{{$tipoAbono}}
            </p>
            <p class="align-wrapper">
                <strong id="text">Valor Diferencia: </strong>${{number_format($diferencia, 0, '','.')}}
            </p>
            <p class="align-wrapper">
                <strong id="text">Tipo Transaccion: </strong>{{$tipoDiferencia}}
            </p>
           
        </div>

        <br>
        <a  class="collection-item active " style="background-color: #039B7B;"><h6>Horarios:</h6></a>
        <div class="collection-item" style="display: flex; flex-direction:row;">

            {{-- @foreach ($visitas as $visita)
                <p class="align-wrapper">
                    <strong id="text">Sauna: </strong>
                    {{is_null($visita->horario_sauna) ? 'No registrado' : $visita->horario_sauna." - ".$visita->hora_fin_sauna}}
                </p>
                <p class="align-wrapper">
                    <strong id="text">Tinaja: </strong>{{is_null($visita->horario_tinaja) ? 'No registrado' : $visita->horario_tinaja." - ".$visita->hora_fin_tinaja}}
                </p>
                @endforeach --}}
                
            <p class="align-wrapper">
                <strong id="text">Sauna: </strong>
                {{is_null($visitas->pluck('horario_sauna')) ? 'No registrado' : $visitas->pluck('horario_sauna')->filter()->unique()->join(', ').'    '}}

                <strong id="text">Fin: </strong>{{is_null($visitas->pluck('horario_sauna')) ? 'No registrado' : $visitas->pluck('hora_fin_sauna')->filter()->unique()->join(', ')}}
            </p>
            
            <p class="align-wrapper">
                <strong id="text">Tinaja: </strong>{{is_null($visitas->pluck('horario_tinaja')) ? 'No registrado' : $visitas->pluck('horario_tinaja')->filter()->unique()->join(', ').'    '}}

                <strong id="text">Fin: </strong>{{is_null($visitas->pluck('horario_tinaja')) ? 'No registrado' : $visitas->pluck('hora_fin_tinaja')->filter()->unique()->join(', ')}}
            </p>


            <p class="align-wrapper">
                <strong id="text">Masajes: </strong>{{is_null($masajes->pluck('horario_masaje')) ? 'No registrado' : $masajes->pluck('horario_masaje')->filter()->unique()->join(', ').'    '}}

                <strong id="text">Fin: </strong>
                {{is_null($masajes->pluck('horario_masaje')) ? 'No registrado' : $masajes->pluck('hora_final_masaje')->filter()->unique()->join(', ')}}
            </p>

        </div>

        <br>
        <a  class="collection-item active " style="background-color: #039B7B;"><h6>Menús:</h6></a>
        <div class="collection-item" style="display: flex; flex-direction:row;">

            <table>
                <thead>
                    <tr>
                        <th style="color: #039B7B;">Entrada</th>
                        <th style="color: #039B7B;">Fondo</th>
                        <th style="color: #039B7B;">Acompañamiento</th>
                        <th style="color: #039B7B;">Alergias</th>
                        <th style="color: #039B7B;">Observación</th>
                    </tr>
                </thead>
                <tbody>
                    
                    @foreach ($menus as $menu)
                        <tr>
                            <td>{{!is_null($menu->id_producto_entrada) ?$menu->productoEntrada->nombre : 'No registra'}}</td>
                            <td>{{!is_null($menu->id_producto_fondo) ?$menu->productoFondo->nombre : 'No registra'}}</td>

                            <td>{{!is_null($menu->id_producto_acompanamiento) ?$menu->productoAcompanamiento->nombre : 'Sin Acompañamiento'}}</td>

                            <td>
                                {{$menu->alergias ?? 'No registra'}}
                            </td>
                            <td>
                                {{$menu->observacion ?? 'No registra'}}
                            </td>
                        </tr>
                    @endforeach
                    
                </tbody>
            </table>
           
        </div>

    </div>


            
            
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

</body>

</html>