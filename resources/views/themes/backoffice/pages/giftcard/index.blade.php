@extends('themes.backoffice.layouts.admin')

@section('title', 'Gift Cards')

@section('breadcrumbs')
{{-- <li><a href="{{route('backoffice.cliente.index')}}">Clientes del Sistema</a></li> --}}
{{-- <li>{{$cliente->nombre_cliente}}</li> --}}
@endsection

@section('dropdown_settings')
<li><a href="{{ route('backoffice.giftcards.create') }}" class="grey-text text-darken-2">Crear Gift Card</a></li>
@endsection

@section('content')
<div class="section">
    
    @php
        $filtros = ['sin_usar' => 'Vigentes sin usar', 'por_vencer' => 'Por vencer (7 días)', 'vencidas' => 'Vencidas sin usar', 'usadas' => 'Usadas', 'todas' => 'Todas'];
    @endphp
    <div class="row" style="margin-bottom:0">
        <div class="col s6 m3"><div class="card-panel green lighten-5" style="padding:12px"><strong style="font-size:22px">{{ $resumen['vigentes'] }}</strong><br>Vigentes sin usar<br><small>${{ number_format($resumen['monto_vigente'], 0, ',', '.') }} · {{ $resumen['personas_vigente'] }} personas</small></div></div>
        <div class="col s6 m3"><div class="card-panel orange lighten-5" style="padding:12px"><strong style="font-size:22px">{{ $resumen['por_vencer'] }}</strong><br>Vencen en 7 días o menos</div></div>
        <div class="col s6 m3"><div class="card-panel red lighten-5" style="padding:12px"><strong style="font-size:22px">{{ $resumen['vencidas'] }}</strong><br>Vencidas sin usar</div></div>
        <div class="col s6 m3"><div class="card-panel grey lighten-4" style="padding:12px"><strong style="font-size:22px">{{ $resumen['usadas'] }}</strong><br>Usadas</div></div>
    </div>
    <div style="margin:4px 0 8px 0">
        @foreach ($filtros as $k => $t)
            <a href="{{ route('backoffice.giftcards.index', ['ver' => $k]) }}" class="btn-small {{ $ver === $k ? '' : 'btn-flat' }}" style="margin:2px">{{ $t }}</a>
        @endforeach
    </div>

    <p class="caption left-align"><strong>Gift Cards: {{ $filtros[$ver] }}</strong> ({{ $giftcards->count() }})</p>

    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12">
                <div class="card-panel">
                    <div class="row">


                        @if ($giftcards->isEmpty())
                            <h5 class="center">No hay gift cards en «{{ $filtros[$ver] }}»</h5>
                        @else
                            <table class="highlight responsive-table" style="font-size:13px">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>De → Para</th>
                                        <th>Programa</th>
                                        <th>Monto</th>
                                        <th>Comprada</th>
                                        <th>Vence</th>
                                        <th>Vigencia</th>
                                        <th>Uso</th>
                                        <th>Contacto</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($giftcards as $gc)
                                    @php $reservaGc = optional($gc->venta)->reserva; @endphp
                                    <tr>
                                        <td><strong>{{ $gc->codigo }}</strong></td>
                                        <td>{{ $gc->de }}<br><span class="grey-text">→ {{ $gc->para }}</span></td>
                                        <td>{{ optional($gc->programa)->nombre_programa ?? '—' }}<br><span class="grey-text">{{ $gc->cantidad_personas ?: '?' }} persona{{ (int) $gc->cantidad_personas === 1 ? '' : 's' }}</span></td>
                                        <td>${{ number_format($gc->monto, 0, ',', '.') }}</td>
                                        <td>{{ $gc->comprada }}</td>
                                        <td>{{ $gc->valido }}</td>
                                        <td><span class="badge white-text {{ $gc->vigencia['color'] }}" style="float:none;margin-left:0">{{ $gc->vigencia['texto'] }}</span></td>
                                        <td>
                                            @if ($gc->usada)
                                                {{ $gc->fecha_uso ? \Carbon\Carbon::parse($gc->fecha_uso)->format('d-m-Y') : 'Usada' }}
                                                @if ($reservaGc)
                                                    <br><a href="{{ route('backoffice.reserva.show', $reservaGc->id) }}">Reserva {{ $reservaGc->id }}</a>
                                                    <span class="grey-text">· visita {{ $reservaGc->fecha_visita }}</span>
                                                @endif
                                            @else
                                                <span class="grey-text">Sin usar</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($gc->telefono)<a href="https://wa.me/{{ preg_replace('/\D/', '', $gc->telefono) }}" target="_blank">{{ $gc->telefono }}</a>@endif
                                            @if ($gc->correo)<br><span class="grey-text">{{ $gc->correo }}</span>@endif
                                        </td>
                                        <td style="white-space:nowrap">
                                            <a class="btn-small btn-floating blue" href="{{route('backoffice.giftcards.show', $gc)}}"><i class='material-icons'>remove_red_eye</i></a>
                                            @if(! $gc->usada)
                                                <a class="btn-small btn-floating purple" href="{{route('backoffice.giftcards.edit', $gc)}}"><i class='material-icons'>edit</i></a>

                                                <a onclick="enviar_formulario({{$gc->id}})" class="btn-small btn-floating red" href="#"><i class='material-icons'>delete</i></a>

                                                <form method="post" action="{{route('backoffice.giftcards.destroy', $gc->id) }} " name="delete_form_{{$gc->id}}">
                                                    {{csrf_field()}}
                                                    {{method_field('DELETE')}}
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif

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

<script>
    function enviar_formulario(id)
    {
        Swal.fire({
            title: "¿Deseas eliminar esta Gift Card?",
            text: "Esta acción no se puede deshacer",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "Si, continuar",
            cancelButtonText: "No, cancelar",
            closeOnCancel: false,
            closeOnConfirm: true
        }).then((result)=> {
            if(result.value){
                document.forms['delete_form_'+id].submit();
            }else{
                Swal.fire(
                    'Operación Cancelada',
                    'La gift card no fue eliminada',
                    'error'
                )
            }
        });
    }
</script>
@endsection