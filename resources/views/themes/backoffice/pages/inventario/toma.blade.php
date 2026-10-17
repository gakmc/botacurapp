@extends('themes.backoffice.layouts.admin')

@section('title', 'Toma de inventario')

@section('content')
<div class="section">
  <p class="caption"><strong>Toma de inventario</strong></p>
  <div class="divider"></div>
  @include('themes.backoffice.pages.inventario._nav')

  <p style="max-width:70ch;color:#546e7a">
    Cuenta lo que hay físicamente y anótalo en la unidad indicada. Deja en blanco lo que no contaste.
    La primera vez que cuentas un insumo queda como <strong>stock inicial</strong>; las siguientes, la diferencia queda como
    <strong>ajuste</strong> (merma o sobrante). Revisa también el costo: es el valor neto (sin IVA) por unidad.
  </p>

  <form method="GET" action="{{ route('backoffice.inventario.toma.create') }}" style="display:flex;gap:10px;flex-wrap:wrap;max-width:720px">
    <select name="ubicacion" class="browser-default" onchange="this.form.submit()" style="flex:1;min-width:200px">
      <option value="">Todas las ubicaciones</option>
      @foreach($ubicaciones as $u)<option value="{{ $u }}" {{ $ubicacion === $u ? 'selected' : '' }}>{{ $u }}</option>@endforeach
    </select>
    <select name="sector_id" class="browser-default" onchange="this.form.submit()" style="flex:1;min-width:200px">
      <option value="">Todos los sectores</option>
      @foreach($sectores as $s)<option value="{{ $s->id }}" {{ (string)$sectorId === (string)$s->id ? 'selected' : '' }}>{{ $s->nombre }}</option>@endforeach
    </select>
  </form>

  <form method="POST" action="{{ route('backoffice.inventario.toma.store') }}" id="form-toma">
    @csrf
    <input type="hidden" name="sector_id" value="{{ $sectorId }}">
    <div class="card-panel" style="overflow-x:auto;margin-top:12px">
      <table class="highlight" style="min-width:680px">
        <thead><tr>
          <th>Insumo</th><th class="inv-num">Stock sistema</th><th>Contado</th><th class="inv-num">Diferencia</th><th>Costo neto por unidad</th>
        </tr></thead>
        <tbody>
        @php $seccion = false; @endphp
        @foreach($insumos as $i)
          @php
            $um = optional($i->unidadMedida)->abreviatura;
            $iniciado = isset($inicios[$i->id]);
            $stock = $iniciado ? (float) $i->cantidad : 0;
          @endphp
          @if($i->ubicacion !== $seccion)
            @php $seccion = $i->ubicacion; @endphp
            <tr style="background:#eceff1"><td colspan="5" style="font-weight:700;color:#37474f">{{ $seccion ?: 'Sin ubicación' }}</td></tr>
          @endif
          <tr>
            <td>{{ $i->nombre }} @if(! $iniciado)<span class="new badge orange" data-badge-caption="Primera toma"></span>@endif</td>
            <td class="inv-num">{{ $iniciado ? rtrim(rtrim(number_format($stock, 3, ',', '.'), '0'), ',') . ' ' . $um : '—' }}</td>
            <td style="min-width:140px">
              <div style="display:flex;align-items:center;gap:6px">
                <input type="number" step="any" min="0" name="conteo[{{ $i->id }}]" value="{{ old('conteo.' . $i->id) }}"
                       data-stock="{{ $stock }}" class="js-conteo" style="margin:0;width:100px">
                <span>{{ $um }}</span>
              </div>
            </td>
            <td class="inv-num js-diff">—</td>
            <td style="min-width:150px">
              <div style="display:flex;align-items:center;gap:6px">
                <span>$</span>
                <input type="number" step="any" min="0" name="costo[{{ $i->id }}]" value="{{ old('costo.' . $i->id, $i->valor) }}" style="margin:0;width:100px">
                <span>/ {{ $um }}</span>
              </div>
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>

    <div class="row">
      <div class="col s12 m4"><label>Fecha y hora del conteo</label>
        <input type="datetime-local" name="fecha" value="{{ old('fecha', now()->format('Y-m-d\TH:i')) }}"></div>
      <div class="col s12 m6"><label>Observación</label>
        <input type="text" name="observacion" maxlength="255" value="{{ old('observacion') }}" placeholder="Quién contó, notas"></div>
      <div class="col s12 m2" style="margin-top:22px"><button class="btn waves-effect" type="submit"><i class="material-icons left">save</i>Guardar</button></div>
    </div>
  </form>
</div>
@endsection

@section('foot')
<script>
  document.querySelectorAll('.js-conteo').forEach(function (inp) {
    var celda = inp.closest('tr').querySelector('.js-diff');
    function pintar() {
      if (inp.value === '') { celda.textContent = '—'; celda.style.color = ''; return; }
      var d = parseFloat(inp.value) - parseFloat(inp.dataset.stock || 0);
      celda.textContent = (d > 0 ? '+' : '') + d.toLocaleString('es-CL', { maximumFractionDigits: 3 });
      celda.style.color = d < 0 ? '#c62828' : (d > 0 ? '#2e7d32' : '');
    }
    inp.addEventListener('input', pintar); pintar();
  });
  document.getElementById('form-toma').addEventListener('submit', function (e) {
    var n = Array.prototype.filter.call(document.querySelectorAll('.js-conteo'), function (i) { return i.value !== ''; }).length;
    if (!n) { e.preventDefault(); alert('No ingresaste ningún conteo.'); }
  });
</script>
@endsection
