@extends('themes.backoffice.layouts.admin')

@section('title', 'Facturas por foto')

@section('content')
<div class="section">
  <p class="caption"><strong>Facturas por foto</strong></p>
  <div class="divider"></div>
  @include('themes.backoffice.pages.inventario._nav')

  <a href="{{ route('backoffice.inventario.facturas.create') }}" class="btn waves-effect" style="margin-bottom:12px"><i class="material-icons left">add_a_photo</i>Subir factura</a>

  <div class="card-panel" style="overflow-x:auto">
    @if($fotos->isEmpty())
      <p class="center grey-text">Aún no se han subido facturas por foto.</p>
    @else
    <table class="highlight" style="min-width:700px">
      <thead><tr><th>Fecha subida</th><th>Proveedor</th><th>Folio</th><th class="inv-num">Total</th><th>SII</th><th>Estado</th><th>Pedido</th></tr></thead>
      <tbody>
      @foreach($fotos as $f)
        @php $d = $f->extraccion ?: []; $v = $f->verificacion ?: []; @endphp
        <tr>
          <td><a href="{{ route('backoffice.inventario.facturas.show', $f) }}">{{ $f->created_at->format('d/m H:i') }}</a><br><span class="grey-text" style="font-size:.85rem">{{ optional($f->usuario)->name }}</span></td>
          <td>{{ $d['razon_social'] ?? '—' }}</td>
          <td>{{ $d['folio'] ?? '—' }}</td>
          <td class="inv-num">{{ isset($d['total']) ? '$' . number_format($d['total'], 0, ',', '.') : '—' }}</td>
          <td>{!! [
              'coincide' => '<span class="green-text">Coincide</span>',
              'con_diferencias' => '<span class="red-text">Con diferencias</span>',
              'no_encontrada' => '<span class="orange-text">No está aún</span>',
              'sin_datos' => '<span class="orange-text">Sin RUT/folio</span>',
            ][$v['estado'] ?? ''] ?? '—' !!}</td>
          <td>{{ ['pendiente' => 'Por revisar', 'confirmada' => 'Confirmada', 'descartada' => 'Descartada', 'error' => 'Error'][$f->estado] ?? $f->estado }}</td>
          <td>{{ $f->pedido ? '#' . $f->pedido->id : '' }}</td>
        </tr>
      @endforeach
      </tbody>
    </table>
    @endif
  </div>
</div>
@endsection
