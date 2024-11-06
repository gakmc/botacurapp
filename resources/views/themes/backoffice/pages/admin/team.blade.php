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
            <div class="col s12 ">
                <div class="card-panel">
                    <div class="row">
                        @foreach ($diasSemana as $dia)
                            <div class="col m4">
                                <div class="card">
                                    <div class="card-header">
                                        {{ $dia }}
                                    </div>
                                    <div class="card-body">
                                        {{-- Mostrar asignaciones por día --}}
                                        @if (isset($asignacionesPorDia[$dia]))
                                            @foreach ($asignacionesPorDia[$dia] as $asignacion)
                                                @foreach ($asignacion->users as $user)
                                                    <p>{{ $user->name }} - $40.000 
                                                        @if (isset($propinasPorDia[$dia]) && $propinasPorDia[$dia] > 0)
                                                            - Propina: ${{ number_format($propinasPorDia[$dia], 0) }}
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
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('foot')
@endsection
