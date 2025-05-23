@extends('themes.backoffice.layouts.admin')

@section('title','Finanzas')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.admin.consumos')}}">Consumos</a></li>
<li>Detalles</li>
@endsection


@section('dropdown_settings')
@endsection

@section('content')

<div class="section">
    <p class="caption"><strong>Consumos</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 ">




                @foreach($ventas as $venta)
                    @if ($venta->consumo)
                        <div class="card z-depth-2 hoverable">
                            <div class="card-content">
                                <div class="row valign-wrapper">
                                    <div class="col s1">
                                        <i class="material-icons medium blue-text">receipt</i>
                                    </div>
                                    <div class="col s11">
                                        <span class="card-title">
                                            Valor Consumo: <span class="blue white-text text-darken-2" style="padding: 5px 10px; border-radius: 10px;">${{ number_format($venta->consumo->detallesConsumos->sum("subtotal"), 0, ',', '.') }}</span>
                                        </span>
                                        <p class="grey-text">Fecha: {{ $venta->created_at->format('d/m/Y') }}</p>
                                    </div>
                                </div>

                                <div class="divider" style="margin: 10px 0;"></div>


                                <ul class="collection with-header z-depth-1" style="margin-top: 15px;">
                                    <li class="collection-header"><h6><strong>Detalle de productos</strong></h6></li>
                                    @foreach($venta->consumo->detallesConsumos as $detalle)
                                        <li class="collection-item avatar">
                                            <i class="material-icons circle teal">shopping_basket</i>
                                            <span class="title"><strong>{{ $detalle->producto->nombre ?? 'Sin nombre' }}</strong></span>
                                            <p>
                                                Cantidad: {{ $detalle->cantidad_producto }}<br>
                                                Precio unitario: ${{ number_format($detalle->producto->valor, 0, ',', '.') }}<br>
                                                Total: ${{ number_format($detalle->cantidad_producto * $detalle->producto->valor, 0, ',', '.') }}
                                            </p>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif
                @endforeach





            </div>
        </div>
    </div>
</div>


  
@endsection


@section('foot')




@endsection