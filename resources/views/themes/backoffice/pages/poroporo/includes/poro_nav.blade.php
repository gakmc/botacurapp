<div class="collection">

   {{-- <a href="#!" class="collection-item active">Alvin</a> --}} 
   <a @if (Auth::user()->has_role(config('app.admin_role'))) href="{{route('backoffice.poroporo.index')}}" @endif class="collection-item active" >Productos Poro Poro</a>
   {{-- <a href="{{route('backoffice.user.assign_role', $user)}}" class="collection-item">Asignar Roles</a> 
    <a href="!#" class="collection-item">Lista de Productos</a> --}}

   @if ($poroProductos->isNotEmpty())
       @foreach ($poroProductos as $poro)
            <a class="collection-item">{{$poro->nombre}} - ${{number_format($poro->valor,0,'','.')}}</a>
       @endforeach
   @else
       <a class="collection-item">No existen productos registrados</a>
   @endif
   

</div>