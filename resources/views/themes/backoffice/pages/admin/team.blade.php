@extends('themes.backoffice.layouts.admin')

@section('title', 'Panel de Administración')

@section('breadcrumbs')
<li>Equipos Asignados</li>
@endsection

@section('content')

<div class="section">
    <p class="caption"><strong>Equipos de la semana</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 m8">
                <div class="card-panel">

                    <div class="row">
                        <form method="POST" action="{{ route('backoffice.sueldos.store') }}">
                            @csrf

                            @php
                                $counter = 0;
                            @endphp
                            @foreach ($diasSemana as $dia)
                            <div class="col m4 l6">
                                <div class="card z-depth-0">
                                    <div class="card-header">
                                        <h5><strong>{{ $dia }}</strong></h5>
                                    </div>
                                    <div class="card-body">
                                        {{-- Mostrar asignaciones por día --}}
                                        @if (isset($asignacionesPorDia[$dia]))
                                        @foreach ($asignacionesPorDia[$dia] as $asignacion)
                                        @foreach ($asignacion->users as $user)
                                        <p><strong>{{ $user->name }}</strong> - $40.000

                                            @if (isset($propinasPorDia[$dia]))
                                            - Propinas del dia: ${{ number_format($propinasPorDia[$dia]['propina'], 0,
                                            ',', '.')
                                            }}


                                            {{-- Campos ocultos para el envío del formulario --}}
                                            <input type="hidden"
                                                name="sueldos[{{ $counter }}][dia_trabajado]"
                                                value="{{ $propinasPorDia[$dia]['dia_trabajado'] }}">
                                            <input type="hidden" name="sueldos[{{ $counter }}][valor_dia]"
                                                value="40000">
                                            <input type="hidden" name="sueldos[{{ $counter }}][sub_sueldo]"
                                                value="{{ number_format(40000 + $propinasPorDia[$dia]['propina'],0,',', '') }}">
                                            <input type="hidden" name="sueldos[{{ $counter }}][total_pagar]"
                                                value="{{ number_format(40000 + $propinasPorDia[$dia]['propina'],0,',', '') }}">
                                            <input type="hidden" name="sueldos[{{ $counter }}][id_user]"
                                                value="{{ $user->id }}">

                                            @php
                                                $counter++
                                            @endphp
                                            @endif
                                        </p>
                                        @endforeach
                                        @endforeach
                                        @else
                                        <p>No hay asignaciones.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                            <button type="submit" class="btn btn-primary right"><i class='material-icons right'>account_balance_wallet</i>Cerrar sueldos de la semana</button>
                        </form>
                    </div>
                </div>
            </div>


            <div class="col s12 m4">
                @include('themes.backoffice.pages.admin.includes.total_nav')
            </div>
        </div>
    </div>
</div>
@endsection

@section('foot')
@endsection