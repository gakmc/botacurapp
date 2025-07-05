@extends('themes.backoffice.layouts.admin')

@section('title', 'Egresos')

@section('head')
@endsection


@section('breadcrumbs')
<li><a href="{{route('backoffice.egreso.index')}}">Egresos Anuales</a></li>
<li>Egresos {{ucfirst(\Carbon\Carbon::create()->month($mes)->year($anio)->locale('es')->isoFormat('MMMM [de] YYYY'))}}</li>
@endsection

@section('dropdown_settings')
<li><a href="{{ route('backoffice.egreso.create') }}" class="grey-text text-darken-2">Crear Egreso</a></li>
@endsection

@section('content')
<div class="section">
    <p class="caption">Listado de Egresos <strong>{{ucfirst(\Carbon\Carbon::create()->month($mes)->year($anio)->locale('es')->isoFormat('MMMM [de] YYYY'))}}</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 ">
                <div class="card-panel">
                    <div class="row">




  <table class="striped responsive-table">
    <thead>
      <tr>
        <th>Tipo</th>
        <th>Categoría</th>
        <th>Subcategoría</th>
        <th>Proveedor</th>
        <th>Folio</th>
        <th>Fecha</th>
        <th>Neto</th>
        <th>IVA</th>
        <th>Total</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      @if ($egresos->isNotEmpty())
      @foreach ($egresos as $e)
        <tr>
          <td>{{ $e->tipo_documento->nombre ?? '-' }}</td>
          <td>{{ $e->categoria->nombre ?? '-' }}</td>
          <td>{{ $e->subcategoria->nombre ?? '-' }}</td>
          <td>{{ $e->proveedor->nombre ?? '-' }}</td>
          <td>{{ $e->folio ?? '-' }}</td>
          <td>{{ \Carbon\Carbon::parse($e->fecha)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</td>
          <td>${{ number_format($e->neto, 0, ',', '.') }}</td>
          <td>${{ number_format($e->iva, 0, ',', '.') }}</td>
          <td><strong>${{ number_format($e->total, 0, ',', '.') }}</strong></td>
          <td>
            
            <a href="{{ route('backoffice.egreso.edit', $e->id) }}" class="btn-floating btn-small purple"><i class='material-icons'>edit</i></a>
            <form action="{{ route('backoffice.egreso.destroy', $e->id) }}" method="POST" style="display:inline;">
              @csrf @method('DELETE')
              <button type="submit" class="btn-floating btn-small red" onclick="return confirm('¿Eliminar este egreso?')"><i class='material-icons'>delete</i></button>
            </form>
          </td>
        </tr>
      @endforeach

      @else
        <tr>
          <td colspan="2"></td>
          <td><h5>No se registran egresos</h5></td>
        </tr>
      @endif
    </tbody>
  </table>


                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


@section('foot')
<script>
      $(document).ready(function () {
        @if(session('info'))
            Swal.fire({
                toast: true,
                position: '',
                icon: 'info',
                title: '{{ session('info') }}',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                  didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                  }
            });
        @endif

        @if(session('success'))
            Swal.fire({
                toast: true,
                position: '',
                icon: 'success',
                title: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                  didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                  }
            });
        @endif
    });
</script>
@endsection