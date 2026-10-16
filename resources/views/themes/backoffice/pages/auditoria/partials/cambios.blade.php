{{-- Lista de cambios de un registro de auditoría. Requiere $auditoria. --}}
@if (! empty($auditoria->cambios))
    <ul class="auditoria-cambios">
        @foreach ($auditoria->cambios as $campo => $valores)
            @if (is_null($valores[0]))
                <li>{{ $campo }}: {{ $valores[1] }}</li>
            @elseif (is_null($valores[1]) && $auditoria->evento === \App\Auditoria::EVENTO_ELIMINO)
                <li>{{ $campo }}: {{ $valores[0] }}</li>
            @else
                <li>{{ $campo }}: {{ $valores[0] }} → {{ $valores[1] ?? '—' }}</li>
            @endif
        @endforeach
    </ul>
@endif
