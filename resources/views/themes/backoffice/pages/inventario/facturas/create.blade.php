@extends('themes.backoffice.layouts.admin')

@section('title', 'Subir factura')

@section('content')
<div class="section">
  <p class="caption"><strong>Subir foto de factura</strong></p>
  <div class="divider"></div>
  @include('themes.backoffice.pages.inventario._nav')

  @if($pedido)
    <div class="inv-msg" style="background:#e3f2fd;color:#0d47a1">Factura del pedido #{{ $pedido->id }} · {{ $pedido->nombreProveedor() }}</div>
  @endif

  <p style="max-width:70ch;color:#546e7a">
    Saca una foto a la factura impresa, derecha y con buena luz, que se lean el RUT, el folio, los productos y los totales.
    Si es larga, sube varias fotos (máximo 8). La app la lee, la busca en el registro de compras del SII y te muestra las diferencias antes de guardar.
  </p>

  <form method="POST" action="{{ route('backoffice.inventario.facturas.store') }}" enctype="multipart/form-data" id="form-foto">
    @csrf
    @if($pedido)<input type="hidden" name="pedido_id" value="{{ $pedido->id }}">@endif
    <div class="card-panel">
      <input type="file" name="archivos[]" id="archivos" accept="image/*,application/pdf" multiple required>
      <div id="previas" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px"></div>
      <p id="estado" class="grey-text" style="font-size:.88rem;margin:8px 0 0"></p>
    </div>
    <button class="btn waves-effect" type="submit" id="btn"><i class="material-icons left">document_scanner</i>Leer factura</button>
  </form>
</div>
@endsection

@section('foot')
<script>
(function () {
  // Las fotos de celular pesan varios MB y el servidor acepta 2 MB por archivo:
  // se reducen en el navegador a 2000 px en JPEG antes de enviarlas.
  var input = document.getElementById('archivos'), previas = document.getElementById('previas'),
      estado = document.getElementById('estado'), form = document.getElementById('form-foto'), btn = document.getElementById('btn');

  function reducir(file) {
    return new Promise(function (resolve) {
      if (!/^image\//.test(file.type)) return resolve(file);
      var img = new Image(), url = URL.createObjectURL(file);
      img.onload = function () {
        var f = Math.min(1, 2000 / Math.max(img.width, img.height));
        var c = document.createElement('canvas');
        c.width = Math.round(img.width * f); c.height = Math.round(img.height * f);
        c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
        URL.revokeObjectURL(url);
        c.toBlob(function (b) { resolve(b ? new File([b], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' }) : file); }, 'image/jpeg', 0.85);
      };
      img.onerror = function () { resolve(file); };
      img.src = url;
    });
  }

  input.addEventListener('change', function () {
    previas.innerHTML = '';
    Array.prototype.forEach.call(input.files, function (f) {
      if (!/^image\//.test(f.type)) { var s = document.createElement('span'); s.textContent = f.name; previas.appendChild(s); return; }
      var im = document.createElement('img'); im.src = URL.createObjectURL(f); im.style.cssText = 'height:110px;border-radius:4px;border:1px solid #ddd';
      previas.appendChild(im);
    });
  });

  var listo = false;
  form.addEventListener('submit', function (e) {
    if (listo) return;
    e.preventDefault();
    btn.disabled = true; estado.textContent = 'Preparando fotos…';
    Promise.all(Array.prototype.map.call(input.files, reducir)).then(function (files) {
      try {
        var dt = new DataTransfer(); files.forEach(function (f) { dt.items.add(f); }); input.files = dt.files;
      } catch (err) { /* navegador antiguo: se envían tal cual */ }
      estado.textContent = 'Leyendo la factura… puede tardar hasta un minuto.';
      listo = true; form.submit();
    });
  });
})();
</script>
@endsection
