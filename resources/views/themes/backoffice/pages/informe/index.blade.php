@extends('themes.backoffice.layouts.admin')

@section('title', 'Informes')

@section('head')
<style>
    .report-card {
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    .report-title {
        font-size: 1.3rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }
    .report-subtitle {
        font-size: 0.9rem;
        color: #777;
        margin-bottom: 1rem;
    }
    .placeholder-chart {
        height: 250px;
        background: #f2f2f2;
        border: 2px dashed #ccc;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        color: #999;
        border-radius: 6px;
    }
</style>
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Panel de Informes</strong></p>
    <div class="divider"></div>

    <div class="row">
        {{-- Informe 1 --}}
        <div class="col s12 m6">
            <div class="card report-card">
                <div class="report-title">10 Bebestibles más consumidos</div>
                <div class="report-subtitle">Mes actual</div>
                <div class="placeholder-chart">Gráfico próximamente</div>
                <ul class="collection">
                    @foreach ($bebestiblesMasConsumidos as $index => $bebestible)
                    <li class="collection-item">{{$index+1}}. {{$bebestible->producto->nombre}} - Cantidad:{{$bebestible->total}}</li>
                        
                    @endforeach
                    {{-- <li class="collection-item">2. Jugo Natural</li>
                    <li class="collection-item">3. Cerveza Artesanal</li> --}}
                </ul>
            </div>
        </div>

        {{-- Informe 2 --}}
        <div class="col s12 m6">
            <div class="card report-card">
                <div class="report-title">10 Programas más contratados</div>
                <div class="report-subtitle">Mes actual</div>
                <div class="placeholder-chart">Gráfico próximamente</div>
                <ul class="collection">
                    {{-- <li class="collection-item">1. Programa Relax</li> --}}
                    @foreach ($programasMasContratados as $index=>$programa)
                    <li class="collection-item">{{$index+1}}. {{$programa->programa->nombre_programa}}</li>
                    @endforeach
                    {{-- <li class="collection-item">2. Programa Familiar</li>
                    <li class="collection-item">3. Programa Premium</li> --}}
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
