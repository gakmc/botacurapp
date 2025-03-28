@extends('themes.backoffice.layouts.admin')

@section('title','Finanzas')

@section('head')
@endsection

@section('breadcrumbs')
<li>Ingresos</li>
@endsection


@section('dropdown_settings')
@endsection

@section('content')

<div class="section">
    <p class="caption"><strong>Ingresos</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 ">
                <div class="card-panel">
                    <div class="row">



                            <div class="card-content">

                                <span class="card-title">Movimientos recientes</span>
                                <table class="responsive-table">
                                    <thead>
                                        <tr>
                                            <th>Mes</th>
                                            <th>Programas contratados</th>
                                            <th>Abonos</th>
                                            <th>Montos Pendientes</th>
                                            <th>Saldo final</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($estadoMensual as $resumen)
                                            @php
                                                $nombreMes = \Carbon\Carbon::createFromDate($resumen->anio, $resumen->mes, 1)
                                                    ->locale('es')->translatedFormat('F Y');
                                            @endphp
                                            <tr>
                                                <td>{{ ucfirst($nombreMes) }}</td>
                                                <td>{{ $resumen->total_ventas }}</td>
                                                <td>${{ number_format($resumen->total_abonos, 0, ',', '.') }}</td>
                                                <td>${{ number_format($resumen->por_pagar, 0, ',', '.') }}</td>
                                                <td>${{ number_format($resumen->total_abonos+$resumen->por_pagar, 0, ',', '.') }}</td>
                                                <td>
                                                    <a href="{{ route('backoffice.admin.ingresos.detalleMes', [$resumen->anio, $resumen->mes]) }}" class="btn-small" style="background-color: #039B7B">
                                                        Ver detalle
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                
                                {{ $estadoMensual->links() }}
                            </div>



                    </div>
                </div>
            </div>
        </div>
    </div>
</div>




<!-- Contenido principal del dashboard -->


        <!-- Título -->
        {{-- <div class="col s12">
            <div class="container">
                <h5 class="breadcrumbs-title">Panel de Finanzas</h5>

                <!-- Tarjetas de resumen financiero -->
                <div class="row">
                    <div class="col s12 m4">
                        <div class="card green lighten-1 white-text">
                            <div class="card-content">
                                <span class="card-title">Ingresos</span>
                                <h4>$12,500</h4>
                            </div>
                        </div>
                    </div>

                    <div class="col s12 m4">
                        <div class="card red lighten-1 white-text">
                            <div class="card-content">
                                <span class="card-title">Egresos</span>
                                <h4>$6,200</h4>
                            </div>
                        </div>
                    </div>

                    <div class="col s12 m4">
                        <div class="card blue lighten-1 white-text">
                            <div class="card-content">
                                <span class="card-title">Balance</span>
                                <h4>$6,300</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de movimientos -->
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Movimientos recientes</span>
                        <table class="highlight responsive-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Descripción</th>
                                    <th>Tipo</th>
                                    <th>Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>2025-03-25</td>
                                    <td>Venta de servicios</td>
                                    <td><span class="new badge green" data-badge-caption="Ingreso"></span></td>
                                    <td>$2,000</td>
                                </tr>
                                <tr>
                                    <td>2025-03-24</td>
                                    <td>Pago proveedor</td>
                                    <td><span class="new badge red" data-badge-caption="Egreso"></span></td>
                                    <td>$1,000</td>
                                </tr>
                                <tr>
                                    <td>2025-03-23</td>
                                    <td>Suscripción mensual</td>
                                    <td><span class="new badge green" data-badge-caption="Ingreso"></span></td>
                                    <td>$1,800</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Puedes agregar más tarjetas o gráficas aquí -->

            </div> --}}

  
@endsection


@section('foot')




@endsection