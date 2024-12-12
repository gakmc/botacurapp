@extends('themes.backoffice.layouts.admin')

@section('title',$permission->name)

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.role.index')}}">Roles del Sistema</a></li>
<li><a href="{{route('backoffice.role.show', $permission->role )}}">Rol: {{$permission->role->name}}</a></li>
<li>{{ $permission->name}}</li>
@endsection


@section('content')

<div class="section">
              <p class="caption"><strong>Permiso: </strong>{{$permission ->name}}</p>
              <div class="divider"></div>
              <div id="basic-form" class="section">
                <div class="row">
                  <div class="col s12 m8 offset-m2 ">
                    <div class="card">
                      <div class="card-content">
                        <span class="card-title">Usuarios con el permiso de {{$permission->name}}</span>
                          <p><strong>Slug: </strong>{{$permission->slug}}</p>
                          <p><strong>Descripción: </strong>{{$permission->description}}</p>
                      </div>
                      <div class="card-action">
                        <a href="{{ route('backoffice.permission.edit', $permission) }}">Editar</a>
                        <a href="#" style="color: red" onclick="enviar_formulario()">Eliminar</a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
</div>

<form method="post" action="{{ route('backoffice.permission.destroy', $permission) }}" name="delete_form">
  {{ csrf_field() }}
  {{ method_field('DELETE') }}



</form>
@endsection
 <script>
  function enviar_formulario()
  {
    Swal.fire({
         title: "¿Deseas eliminar este permiso?",
         text: "Esta acción no se puede deshacer",
         type: "warning",
         showCancelButton: true,
         confirmButtonText: "Si, continuar",
         cancelButtonText: "No, cancelar",
         closeOnCancel: false,
         closeOnConfirm: true
     }).then((result)=> {
         if(result.value){
             document.delete_form.submit();
         }else{
             Swal.fire(
                 'Operación Cancelada',
                 'Registro no eliminado',
                 'error'
             )
         }
     });
 }

 </script>

@section('foot')


@endsection


