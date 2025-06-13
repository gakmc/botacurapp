@extends('themes.backoffice.layouts.admin')

@section('title','Cotizacion')

@section('head')
@endsection

@section('breadcrumbs')
<li>
    <a href="{{route('backoffice.cotizacion.index')}}">Cotizaciones</a>
</li>
<li>
    Cotización N.°{{$cotizacion->id}}
</li>
@endsection


@section('dropdown_settings')
<li>
    <a href="{{route('backoffice.cotizacion.verpdf', $cotizacion)}}" target="_blank"><i class="material-icons left">picture_as_pdf</i>Ver PDF</a>
</li>
<li>
    <form action="{{ route('backoffice.cotizacion.enviarpdf', $cotizacion) }}" method="POST" style="display:inline;">
        @csrf

        <button type="submit"
                style="border: none; background: none; width: 100%; text-align: left; padding: 8px 16px; display: flex; align-items: center; color: #ff4081; font-family: inherit; font-size: 14px;">
            <i class="material-icons left" style="margin-right: 10px;">email</i> ENVIAR COTIZACIÓN
        </button>
    </form>
</li>
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Cotizacion</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12">
                <div class="card-panel">

                    <div class="row">
                        <div class="col s12 center-align" style="height: 120px">
                            <img src="/images/logo/logo.png" alt="logo" style="height: 120px">
                            <p style="margin-top: 0; margin-bottom: 20px;">
                                Cam. Al Volcán 13274, El Manzano, San José de Maipo, Región Metropolitana
                            </p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <br>
                    </div>
                    @php
                        $dia = ((int)$cotizacion->validez_dias <= 1) ? 'Día' : 'Días';
                        $iva = 0;
                        $sumaTotal = 0;
                    @endphp
                    <div class="row">
                        <div class="col s12 offset-m1">

                            <div class="col s12 m6">
                                <h4 class="header2" style="margin-top: 50px;"><strong>HACIA</strong></h4>
                            </div>
                            <div class="col s12 m6">
                                <h4 class="header2" style="margin-top: 50px;"><strong>Cotizacion: </strong>N.°{{$cotizacion->id}}</h4>
                            </div>

                            <div class="col s12 m6 left-align">
                                <h6 class="" style=""><strong>Cliente: </strong>{{ $cotizacion->cliente }}</h6>
                                <h6 class="" style=""><strong>Solicitante: </strong>{{$cotizacion->solicitante}}</h6>
                                <h6 class="" style=""><strong>Correo: </strong>{{$cotizacion->correo}}</h6>
                                <h6 class="" style=""><strong>Validez: </strong>{{$cotizacion->validez_dias}} {{$dia}}</h6>
                            </div>

                            <div class="col s12 m6 rigth-align">
                                <h6 class="" style=""><strong>Emitida: </strong>{{ $cotizacion->fecha_emision->isoFormat('D [de] MMMM') }}</h6>
                                <h6 class="" style=""><strong>Fecha reserva: </strong>{{ $cotizacion->fecha_reserva->isoFormat('D [de] MMMM') }}</h6>
                            </div>
                            
                        </div>
                    </div>


                    <div class="row">
                        <div class="col s12 m10 offset-m1">

                            <table class="striped">
                                <thead>
                                    <tr>
                                        <th>Descripción</th>
                                        <th>Cantidad</th>
                                        <th>Valor Neto</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (!empty($cotizacion->items))
                                        @foreach ($cotizacion->items as $item)
                                            @if ($item->itemable_type == 'App\Programa')
                                                <tr>
                                                    <td style="color: #039B7B">{{$item->itemable->nombre_programa}}</td>
                                                    <td>{{$item->cantidad}}</td>
                                                    <td>${{number_format($item->valor_neto,0,',','.')}}</td>
                                                    <td>${{number_format($item->total,0,',','.')}}</td>
                                                </tr>

                                                @foreach ($item->itemable->servicios as $servicio)
                                                    <tr>
                                                        <td>{{ $servicio->nombre_servicio }}</td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                    </tr>
                                                @endforeach

                                            @endif

                                            @if ($item->itemable_type == 'App\Servicio')
                                                <tr>
                                                    <td style="color: #039B7B">{{$item->itemable->nombre_servicio}}</td>
                                                    <td>{{$item->cantidad}}</td>
                                                    <td>${{number_format($item->valor_neto,0,',','.')}}</td>
                                                    <td>${{number_format($item->total,0,',','.')}}</td>
                                                </tr>
                                            @endif

                                            @if ($item->itemable_type == 'App\Producto')
                                                <tr>
                                                    <td style="color: #039B7B">{{$item->itemable->nombre}}</td>
                                                    <td>{{$item->cantidad}}</td>
                                                    <td>${{number_format($item->valor_neto,0,',','.')}}</td>
                                                    <td>${{number_format($item->total,0,',','.')}}</td>
                                                </tr>

                                            @endif

                                                    @php
                                                        $sumaTotal += $item->total;
                                                        $iva = $sumaTotal*0.19;
                                                    @endphp
                                        @endforeach
                                    @endif
 

                                </tbody>
                                
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td class="right-align"><strong>SUBTOTAL</strong></td>
                                    <td>${{number_format($sumaTotal,0,'','.')}}</td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td class="right-align"><strong>IVA (19%)</strong></td>
                                    <td>${{number_format($iva,0,'','.')}}</td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td class="right-align"><strong>TOTAL</strong></td>
                                    <td>${{number_format($sumaTotal+$iva,0,'','.')}}</td>
                                </tr>
                            </table>

                        </div>

                    </div>



                    <div class="row">

                    </div>




                </div>
            </div>
        </div>
    </div>
</div>
@endsection


@section('foot')
<script>
    $(document).ready(function () {
        
        @if(session('info'))
            Swal.fire({
                toast: true,
                position: '',
                icon: 'info',
                title: '{{ session('info') }}',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                  didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                  }
            });
        @endif

        @if(session('success'))
            Swal.fire({
                toast: true,
                position: '',
                icon: 'success',
                title: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                  didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                  }
            });
        @endif

    });
        

</script>
@endsection