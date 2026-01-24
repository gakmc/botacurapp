@extends('themes.backoffice.layouts.admin')

@section('title','Insumos')

@section('content')
<div class="section">
  <p class="caption"><strong>Insumos</strong></p>
  <div class="divider"></div>

  {{-- Filtros --}}
  <form method="GET" action="{{ route('backoffice.insumo.index') }}">
    <div class="row">
      <div class="col s12 m4">
        <div class="input-field">
          <input id="search" name="search" type="text" value="{{ $search }}">
          <label for="search" class="active">Buscar por nombre</label>
        </div>
      </div>

      <div class="col s12 m4">
        <div class="input-field">
          <select id="sector_id" name="sector_id">
            <option value="" {{ !$sectorId ? 'selected' : '' }}>Todos los sectores</option>
            @foreach($sectores as $s)
              <option value="{{ $s->id }}" {{ (string)$sectorId === (string)$s->id ? 'selected' : '' }}>
                {{ $s->nombre }}
              </option>
            @endforeach
          </select>
          <label>Sector</label>
        </div>
      </div>

      <div class="col s12 m2" style="margin-top: 18px;">
        <p>
          <label>
            <input type="checkbox" name="criticos" value="1" {{ $soloCrit ? 'checked' : '' }}>
            <span>Solo críticos</span>
          </label>
        </p>
      </div>

      <div class="col s12 m2 right-align" style="margin-top: 18px;">
        <button class="btn waves-effect" type="submit">
          <i class="material-icons left">search</i>Filtrar
        </button>
      </div>
    </div>
  </form>

  {{-- Acciones rápidas --}}
  <div class="row">
    <div class="col s12 right-align" style="margin-bottom:10px;">
      <a href="{{ route('backoffice.insumo.create') }}" class="btn waves-effect">
        <i class="material-icons left">add</i>Ingresar Nuevo Insumo
      </a>
    </div>
  </div>

  {{-- Tabla unificada --}}
  <div class="card-panel">
    @if($insumos->isEmpty())
      <h5 class="center">No existen registros</h5>
    @else
      <table class="highlight responsive-table">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Valor</th>
            <th>Cantidad</th>
            <th>UM</th>
            <th>Sector</th>
            <th>Estado</th>
            <th class="center">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($insumos as $insumo)
            @php $esCritico = $insumo->cantidad <= $insumo->stock_critico; @endphp
            <tr @if($esCritico) style="background:#ef5350;color:#fff" @endif>
              <td>{{ $insumo->nombre }}</td>
              <td>{{ '$'.number_format($insumo->valor, 0, '', '.') }}</td>
              <td>
                {{ rtrim(rtrim(number_format($insumo->cantidad,3,'.',''), '0'),'.') }}
                {{ $insumo->cantidad <= 1 ? $insumo->unidadMedida->abreviatura : $insumo->unidadMedida->abreviatura.'s' }}.
              </td>
              <td>{{ $insumo->unidadMedida->abreviatura }}</td>
              <td>{{ $insumo->sector->nombre }}</td>
              <td>
                @if($esCritico)
                  <span class="new badge red" data-badge-caption="Crítico"></span>
                @else
                  <span class="new badge green" data-badge-caption="OK"></span>
                @endif
              </td>
              <td class="center">
                <a href="{{ route('backoffice.insumo.edit', $insumo->id) }}" class="tooltipped" data-tooltip="Añadir">
                  <i class="material-icons text-green">add_shopping_cart</i>
                </a>
                <a href="{{ route('backoffice.insumo.edit', $insumo->id) }}" class="tooltipped" data-tooltip="Editar">
                  <i class="material-icons">mode_edit</i>
                </a>
                <a href="#!" class="tooltipped" data-tooltip="Eliminar"
                   onclick="event.preventDefault(); document.getElementById('del-{{ $insumo->id }}').submit();"
                   style="color: purple">
                  <i class="material-icons">delete</i>
                </a>
                <form id="del-{{ $insumo->id }}" method="POST" action="{{ route('backoffice.insumo.destroy',$insumo->id) }}" style="display:none">
                  @csrf @method('DELETE')
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div>
</div>
@endsection

@section('foot')
<script>
  $(document).ready(function(){
    $('select').material_select();
    $('.tooltipped').tooltip();

    // Submit automático al cambiar sector o críticos
    $('#sector_id').on('change', function(){ $(this).closest('form').submit(); });
    $('input[name="criticos"]').on('change', function(){ $(this).closest('form').submit(); });

    // Enter en buscador dispara submit
    $('#search').on('keypress', function(e){
      if(e.which === 13){ $(this).closest('form').submit(); }
    });
  });
</script>
@endsection
