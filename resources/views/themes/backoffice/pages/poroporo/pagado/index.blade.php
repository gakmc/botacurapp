@extends('themes.backoffice.layouts.admin')

@section('title', 'Ventas Poro Poro')

@section('head')
@endsection

@section('breadcrumbs')
{{-- <li><a href="">Venta directa</a></li> --}}
@endsection

@section('dropdown_settings')
{{-- Opciones adicionales aquí --}}
{{-- <li><a href="route("backoffice.ventas_poroporo.create")">Generar Venta</a></li> --}}
@endsection

@section('content')

<div class="section">
    <p class="caption"><strong>Poro Poro</strong></p> 
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 m10 offset-m1">
                <div class="card-panel">

    {{-- Ventas No Pagadas --}}
    <div class="card red lighten-5">
        <div class="card-content">
            <span class="card-title red-text text-darken-3"><i class="material-icons left">error_outline</i>Ventas No Pagadas</span>

            @if($ventasNoPagadas->isEmpty())
                <p class="grey-text">No hay ventas pendientes de pago para este mes.</p>
            @else
                <ul class="collection">
                    @foreach($ventasNoPagadas as $venta)
                        <li class="collection-item">
                            <strong>Fecha:</strong> {{ \Carbon\Carbon::parse($venta->fecha)->format('d/m/Y') }} &nbsp; | 
                            <strong>Monto:</strong> ${{ number_format($venta->total, 0, ',', '.') }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- Ventas Pagadas --}}
    <div class="card green lighten-5">
        <div class="card-content">
            <span class="card-title green-text text-darken-3"><i class="material-icons left">check_circle</i>Ventas Pagadas</span>

            @if($ventasPagadas->isEmpty())
                <p class="grey-text">No hay ventas pagadas registradas para este mes.</p>
            @else
                <ul class="collection">
                    @foreach($ventasPagadas as $venta)
                        <li class="collection-item">
                            <strong>Fecha:</strong> {{ \Carbon\Carbon::parse($venta->fecha)->format('d/m/Y') }} &nbsp; | 
                            <strong>Monto:</strong> ${{ number_format($venta->total, 0, ',', '.') }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

                    
                </div>
            </div>


        </div>
    </div>
</div>
@endsection

@section('foot')
<script>
    $(document).ready(function() {
        $('select').material_select();
        $('.modal').modal();
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.btn-eliminar-venta').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const url = this.dataset.url;
    
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "Esta acción no se puede deshacer",
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#aaa',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('form-eliminar-venta');
                        form.setAttribute('action', url);
                        form.submit();
                    }
                });
            });
        });
    });
</script>
@endsection
