<div class="collection">

   {{-- <a href="#!" class="collection-item active">Alvin</a> --}} 
   <a href="{{route('backoffice.user.show', $user)}}"  class="collection-item active" >{{$user->name}}</a>
   <a href="{{route('backoffice.user.assign_role', $user)}}" class="collection-item">Asignar Roles</a> 
   <a href="{{route('backoffice.user.assign_permission', $user)}}" class="collection-item">Asignar Permisos</a>
   <a href="{{route('backoffice.datos-bancarios.edit', $user)}}" class="collection-item">Datos Bancarios</a>


</div>