@extends('themes.backoffice.layouts.admin')

@section('title', 'Kardex ' . $insumo->nombre)

@section('content')
<div class="section">
  <p class="caption"><strong>Kardex · {{ $insumo->nombre }}</strong></p>
  <div class="divider"></div>
  @include('themes.backoffice.pages.inventario._nav')

  @php
    $um = optional($insumo->unidadMedida)->abreviatura;
    $fmt = function ($n) { return rtrim(rtrim(number_format((float) $n, 3, ',', '.'), '0'), ','); };
  @endphp
  <p>Stock actual: <strong>{{ $fmt($insumo->cantidad) }} {{ $um }}</strong> · Costo promedio neto: <strong>${{ number_format($insumo->valor, 0, ',', '.') }} / {{ $um }}</strong></p>

  <div class="card-panel" style="overflow-x:auto">
    @if($detalles->isEmpty())
      <p class="center grey-text">Este insumo aún no tiene movimientos. Parte con una toma de inventario.</p>
    @else
    <table class="highlight" style="min-width:640px">
      <thead><tr><th>Fecha</th><th>Origen</th><th class="inv-num">Entrada</th><th class="inv-num">Salida</th><th class="inv-num">Saldo</th><th class="inv-num">Costo unit.</th><th>Nota</th></tr></thead>
      <tbody>
      @foreach($detalles as $d)
        @php $q = (float) $d->cantidad_base; @endphp
        <tr>
          <td style="white-space:nowrap">{{ \Carbon\Carbon::parse($d->movimiento->fecha)->format('d/m/Y H:i') }}</td>
          <td>{{ $origenes[$d->movimiento->origen] ?? $d->movimiento->origen }}</td>
          <td class="inv-num" style="color:#2e7d32">{{ $q > 0 ? $fmt($q) : '' }}</td>
          <td class="inv-num" style="color:#c62828">{{ $q < 0 ? $fmt(-$q) : '' }}</td>
          <td class="inv-num"><strong>{{ $fmt($d->saldo) }}</strong></td>
          <td class="inv-num">${{ number_format($d->costo_unitario, 0, ',', '.') }}</td>
          <td class="grey-text" style="font-size:.85rem">{{ $d->movimiento->observacion }}</td>
        </tr>
      @endforeach
      </tbody>
    </table>
    @endif
  </div>
</div>
@endsection
