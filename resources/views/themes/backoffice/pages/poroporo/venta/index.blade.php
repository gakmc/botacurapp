@extends('themes.backoffice.layouts.admin')

@section('title', 'Ventas Poro Poro')

@section('head')
@endsection

@section('breadcrumbs')
{{-- <li><a href="">Venta directa</a></li> --}}
@endsection

@section('dropdown_settings')
{{-- Opciones adicionales aquí --}}
<li><a href="{{route("backoffice.ventas_poroporo.create")}}">Generar Venta</a></li>
@endsection

@section('content')

<div class="section">
    <p class="caption"><strong>Ventas Poro Poro</strong></p> 
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 m9">
                <div class="card-panel">

                    <table>
                        <thead>
                            <tr>
                                <th data-field="id_user">Atendido por</th>
                                <th data-field="total">Valor Venta</th>
                                <th data-field="fecha">Fecha Venta</th>
                                <th data-field="hora">Hora generada</th>
                                <th data-field="acciones">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($poroVentas->isNotEmpty())
                                @foreach ($poroVentas as $poroVenta)
                                <tr>
                                    <td>{{ $poroVenta->user->name }}</td>
                                    <td>${{ number_format($poroVenta->total,0,',','.') }}</td>

                                    <td>{{ \Carbon\Carbon::parse($poroVenta->fecha)->format('d-m-Y') }}</td>

                                    <td>{{ $poroVenta->created_at->format('H:i:s') }}</td>
                                    <td>
                                        <a href="#modal{{$poroVenta->id }}" class="btn-floating btn-small waves-effect waves-light blue modal-trigger"><i class="material-icons">visibility</i></a>


                                        @if (Auth::user()->has_role(config('app.admin_role')) || Auth::user()->has_role(config('app.jefe_local_role')) )
                                        
                                            <a href="{{ route('backoffice.ventas_poroporo.edit', $poroVenta) }}" class="btn-floating btn-small waves-effect waves-light purple"><i class="material-icons">edit</i></a>

                                            <a href="#" class="btn-floating btn-small waves-effect waves-light red btn-eliminar-venta" data-url="{{ route('backoffice.ventas_poroporo.destroy', ['ventas_poroporo' => $poroVenta->id]) }}"><i class="material-icons">delete</i></a>
                                            
                                        @endif

                                    </td>
                                    
                                </tr>
                                @include('themes.backoffice.pages.poroporo.venta.includes.modal_venta', ['poroVenta' => $poroVenta])
                                @endforeach
                            @else
                            <tr>
                                <td colspan="2"></td>
                                <td><h5><strong>No hay ventas en la semana actual ({{ \Carbon\Carbon::parse($inicio)->isoFormat('D MMM') }} al {{ \Carbon\Carbon::parse($fin)->isoFormat('D MMM') }})</strong></h5></td>
                                

                            </tr>
                            @endif
                        </tbody>
                    </table>

                    <form id="form-eliminar-venta" method="POST" style="display: none;">
                        @csrf
                        @method('DELETE')
                    </form>
                    
                </div>
            </div>

            <div class="col s12 m3">
                @include('themes.backoffice.pages.poroporo.includes.poro_nav', $poroProductos)
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
