@extends('themes.backoffice.layouts.admin')

@section('title', 'Sueldos por Usuarios')

@section('dropdown_settings')
    <li><a href="{{ route('backoffice.rango-sueldos.index') }}" class="grey-text text-darken-2">Rango sueldo por rol</a></li>
@endsection

{{-- @section('content')
    <div class="section">
        <h5>Sueldos Personalizados</h5>

    <table class="striped">
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Sueldo Base Rol</th>
                <th>Sueldo Personalizado</th>
                <th>Motivo</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($usuarios as $usuario)
                <tr>
                    <td>{{ $usuario->name }}</td>
                    <td>{{ $usuario->roles->pluck('name')->join(', ') }}</td>
                    <td>${{ number_format($usuario->salario, 0, ',', '.') }}</td>
                    <td>
                        @if($usuario->anularSueldo)
                            ${{ number_format($usuario->anularSueldo->salario, 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $usuario->anularSueldo->motivo ?? '-' }}</td>
                    <td>
                        @if($usuario->anularSueldo)
                            <a href="{{ route('backoffice.usuario-sueldo.edit', $usuario->anularSueldo) }}" class="btn-flat">
                                <i class="material-icons">edit</i>
                            </a>
                            <form action="{{ route('backoffice.usuario-sueldo.destroy', $usuario->anularSueldo) }}" method="POST" style="display:inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-flat red-text">
                                    <i class="material-icons">delete</i>
                                </button>
                            </form>
                        @else
                            <a href="{{ route('backoffice.usuario-sueldo.create', ['usuario' => $usuario->id]) }}" class="btn-flat">
                                <i class="material-icons">local_atm</i>
                            </a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>
@endsection --}}

@section('content')
<div class="section">
    <h5>Sueldos por Usuario</h5>
    <div class="divider"></div>

    <table class="striped responsive-table">
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Rol(es)</th>
                <th>Sueldo por Rol</th>
                <th>Sobrescrito</th>
                <th>Sueldo Vigente</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($usuarios as $usuario)
            <tr>
                <td>{{ $usuario->name }}</td>
                <td>
                    @foreach($usuario->roles as $rol)
                        <span class="chip">{{ $rol->name }}</span>
                    @endforeach
                </td>
                <td>
                    ${{ number_format(optional($usuario->roles->flatMap->rangoSueldo)
                        ->filter(function ($r) {
                            $hoy = \Carbon\Carbon::now();
                            return $r->vigente_desde <= $hoy && (is_null($r->vigente_hasta) || $r->vigente_hasta >= $hoy);
                        })
                        ->sortByDesc('vigente_desde')
                        ->first()
                        ->sueldo_base ?? 0, 0, ',', '.') }}
                </td>
                <td>
                    @if($usuario->anularSueldo)
                        ${{ number_format($usuario->anularSueldo->salario, 0, ',', '.') }}
                        <br>
                        <small class="grey-text">{{ $usuario->anularSueldo->motivo }}</small>
                    @else
                        <span class="grey-text">No definido</span>
                    @endif
                </td>
                <td class="green-text">
                    ${{ number_format($usuario->salario, 0, ',', '.') }}
                </td>
                <td>
                    @if($usuario->anularSueldo)
                        <a href="{{ route('backoffice.usuario-sueldo.edit', $usuario->anularSueldo) }}" class="btn-flat">
                            <i class="material-icons purple-text">edit</i>
                        </a>
                        <form method="POST" action="{{ route('backoffice.usuario-sueldo.destroy', $usuario->anularSueldo) }}" style="display:inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-flat red-text">
                                <i class="material-icons">delete</i>
                            </button>
                        </form>
                    @else
                        <a href="{{ route('backoffice.usuario-sueldo.create') }}?user_id={{ $usuario->id }}" class="btn-flat green-text">
                            <i class="material-icons">add</i>
                        </a>
                    @endif
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