    <table class="">
        <thead>
            <tr>
                <th>Rol</th>
                <th>Sueldo Base</th>
                <th>Desde</th>
                <th>Hasta</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rangos as $rango)
            <tr>
                <td>{{ $rango->role->name }}</td>
                <td>${{ number_format($rango->sueldo_base, 0, ',', '.') }}</td>
                <td>{{ $rango->vigente_desde->format('d-m-Y') }}</td>
                <td class="{{($rango->vigente_hasta) ? 'red-text' : 'green-text'}}">{{ $rango->vigente_hasta ? $rango->vigente_hasta->format('d-m-Y') : 'Vigente' }}</td>
                <td>
                    <a href="{{ route('backoffice.rango-sueldos.edit', $rango) }}" class="btn-flat">
                        <i class="material-icons purple-text">edit</i>
                    </a>
                    <form action="{{ route('backoffice.rango-sueldos.destroy', $rango) }}" method="POST" style="display:inline">
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