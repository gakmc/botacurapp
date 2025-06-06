@extends('themes.backoffice.layouts.admin')

@section('title', 'Sueldos por Rol')

@section('dropdown_settings')
 <li><a href="{{ route('backoffice.rango-sueldos.create') }}" class="grey-text text-darken-2">Asignar Rango al rol</a></li>
 <li><a href="{{ route('backoffice.usuario-sueldo.index') }}" class="grey-text text-darken-2">Sueldos por usuarios</a></li>
@endsection

@section('content')
<div class="section">

    <h5>Rangos de Sueldo por Rol {{$titulo}}</h5>
    {{-- <div class="right-align mb-3">
        <a href="{{ route('backoffice.rango-sueldos.index', ['filtro' => 'vigentes']) }}" class="btn teal">Vigentes</a>
        <a href="{{ route('backoffice.rango-sueldos.index', ['filtro' => 'no-vigentes']) }}" class="btn red">No Vigentes</a>
        <a href="{{ route('backoffice.rango-sueldos.index', ['filtro' => 'todos']) }}" class="btn blue">Todos</a>
    </div> --}}
    <div class="right-align mb-3">
        <a href="{{ route('backoffice.rango-sueldos.index', ['filtro' => 'vigentes']) }}" class="btn teal filtro-btn" data-filtro="vigentes">Vigentes</a>
        <a href="{{ route('backoffice.rango-sueldos.index', ['filtro' => 'no-vigentes']) }}" class="btn red filtro-btn" data-filtro="no-vigentes">No Vigentes</a>
        <a href="{{ route('backoffice.rango-sueldos.index', ['filtro' => 'todos']) }}" class="btn blue filtro-btn" data-filtro="todos">Todos</a>
    </div>

   

    <div id="tabla-rangos">
        @include('themes.backoffice.pages.sueldo.por-role._tabla', ['rangos' => $rangos])
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

<script>
    $(document).ready(function () {
        $('.filtro-btn').on('click', function (e) {
            e.preventDefault();
            const filtro = $(this).data('filtro');

            $.ajax({
                url: '{{ route("backoffice.rango-sueldos.index") }}',
                type: 'GET',
                data: { filtro: filtro },
                beforeSend: function () {
                    $('#tabla-rangos').html('<p>Cargando...</p>');
                },
                success: function (data) {
                    // Extraer solo la tabla del contenido renderizado completo
                    const html = $(data).find('#tabla-rangos').html();
                    $('#tabla-rangos').html(html);
                },
                error: function () {
                    alert('Hubo un error al cargar los datos.');
                }
            });
        });
    });
</script>

@endsection