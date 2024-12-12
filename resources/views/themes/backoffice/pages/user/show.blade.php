@extends('themes.backoffice.layouts.admin')

@section('title', $user->name)

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.user.index')}}">Usuarios del Sistema</a></li>
<li>{{$user->name}}</li>
@endsection

@section('dropdown_settings')
<li><a href="{{route('backoffice.user.edit',$user)}}" class="grey-text text-darken-2">Editar Usuario</a></li>
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Usuario:</strong> {{$user->name}}</p>
    <div class="divider"></div>
        <div id="basic-form" class="section">
            <div class="row">
                <div class="col s12 m8">
                    <div class="card">
                            <div class="card-content">
                                <h4 class="card-title">{{$user->name}}</h4>
                                <p><strong>Edad: </strong>{{$user->age()}}</p>
                                <h5>Roles: </h5>
                                <ul>
                                    @foreach($user->roles as $role)
                                    <li><i class='material-icons'>check</i>{{$role->name}}</li>
                                    @foreach ($role->permissions as $permission)
                                        <ul>
                                            <li>{{$permission->name}}</li>
                                        </ul>
                                    @endforeach
                                    @endforeach
                                </ul>
                            </div>
                                <div class="card-action">
                                    <a href="{{route('backoffice.user.edit', $user) }}">Editar</a>
                                    <a href="#" style="color: red" onclick="enviar_formulario()">Eliminar</a>
                                </div>
                    </div>
                </div>

            
                        <div class="col s12 m4">
                        @include('themes.backoffice.pages.user.includes.user_nav')
                        </div>

        </div>
        </div>
    </div>
</div>

<form method="post" action="{{route('backoffice.user.destroy', $user) }} " name="delete_form">
{{csrf_field()}}
{{method_field('DELETE')}}
</form>
@endsection

@section('foot')
<script>
 function enviar_formulario()
 {
     Swal.fire({
         title: "¿Deseas eliminar este usuario?",
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
@endsection