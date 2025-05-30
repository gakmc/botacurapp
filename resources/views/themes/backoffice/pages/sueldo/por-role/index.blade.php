@extends('themes.backoffice.layouts.admin')

@section('title', 'Sueldos por Rol')

@section('dropdown_settings')
 <li class="collection-item active"><a href="{{ route('backoffice.rango-sueldos.create') }}" class="grey-text text-darken-2">Asignar Rango al rol</a></li>
@endsection

@section('content')
<div class="section">
    <h5>Rangos de Sueldo por Rol</h5>
   
    <table class="striped">
        <thead>
            <tr>
                <th>Rol</th>
                <th>Salario</th>
                <th>Desde</th>
                <th>Hasta</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rangos as $rango)
            <tr>
                <td>{{ $rango->role->nombre }}</td>
                <td>${{ number_format($rango->salario_base, 0, ',', '.') }}</td>
                <td>{{ $rango->vigente_desde }}</td>
                <td>{{ $rango->vigente_hasta ?? 'Vigente' }}</td>
                <td>
                    <a href="{{ route('rango-sueldos.edit', $rango) }}" class="btn-flat">
                        <i class="material-icons">edit</i>
                    </a>
                    <form action="{{ route('rango-sueldos.destroy', $rango) }}" method="POST" style="display:inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-flat red-text">
                            <i class="material-icons">delete</i>
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
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