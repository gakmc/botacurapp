@extends('themes.backoffice.layouts.admin')

@section('title','Detalle de auditoría')

@section('head')
@include('themes.backoffice.pages.auditoria.partials.estilos')
@endsection

@section('breadcrumbs')
<li><a href="{{ route('backoffice.auditoria.index') }}">Auditoría</a></li>
<li>Registro #{{ $auditoria->id }}</li>
@endsection

@section('content')
@php($formato = app(\App\Services\AuditoriaService::class))

<div class="section">
    <p class="caption"><strong>Registro de auditoría #{{ $auditoria->id }}</strong></p>
    <div class="divider"></div>
    <div class="section">
        <div class="row">
            <div class="col s12 l8">
                <div class="card-panel">
                    <p class="auditoria-frase">{{ $auditoria->frase }}</p>

                    @if (! empty($auditoria->cambios))
                        <h6><strong>Detalle</strong></h6>
                        <table class="striped">
                            <thead>
                                <tr><th>Campo</th><th>Antes</th><th>Después</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($auditoria->cambios as $campo => $valores)
                                    <tr>
                                        <td>{{ $campo }}</td>
                                        <td>{{ $valores[0] ?? '—' }}</td>
                                        <td>{{ $valores[1] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                @if ($relacionados->isNotEmpty())
                    <div class="card-panel">
                        <h6><strong>Otras acciones de la misma operación</strong></h6>
                        <p class="grey-text">Registradas en la misma petición o ejecución.</p>
                        <ul class="collection">
                            @foreach ($relacionados as $relacionado)
                                <li class="collection-item">
                                    <a href="{{ route('backoffice.auditoria.show', $relacionado) }}">{{ $relacionado->evento_etiqueta }}</a>:
                                    {{ $relacionado->descripcion }}
                                    @include('themes.backoffice.pages.auditoria.partials.cambios', ['auditoria' => $relacionado])
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div class="col s12 l4">
                <div class="card-panel">
                    <dl class="auditoria-ficha">
                        <dt>Fecha</dt>
                        <dd>{{ $formato->fechaHora($auditoria->created_at) }}</dd>

                        <dt>Realizado por</dt>
                        <dd>{{ $auditoria->actor }}</dd>

                        <dt>Origen</dt>
                        <dd>{{ $origenes[$auditoria->actor_tipo] ?? $auditoria->actor_tipo }}@if($auditoria->actor_detalle && $auditoria->user_nombre) ({{ $auditoria->actor_detalle }})@endif</dd>

                        <dt>Evento</dt>
                        <dd>{{ $auditoria->evento_etiqueta }}</dd>

                        <dt>Categoría</dt>
                        <dd>
                            <span class="auditoria-chip {{ $auditoria->categoria }}">{{ $categorias[$auditoria->categoria] ?? $auditoria->categoria }}</span>
                            @if ($auditoria instanceof \App\AuditoriaArchivo)
                                <span class="auditoria-chip archivado">Archivado el {{ $formato->fechaHora($auditoria->archivado_at) }}</span>
                            @endif
                        </dd>

                        @if ($afectado)
                            <dt>Registro afectado</dt>
                            <dd>
                                @if ($afectado['url'])
                                    <a href="{{ $afectado['url'] }}">{{ $afectado['texto'] }}</a>
                                @else
                                    {{ $afectado['texto'] }}
                                @endif
                            </dd>
                        @endif

                        @if ($contexto && $contexto != $afectado)
                            <dt>Pertenece a</dt>
                            <dd>
                                @if ($contexto['url'])
                                    <a href="{{ $contexto['url'] }}">{{ $contexto['texto'] }}</a>
                                @else
                                    {{ $contexto['texto'] }}
                                @endif
                            </dd>
                        @endif

                        <dt>IP</dt>
                        <dd>{{ $auditoria->ip ?: '—' }}</dd>

                        @if ($auditoria->ruta_nombre)
                            <dt>Ruta</dt>
                            <dd><code>{{ $auditoria->ruta_nombre }}</code></dd>
                        @endif
                    </dl>
                </div>

                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('backoffice.auditoria.index') }}" class="btn-flat">
                    <i class="material-icons left">arrow_back</i>Volver
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('foot')
@endsection
