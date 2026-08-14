@extends('themes.backoffice.layouts.admin')

@section('title','Usuarios Inactivos')

@section('head')
@endsection


@section('breadcrumbs')
<li><a href="{{route('backoffice.user.inactivos')}}">Usuarios Inactivos</a></li>
@endsection


@section('dropdown_settings')
<li><a href="{{route ('backoffice.user.create') }}" class="grey-text text-darken-2">Crear Usuario</a></li>
@endsection


@section('content')

<div class="section">
    <div class="row right">
        <div class="col s12">
            <a href="{{ route('backoffice.user.index') }}"
            class="btn {{ request()->routeIs('backoffice.user.index') ? 'pink-text text-darken-2' : '' }}" style="background-color: #039B7B">
            Activos
            </a>
            <a href="{{ route('backoffice.user.inactivos') }}"
            class="btn {{ request()->routeIs('backoffice.user.inactivos') ? 'pink-text text-darken-2' : '' }}" style="background-color: #039B7B">
            Inactivos
            </a>
        </div>
    </div>
    <p class="caption"><strong>Usuarios Inactivos</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 ">
                <div class="card-panel">

                    <div class="row">

                        @if ($users->isNotEmpty())

                        <table>
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Edad</th>
                                    <th>Correo</th>
                                    <th colspan="3">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users->sortBy('name') as $user )
                                <tr>
                                  <td><a href="{{route('backoffice.user.show' ,$user )}}">{{$user->name}}</a></td>
                                  <td>{{$user->age()}}</td>
                                  <td>{{$user->email}}</td>

                                  <td><a href="{{ route('backoffice.user.edit', $user )}}">Editar</a></td>
                                  @if (Auth::user()->has_role(config('app.admin_role')))
                                    <td><a class="tooltipped" data-position="top" data-delay="50" data-tooltip="Certificado de antiguedad" href="{{ route('backoffice.certificados.antiguedad.create', $user )}}"><i class='material-icons red-text'>library_books</i></a></td>
                                  @endif
                                  <td>
                                    <button class="btn-small waves-effect cambiar-estado-user tooltipped" data-position="top" data-delay="50" data-tooltip="Activar"
                                            data-id="{{ $user->id }}"
                                            data-action="{{ route('backoffice.user.toggle_status', $user) }}">
                                    <i class="material-icons">done_all</i>
                                    </button>
                                  </td>
                                </tr>
                                @endforeach
                              </tbody>
                            </table>

                        @else
                            <h5>No se registran usuarios inactivos</h5>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


@section('foot')
<script>

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    });

    $(document).off('click.userToggle').on('click.userToggle', '.cambiar-estado-user', function(e){
      e.preventDefault();

      const $btn = $(this);
      if ($btn.prop('disabled')) return;
      $btn.prop('disabled', true);
      const url  = $btn.data('action'); 

      $.ajax({
          url: url,
          type: 'PATCH',
          success: function(res){
            $btn.closest('tr').fadeOut(250, function(){ $(this).remove(); });

            if (window.M) M.toast({ html: res.msg ?? 'Estado actualizado', classes: 'green' });
            if (window.Swal) Swal.fire({ toast:true, position:'center', icon:'success',
                title: res.msg ?? 'Estado actualizado', showConfirmButton:false, timer:2500 });
          },
          error: function(xhr){
            $btn.prop('disabled', false);
            const msg = xhr?.responseJSON?.message || 'Error al cambiar estado';
            if (window.M) M.toast({ html: msg, classes:'red' });
            if (window.Swal) Swal.fire({ toast:true, position:'center', icon:'error',
                title: msg, showConfirmButton:false, timer:2500 });
          }
      });
    });
</script>

<script>
    $(document).ready(function(){
        $('.tooltipped').tooltip({delay: 50});
    });
</script>
@endsection
