{{-- Enlace al historial de auditoría de un registro (menú "Acciones"). Requiere $tipo y $id. --}}
@can('ver-auditoria')
<li><a href="{{ route('backoffice.auditoria.historial', [$tipo, $id]) }}" class="grey-text text-darken-2"><i class="material-icons left">history</i>Historial de auditoría</a></li>
@if (! empty($accionesDeUsuario))
<li><a href="{{ route('backoffice.auditoria.index', ['user_id' => $id]) }}" class="grey-text text-darken-2"><i class="material-icons left">search</i>Acciones que realizó</a></li>
@endif
@endcan
