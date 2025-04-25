@extends('themes.backoffice.layouts.admin')

@section('title', 'Venta Directa')

@section('head')
@endsection

@section('breadcrumbs')
{{-- <li><a href="">Venta directa</a></li> --}}
@endsection

@section('dropdown_settings')
{{-- Opciones adicionales aquí --}}
<li><a href="{{route("backoffice.venta_directa.create")}}">Generar Venta directa</a></li>
@endsection

@section('content')

<div class="section">
    <p class="caption"><strong>Venta Directa</strong></p> 
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12">
                <div class="card-panel">

                    <table>
                        <thead>
                            <tr>
                                <th data-field="id_user">Atendido por</th>
                                <th data-field="valor_venta">Valor Venta</th>
                                <th data-field="tiene_propina">Posee propina</th>
                                <th data-field="hora">Hora generada</th>
                                <th data-field="acciones">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($ventasDirectas->isNotEmpty())
                                @foreach ($ventasDirectas as $ventaDirecta)
                                <tr>
                                    <td>{{ $ventaDirecta->user->name }}</td>
                                    <td>${{ $ventaDirecta->tiene_propina ? number_format($ventaDirecta->total,0,'','.') : number_format($ventaDirecta->subtotal,0,'','.') }}</td>
                                    <td>
                                        @if ($ventaDirecta->tiene_propina)
                                        <a class="btn disabled"><span class="black-text">${{number_format($ventaDirecta->valor_propina, 0,'','.')}}</span><i class='material-icons green-text left '>check_circle</i></a>
                                        @else
                                        <a class="btn disabled"><span class="black-text">${{number_format($ventaDirecta->valor_propina, 0,'','.')}}</span><i class='material-icons red-text left '>cancel</i></a>
                                        @endif
                                        </td>
                                    <td>{{ $ventaDirecta->created_at->format('H:i:s') }}</td>
                                    <td>
                                        {{-- <a href="{{ route('backoffice.venta_directa.show', $ventaDirecta->id) }}" class="btn-floating btn-small waves-effect waves-light blue"><i class="material-icons">visibility</i></a> --}}
                                        <a href="#modal{{$ventaDirecta->id }}" class="btn-floating btn-small waves-effect waves-light blue modal-trigger"><i class="material-icons">visibility</i></a>
                                        @if (Auth::user()->has_role(config('app.admin_role')) || Auth::user()->has_role(config('app.jefe_local_role')) )
                                        <a href="{{ route('backoffice.venta_directa.edit', $ventaDirecta->id) }}" class="btn-floating btn-small waves-effect waves-light purple"><i class="material-icons">edit</i></a>
                                        <a href="{{ route('backoffice.venta_directa.destroy', $ventaDirecta->id) }}" class="btn-floating btn-small waves-effect waves-light red"><i class="material-icons">delete</i></a>
                                            
                                        @endif

                                    </td>
                                    
                                </tr>
                                @include('themes.backoffice.pages.venta_directa.includes.modal_venta', ['ventaDirecta' => $ventaDirecta])
                                @endforeach
                            @else
                            <tr>
                                <td colspan="2"></td>
                                <td><h5><strong>No hay registro de ventas para hoy</strong></h5></td>
                            </tr>
                            @endif
                        </tbody>
                    </table>


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
@endsection
