@extends('themes.backoffice.layouts.admin')

@section('title', 'Tipos de terapias')

@section('head')
@endsection


@section('breadcrumbs')
{{-- <li><a href="{{route('backoffice.cliente.index')}}">Clientes del Sistema</a></li> --}}
{{-- <li>{{$cliente->nombre_cliente}}</li> --}}
@endsection

@section('dropdown_settings')
{{-- <li><a href="{{ route('backoffice.reserva.create',$cliente->id) }}" class="grey-text text-darken-2">Crear Reserva</a></li> --}}
@endsection


@section('content')
<div class="section">
    <p class="caption"><strong>Masajes</strong></p>
<div class="row right">
  <div class="col s12">
    <a href="{{ route('backoffice.masajes.valores') }}"
       class="btn {{ request()->routeIs('backoffice.masajes.valores') ? 'pink-text text-darken-2' : '' }}" style="background-color: #039B7B">
       Activos
    </a>
    <a href="{{ route('backoffice.masajes.valores.inactivos') }}"
       class="btn {{ request()->routeIs('backoffice.masajes.valores.inactivos') ? 'pink-text text-darken-2' : '' }}" style="background-color: #039B7B">
       Inactivos
    </a>
  </div>
</div>
    
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 ">
                <div class="card-panel">
                    <div class="row">
                        
                        
                        
                        {{-- CONTENIDO --}}
                        
                        
                        <table class="bordered highlight responsive-table">
                            <thead>
                                <tr>
                                    <th data-field="categoria">Categoría</th>
                                    <th data-field="tipo">Tipo Masaje</th>
                                    <th data-field="duracion">Duración</th>
                                    <th data-field="precio_unitario">Precio Unitario</th>
                                    <th data-field="precio_pareja">Precio Pareja</th>
                                    <th data-field="pago_maso">Pago Masoterapeuta</th>
                                    <th data-field="acciones">Acciones</th>

                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($masajes as $masaje)
                                @php $rowspan = max(1, $masaje->precios->count()); @endphp
                                
                                    @forelse ($masaje->precios as $i => $precio)
                                    <tr>
                                        @if ($i === 0)
                                        <td rowspan="{{ $rowspan }}">{{ $masaje->categoria->nombre }}</td>
                                        <td rowspan="{{ $rowspan }}">{{ $masaje->nombre }}</td>
                                        @endif
                                        
                                        <td>{{ $precio->duracion_minutos }} min</td>
                                        <td>${{ number_format((int)$precio->precio_unitario, 0, ',', '.') }}</td>
                                        <td>
                                            @if(!is_null($precio->precio_pareja) && $precio->precio_pareja > 0)
                                            ${{ number_format((int)$precio->precio_pareja, 0, ',', '.') }}
                                            @else
                                            —
                                            @endif
                                        </td>
                                        <td>
                                            @if(!is_null($precio->pago_masoterapeuta) && $precio->pago_masoterapeuta > 0)
                                            ${{ number_format((int)$precio->pago_masoterapeuta, 0, ',', '.') }}
                                            @else
                                            —
                                            @endif
                                        </td>
                                    @if($i===0)
                                        <td rowspan="{{ $rowspan }}">
                                        <form method="POST" action="{{ route('backoffice.masajes.estado',$masaje) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="activo" value="0">
                                            <button class="btn-small waves-effect tooltipped" data-position="top" data-delay="50" data-tooltip="Desactivar"><i class='material-icons'>block</i></button>
                                        </form>
                                        </td>
                                    @endif
                                    </tr>
                                    @empty
                                    <tr>
                                        <td>{{ $masaje->categoria->nombre }}</td>
                                        <td>{{ $masaje->nombre }}</td>
                                        <td colspan="3">Sin precios registrados</td>
                                    </tr>
                                    @endforelse
                                @endforeach
                                
                                
                                
                            </tbody>
                        </table>
                        
                        
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('foot')
<script>$(function(){$('ul.tabs').tabs();});</script>


<script>
    @if(session('status'))
        Swal.fire({
            toast: true,
            position: '',
            icon: 'success',
            title: '{{ session('status') }}',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        });
        @endif
</script>

@endsection