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
            height: 200px;
            align-items: center;
            justify-content: center;
        }
        #text{
            color: #039B7B;
        }
    </style>

    <div class="center">
        <img class="center logo" src="https://botacura.cl/wp-content/uploads/2024/04/294235172_462864912512116_3346235978129441981_n-modified.png"
            alt="botacura logo"/>
        <h5 class="">Cliente {{$nombre}}</h5>
        <h6 class="">Fecha Visita: {{$fecha_visita}}</h6>
    </div>




    <div class="collection">

        <a href="" class="collection-item active " style="background-color: #039B7B;"><h6>Reserva:</h6></a>

        <div class="collection-item" style="display: flex;">

            <a href="" id="text" class="align-wrapper">
                Programa:
            </a>
            <a href="" id="text" class="align-wrapper">
                {{$programa}}
            </a>
           
        </div>

        <div class="collection-item" style="display: flex;">

            <a id="text" class="align-wrapper">
                Cantidad de Personas:
            </a>
            <a href="" id="text" class="align-wrapper">
                {{$personas}} personas
            </a>
           
        </div>

    </div>


            
            
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

</body>

</html>