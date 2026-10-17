@extends('themes.backoffice.layouts.admin')

@section('title', 'Configurar insumos')

@section('content')
<div class="section">
  <p class="caption"><strong>Configurar insumos</strong></p>
  <div class="divider"></div>
  @include('themes.backoffice.pages.inventario._nav')

  <p style="max-width:75ch;color:#546e7a">
    El <strong>proveedor habitual</strong> define en qué sección de la lista de compras aparece cada insumo (Cencosud, Cocha, Lo Valledor…).
    El <strong>stock objetivo</strong> es hasta dónde se repone: la lista sugiere comprar objetivo − stock − lo ya pedido.
    Todo en la unidad del insumo.
  </p>

  <form method="GET" action="{{ route('backoffice.inventario.compras.configurar') }}" style="max-width:420px">
    <select name="id_proveedor" class="browser-default" onchange="this.form.submit()">
      <option value="">Todos los insumos</option>
      <option value="sin" {{ $provId === 'sin' ? 'selected' : '' }}>Sin proveedor asignado</option>
      @foreach($proveedores as $p)<option value="{{ $p->id }}" {{ (string) $provId === (string) $p->id ? 'selected' : '' }}>{{ $p->nombre }}</option>@endforeach
    </select>
  </form>

  <form method="POST" action="{{ route('backoffice.inventario.compras.configurar.guardar') }}">
    @csrf
    <div class="card-panel" style="overflow-x:auto;margin-top:12px">
      <table class="highlight" style="min-width:1100px">
        <thead><tr>
          <th>Insumo</th><th>UM</th><th>Proveedor habitual</th><th>Sector</th><th>Ubicación</th>
          <th>Crítico</th><th>Objetivo</th><th>Presentación</th>
        </tr></thead>
        <tbody>
        @foreach($insumos as $i)
          <tr>
            <td><input type="text" name="ins[{{ $i->id }}][nombre]" value="{{ $i->nombre }}" style="margin:0;min-width:180px"></td>
            <td>{{ optional($i->unidadMedida)->abreviatura }}</td>
            <td><select name="ins[{{ $i->id }}][id_proveedor]" class="browser-default" style="min-width:200px">
              <option value="">—</option>
              @foreach($proveedores as $p)<option value="{{ $p->id }}" {{ (string) $i->id_proveedor === (string) $p->id ? 'selected' : '' }}>{{ \Illuminate\Support\Str::limit($p->nombre, 40) }}</option>@endforeach
            </select></td>
            <td><select name="ins[{{ $i->id }}][id_sector]" class="browser-default">
              @foreach($sectores as $s)<option value="{{ $s->id }}" {{ (string) $i->id_sector === (string) $s->id ? 'selected' : '' }}>{{ $s->nombre }}</option>@endforeach
            </select></td>
            <td><input type="text" name="ins[{{ $i->id }}][ubicacion]" value="{{ $i->ubicacion }}" placeholder="Equipo 3, Despensa 1…" style="margin:0;width:130px"></td>
            <td><input type="number" step="any" min="0" name="ins[{{ $i->id }}][stock_critico]" value="{{ (float) $i->stock_critico }}" style="margin:0;width:80px"></td>
            <td><input type="number" step="any" min="0" name="ins[{{ $i->id }}][stock_objetivo]" value="{{ $i->stock_objetivo !== null ? (float) $i->stock_objetivo : '' }}" style="margin:0;width:80px"></td>
            <td><input type="text" name="ins[{{ $i->id }}][presentacion]" value="{{ $i->presentacion }}" placeholder="caja 12, saco 25 kg" style="margin:0;width:130px"></td>
          </tr>
        @endforeach
          <tr style="background:#f1f8e9">
            <td><input type="text" name="nuevo[nombre]" placeholder="+ Nuevo insumo" style="margin:0;min-width:180px"></td>
            <td><select name="nuevo[id_unidad_medida]" class="browser-default">
              @foreach($unidades as $u)<option value="{{ $u->id }}">{{ $u->abreviatura }}</option>@endforeach
            </select></td>
            <td><select name="nuevo[id_proveedor]" class="browser-default" style="min-width:200px">
              <option value="">—</option>
              @foreach($proveedores as $p)<option value="{{ $p->id }}" {{ (string) $provId === (string) $p->id ? 'selected' : '' }}>{{ \Illuminate\Support\Str::limit($p->nombre, 40) }}</option>@endforeach
            </select></td>
            <td><select name="nuevo[id_sector]" class="browser-default">
              @foreach($sectores as $s)<option value="{{ $s->id }}">{{ $s->nombre }}</option>@endforeach
            </select></td>
            <td><input type="text" name="nuevo[ubicacion]" style="margin:0;width:130px"></td>
            <td><input type="number" step="any" min="0" name="nuevo[stock_critico]" style="margin:0;width:80px"></td>
            <td><input type="number" step="any" min="0" name="nuevo[stock_objetivo]" style="margin:0;width:80px"></td>
            <td><input type="text" name="nuevo[presentacion]" style="margin:0;width:130px"></td>
          </tr>
        </tbody>
      </table>
    </div>
    <button class="btn waves-effect" type="submit"><i class="material-icons left">save</i>Guardar cambios</button>
  </form>
</div>
@endsection

@section('foot')
<script>
  // Envía solo las filas modificadas (evita el límite de campos por formulario con muchos insumos)
  (function () {
    var form = document.querySelector('form[method="POST"]');
    form.querySelectorAll('tbody tr').forEach(function (tr) {
      tr.addEventListener('input', function () { tr.dataset.cambio = '1'; });
      tr.addEventListener('change', function () { tr.dataset.cambio = '1'; });
    });
    form.addEventListener('submit', function () {
      form.querySelectorAll('tbody tr').forEach(function (tr) {
        if (tr.dataset.cambio !== '1') tr.querySelectorAll('input, select').forEach(function (el) { el.disabled = true; });
      });
    });
  })();
</script>
@endsection
