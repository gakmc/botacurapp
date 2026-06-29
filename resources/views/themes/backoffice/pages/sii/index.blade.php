@extends('themes.backoffice.layouts.admin')

@section('title', 'Importar desde SII')

@section('breadcrumbs')
<li><a href="{{ route('backoffice.egreso.index') }}">Egresos</a></li>
<li>Importar desde SII</li>
@endsection

@section('dropdown_settings')
<li><a href="{{ route('backoffice.egreso.index') }}" class="grey-text text-darken-2">Ver Egresos</a></li>
@endsection

@section('content')
<div class="section">
    <div class="card-panel">

        {{-- Alerta si no hay credenciales --}}
        @if (!$credencialesOk)
        <div class="card-panel red lighten-4">
            <span class="red-text text-darken-2">
                <i class="material-icons tiny">warning</i>
                Las credenciales de SII no están configuradas. Agrega
                <code>SII_API_KEY</code> y <code>SII_RUT_EMPRESA</code> al archivo <code>.env</code>.
            </span>
        </div>
        @endif

        <h5 class="center">Importar Facturas de Compra desde SII</h5>
        <p class="center grey-text">Selecciona el período para consultar el Registro de Compras y Ventas (RCV)</p>

        <div class="divider" style="margin: 24px 0;"></div>

        <form action="{{ route('backoffice.sii.listar') }}" method="GET">
            <div class="row center" style="margin-top: 30px;">

                <div class="input-field col s12 m3 offset-m3">
                    <select name="mes" id="mes">
                        @foreach(range(1,12) as $m)
                            <option value="{{ $m }}" {{ $m == $mes ? 'selected' : '' }}>
                                {{ ucfirst(\Carbon\Carbon::create(null, $m)->locale('es')->isoFormat('MMMM')) }}
                            </option>
                        @endforeach
                    </select>
                    <label>Mes</label>
                </div>

                <div class="input-field col s12 m2">
                    <select name="anio" id="anio">
                        @foreach(range(now()->year, 2020, -1) as $a)
                            <option value="{{ $a }}" {{ $a == $anio ? 'selected' : '' }}>{{ $a }}</option>
                        @endforeach
                    </select>
                    <label>Año</label>
                </div>

                <div class="input-field col s12 m2" style="margin-top: 26px;">
                    <button type="submit" class="btn waves-effect" style="background-color: #039B7B;" {{ !$credencialesOk ? 'disabled' : '' }}>
                        Consultar SII
                        <i class="material-icons right">search</i>
                    </button>
                </div>

            </div>
        </form>

        @if(session('success'))
        <div class="card-panel green lighten-4" style="margin-top: 20px;">
            <span class="green-text text-darken-2">
                <i class="material-icons tiny">check_circle</i>
                {{ session('success') }}
            </span>
        </div>
        @endif

        @if(session('error'))
        <div class="card-panel red lighten-4" style="margin-top: 20px;">
            <span class="red-text text-darken-2">
                <i class="material-icons tiny">error</i>
                {{ session('error') }}
            </span>
        </div>
        @endif

    </div>
</div>
@endsection

@section('foot')
<script>
    $(document).ready(function () {
        $('select').material_select();
    });
</script>
@endsection
