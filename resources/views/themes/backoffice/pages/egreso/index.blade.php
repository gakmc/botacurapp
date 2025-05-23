@extends('themes.backoffice.layouts.admin')

@section('title', 'Egresos')

@section('head')
@endsection


@section('breadcrumbs')
{{-- <li><a href="{{route('backoffice.cliente.index')}}">Clientes del Sistema</a></li> --}}
{{-- <li>{{$cliente->nombre_cliente}}</li> --}}
@endsection

@section('dropdown_settings')
<li><a href="{{ route('backoffice.egreso.create') }}" class="grey-text text-darken-2">Crear Egreso</a></li>
@endsection


@section('content')
<div class="section">
    <p class="caption"><strong>Egresos</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 ">
                <div class="card-panel">
                    <div class="row">



                        <table>
                            <thead>
                                <tr>
                                    <th data-field="categoria">Categoria</th>
                                    <th data-field="monto">Monto</th>
                                    <th data-field="fecha">Fecha</th>
                                    <th data-field="acciones">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>

                                @if ($egresos->isNotEmpty())

                                    @foreach ($egresos as $egreso)
                                    <tr>
                                        <td>{{ $egreso->categoria->nombre }}</td>
                                        <td>${{ number_format($egreso->monto, 0,'','.') }}</td>
                                        <td>{{ $egreso->fecha }}</td>
                                        <td>
                                            <a href="{{ route('backoffice.egreso.edit', $egreso->id) }}" class="btn-floating btn-small waves-effect waves-light blue"><i class="material-icons">edit</i></a>
                                            <a onclick="enviar_formulario('{{ route('backoffice.egreso.destroy', $egreso->id) }}')"  class="btn-floating btn-small waves-effect waves-light red"><i class="material-icons">delete</i></a>
                                        </td>
                                    </tr>
                                    @endforeach

                                @else
                                    <tr>
                                        <td colspan="5" class="center-align"><h5>No hay egresos registrados.</h5></td>
                                    </tr>

                                @endif

                            </tbody>
                        </table>


<form id="delete_form" method="post" action="">
    {{ csrf_field() }}
    {{ method_field('DELETE') }}
    <input type="hidden" name="egreso_id" id="table_egreso">
</form>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('foot')

<script>
  @if(session('info'))
    Swal.fire({
        toast: true,
        position: '',
        icon: 'info',
        title: '{{ session('info') }}',
        showConfirmButton: false,
        timer: 5000,
        timerProgressBar: true,
          didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
          }
    });
  @endif

  @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Éxito',
            text: '{{ session('success') }}',
            showConfirmButton: true,
            confirmButtonText: `Confirmar`,
            timer: 5000,
        });
  @endif
</script>

<script>
    function enviar_formulario(actionUrl) {
    const form = document.getElementById('delete_form');
    form.action = actionUrl; // Asegúrate de que este valor sea correcto

    Swal.fire({
        title: "¿Deseas eliminar este registro?",
        text: "Esta acción no se puede deshacer",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, continuar",
        cancelButtonText: "No, cancelar",
        reverseButtons: true 
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit(); 
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            Swal.fire('Operación Cancelada', 'Registro no eliminado', 'error');
        }
    });
}
</script>
@endsection