@extends('themes.backoffice.layouts.admin')

@section('title','Finanzas')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.admin.ingresos')}}">Consumos</a></li>
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
                <div class="card-panel">
                    <div class="row">
                        
                        @foreach($ventas as $venta)
                        @if ($venta->consumo)

                        <h4>Venta ID: {{ $venta->id }}</h4>
                    
                       
                        
                            <p>Subtotal: {{ $venta->consumo->subtotal }}</p>
                    
                            @foreach($venta->consumo->detallesConsumos as $detalle)
                                <div>
                                    Producto: {{ $detalle->producto->nombre ?? 'Sin nombre' }} <br>
                                    Cantidad: {{ $detalle->cantidad_producto }} <br>
                                    Precio unitario: {{ $detalle->producto->valor }}
                                </div>
                            @endforeach
                    
                        
                            @endif
                        @endforeach

                    </div>
                </div>








            </div>
        </div>
    </div>
</div>


  
@endsection


@section('foot')




@endsection