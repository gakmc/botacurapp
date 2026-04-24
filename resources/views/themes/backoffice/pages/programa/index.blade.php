@extends('themes.backoffice.layouts.admin')

@section('title','Programas Botacura')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.programa.index')}}">Programas Activos</a></li>
@endsection


@section('dropdown_settings')
<li><a href="{{route ('backoffice.programa.create') }}" class="grey-text text-darken-2">Crear Programa</a></li> 
@endsection

@section('content')
<div class="section">
      <div class="row right">
        <div class="col s12">
            <a href="{{ route('backoffice.programa.index') }}"
            class="btn {{ request()->routeIs('backoffice.programa.index') ? 'pink-text text-darken-2' : '' }}" style="background-color: #039B7B">
            Activos
            </a>
            <a href="{{ route('backoffice.programa.inactivos') }}"
            class="btn {{ request()->routeIs('backoffice.programa.inactivos') ? 'pink-text text-darken-2' : '' }}" style="background-color: #039B7B">
            Inactivos
            </a>
        </div>
    </div>
              <p class="caption"><strong>Programas de Temporada</strong></p>
              <div class="divider"></div>
              <div id="basic-form" class="section">
                <div class="row">
                  <div class="col s12 ">
                    <div class="card-panel">
                     
                      <div class="row">


                      <table>
                            <thead>
                                <tr>
                                    <th>Nombre Programa	</th>
                                    <th>Valor Programa</th>
                                    <th>Descuento</th>
                                    <th>Valor Final</th>
                                    <th colspan="2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($programa as $programa)
                                <tr>
                                    <td><a href="{{route ('backoffice.programa.show',$programa)}}">{{$programa->nombre_programa}}</a></td>
                                    <td>${{number_format($programa->valor_programa + $programa->descuento,0,'','.')}}</td>
                                    <td>${{number_format($programa->descuento,0,'','.')}}</td>
                                    <td><strong>${{number_format($programa->valor_programa,0,'','.')}}</strong></td>

                                    <td><a class="btn-small cyan" href="{{ route('backoffice.programa.edit', $programa )}}"><i class="material-icons">mode_edit</i></a></td>
                                    <td>
                                      @if($programa->estado === 'activo' || $programa->estado === null)
                                        <button class="btn-small waves-effect cambiar-estado-programa tooltipped" data-position="top" data-delay="50" data-tooltip="Desactivar"
                                                data-id="{{ $programa->id }}"
                                                data-estado="inactivo"
                                                data-action="{{ route('backoffice.programa.estado', $programa) }}">
                                        <i class="material-icons">block</i>
                                        </button>
  
                                      @endif

                                    </td>
                                </tr>
                                @endforeach
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
    // CSRF para jQuery (Laravel 6)
    $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    });

    $(document).on('click', '.cambiar-estado-programa', function(e){
      e.preventDefault();

      const $btn   = $(this);
      const id     = $btn.data('id');
      const estado = $btn.data('estado');
      const url    = $btn.data('action'); // ya viene con el prefix backoffice

      $.ajax({
          url: url,
          type: 'PATCH',
          data: { estado: estado },
          success: function(res){
            // Quita la fila (si estás en vista de activos)
            $btn.closest('tr').fadeOut(250, function(){ $(this).remove(); });

            // Toast (elige uno: Materialize o SweetAlert)
            if (window.M) M.toast({ html: res.msg ?? 'Estado actualizado', classes: 'green' });
            if (window.Swal) Swal.fire({ toast:true, position:'center', icon:'success',
                title: res.msg ?? 'Estado actualizado', showConfirmButton:false, timer:2500 });
          },
          error: function(xhr){
            const msg = xhr?.responseJSON?.message || 'Error al cambiar estado';
            if (window.M) M.toast({ html: msg, classes:'red' });
            if (window.Swal) Swal.fire({ toast:true, position:'center', icon:'error',
                title: msg, showConfirmButton:false, timer:2500 });
          }
      });
    });
</script>

<script>
    @if(session('status'))
        Swal.fire({
            toast: true,
            position: '',
            icon: 'success',
            title: '{{ session('status') }}',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        });
        @endif
</script>
@endsection
