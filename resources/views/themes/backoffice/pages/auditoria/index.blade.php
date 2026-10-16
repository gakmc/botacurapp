@extends('themes.backoffice.layouts.admin')

@section('title','Auditoría')

@section('head')
@include('themes.backoffice.pages.auditoria.partials.estilos')
@endsection

@section('breadcrumbs')
<li><a href="{{ route('backoffice.auditoria.index') }}">Auditoría</a></li>
@endsection

@section('dropdown_settings')
@can('gestionar-auditoria')
<li><a href="{{ route('backoffice.auditoria.autorizados') }}" class="grey-text text-darken-2">Gestionar accesos</a></li>
@endcan
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Auditoría del sistema</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12">
                <div class="card-panel">

                    <form method="GET" action="{{ route('backoffice.auditoria.index') }}" class="row auditoria-filtros">
                        <div class="col s12 m6 l3">
                            <label for="user_id">Usuario</label>
                            <select name="user_id" id="user_id" class="browser-default">
                                <option value="">Todos</option>
                                @foreach ($usuarios as $usuario)
                                    <option value="{{ $usuario->id }}" @if(request('user_id') == $usuario->id) selected @endif>{{ $usuario->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col s12 m6 l3">
                            <label for="actor_tipo">Origen</label>
                            <select name="actor_tipo" id="actor_tipo" class="browser-default">
                                <option value="">Todos</option>
                                @foreach ($origenes as $valor => $etiqueta)
                                    <option value="{{ $valor }}" @if(request('actor_tipo') === $valor) selected @endif>{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col s12 m6 l3">
                            <label for="categoria">Categoría</label>
                            <select name="categoria" id="categoria" class="browser-default">
                                <option value="">Todas</option>
                                @foreach ($categorias as $valor => $etiqueta)
                                    <option value="{{ $valor }}" @if(request('categoria') === $valor) selected @endif>{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col s12 m6 l3">
                            <label for="evento">Evento</label>
                            <select name="evento" id="evento" class="browser-default">
                                <option value="">Todos</option>
                                @foreach ($eventos as $valor => $etiqueta)
                                    <option value="{{ $valor }}" @if(request('evento') === $valor) selected @endif>{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col s6 m3 l2">
                            <label for="desde">Desde</label>
                            <input type="date" name="desde" id="desde" value="{{ request('desde') }}">
                        </div>
                        <div class="col s6 m3 l2">
                            <label for="hasta">Hasta</label>
                            <input type="date" name="hasta" id="hasta" value="{{ request('hasta') }}">
                        </div>
                        <div class="col s12 m6 l4">
                            <label for="q">Buscar en la descripción</label>
                            <input type="text" name="q" id="q" value="{{ request('q') }}" placeholder="Ej: reserva #512, Juan Pérez">
                        </div>
                        <div class="col s12 m6 l2" style="padding-top: 24px;">
                            <label>
                                <input type="checkbox" name="incluir_archivo" value="1" class="filled-in" @if(request()->boolean('incluir_archivo')) checked @endif>
                                <span title="Incluye registros antiguos ya movidos al archivo histórico">Incluir archivo</span>
                            </label>
                        </div>
                        <div class="col s12 l2" style="padding-top: 18px;">
                            <button type="submit" class="btn waves-effect waves-light">Filtrar</button>
                            <a href="{{ route('backoffice.auditoria.index') }}" class="btn-flat">Limpiar</a>
                        </div>
                    </form>

                    @if ($errors->any())
                        <p class="red-text">{{ $errors->first() }}</p>
                    @endif

                    <div class="divider"></div>

                    <p class="grey-text">{{ $auditorias->total() }} registro(s)</p>

                    @include('themes.backoffice.pages.auditoria.partials.tabla')

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('foot')
@endsection
