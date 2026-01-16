<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    .title { text-align:center; font-size: 18px; font-weight: bold; margin-top: 10px; }
    .box { margin-top: 20px; line-height: 1.6; }
    .firma { margin-top: 60px; text-align:center; }
    .small { margin-top: 25px; font-size: 10px; color: #555; }
  </style>
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
        <div class="header">
        <img src="https://botacura.cl/wp-content/uploads/2024/04/logo.png" alt="Logo Botacura" class="logo">
        <p>Cam. Al Volcán 13274, El Manzano, San José de Maipo, Región Metropolitana</p>
        <p>Centro de relajación y descanso</p>
        <h2 class="highlight title">CERTIFICADO DE ANTIGÜEDAD</h2>
    </div>


  <div class="box">
    Se certifica que <strong>{{ $nombre }}</strong>, RUT <strong>{{ $rut }}</strong>,
    se desempeña en la organización desde el día <strong>{{ $fechaIngreso->format('d/m/Y') }}</strong>.

    <br><br>
    Y se mantiene en funciones a la fecha, su antigüedad corresponde a:
    <strong>{{ $antiguedadTexto }}</strong>.

    <br><br>
    Se extiende el presente certificado, para los fines que el interesado estime conveniente.
  </div>

  <div class="firma">
    ___________________________<br>
    Firma y timbre<br>


    {{--$emitido_por->name--}} Sebastian Wimmer Wirlok - Administrador - Botacura - +56 9 6191 0398
  </div>

  <div class="small">
    Documento emitido por el sistema.  Fecha: {{ $fechaEmision->format('d/m/Y') }}
  </div>
</body>
</html>
