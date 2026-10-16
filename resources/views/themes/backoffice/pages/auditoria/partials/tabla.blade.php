{{-- Tabla de registros de auditoría. Requiere $auditorias (paginador) y $categorias. --}}
@php($formato = app(\App\Services\AuditoriaService::class))

<table class="responsive-table striped">
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Realizado por</th>
            <th>Evento</th>
            <th>Qué ocurrió</th>
            <th>Categoría</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($auditorias as $auditoria)
            <tr>
                <td class="auditoria-fecha">{{ $formato->fechaHora($auditoria->created_at) }}</td>
                <td>{{ $auditoria->actor }}</td>
                <td>{{ $auditoria->evento_etiqueta }}</td>
                <td>
                    {{ $auditoria->descripcion }}
                    @include('themes.backoffice.pages.auditoria.partials.cambios', ['auditoria' => $auditoria])
                </td>
                <td>
                    <span class="auditoria-chip {{ $auditoria->categoria }}">{{ $categorias[$auditoria->categoria] ?? $auditoria->categoria }}</span>
                    @if ($auditoria->archivado)
                        <span class="auditoria-chip archivado" title="Registro en el archivo histórico">Archivado</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('backoffice.auditoria.show', $auditoria) }}" title="Ver detalle">
                        <i class="material-icons">visibility</i>
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="center grey-text">No hay registros.</td>
            </tr>
        @endforelse
    </tbody>
</table>

{{ $auditorias->links('vendor.pagination.materialize') }}
