@extends('themes.backoffice.layouts.admin')

@section('title', 'Configuración del Bot')

@section('head')
<style>
    .bc-tabs { display:flex; border-bottom:2px solid #e0e0e0; margin-bottom:20px; flex-wrap:wrap; }
    .bc-tab-btn {
        padding:12px 20px; cursor:pointer; font-weight:600; color:#757575;
        border-bottom:3px solid transparent; background:none; border-top:none; border-left:none; border-right:none;
    }
    .bc-tab-btn.active { color:#3B82F6; border-bottom-color:#3B82F6; }
    .bc-panel { display:none; }
    .bc-panel.active { display:block; }
    .bc-card { background:#fff; border-radius:6px; box-shadow:0 1px 3px rgba(0,0,0,.15); padding:20px; margin-bottom:18px; }
    .bc-card h6 { margin-top:0; font-weight:700; color:#333; }
    .bc-card textarea { width:100%; min-height:110px; font-family:inherit; padding:8px; border:1px solid #ccc; border-radius:4px; }
    .bc-lock { color:#f57c00; font-size:13px; margin-bottom:6px; display:block; }
    .bc-masaje-cat { font-weight:700; margin:14px 0 6px; color:#3B82F6; }
    .bc-masaje-row { padding:4px 0; border-bottom:1px solid #f0f0f0; }
    .bc-banner { background:#e3f2fd; border-left:4px solid #3B82F6; padding:12px 16px; border-radius:4px; margin-bottom:16px; }
</style>
@endsection

@section('breadcrumbs')
<li>Configuración del Bot</li>
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Configuración del Bot de WhatsApp</strong></p>
    <div class="divider"></div>

    <div class="bc-tabs">
        <button type="button" class="bc-tab-btn active" data-tab="perfil">Perfil del vendedor</button>
        <button type="button" class="bc-tab-btn" data-tab="recinto">Info del recinto</button>
        <button type="button" class="bc-tab-btn" data-tab="guion">Guion del bot</button>
        <button type="button" class="bc-tab-btn" data-tab="masajes">Masajes</button>
    </div>

    <form action="{{ route('backoffice.bot-configuracion.secciones.update') }}" method="POST">
        @csrf

        {{-- PERFIL --}}
        <div class="bc-panel active" id="panel-perfil">
            @foreach ($perfil as $s)
                <div class="bc-card">
                    <h6>{{ $s->etiqueta }}</h6>
                    <textarea name="contenido[{{ $s->clave }}]">{{ old('contenido.'.$s->clave, $s->contenido) }}</textarea>
                </div>
            @endforeach
        </div>

        {{-- RECINTO --}}
        <div class="bc-panel" id="panel-recinto">
            @foreach ($recinto as $s)
                <div class="bc-card">
                    <h6>{{ $s->etiqueta }}</h6>
                    <textarea name="contenido[{{ $s->clave }}]">{{ old('contenido.'.$s->clave, $s->contenido) }}</textarea>
                </div>
            @endforeach
        </div>

        <div id="btn-guardar-secciones" style="text-align:right; margin-bottom:30px;">
            <button type="submit" class="btn waves-effect waves-light">
                Guardar cambios
                <i class="material-icons right">save</i>
            </button>
        </div>
    </form>

    {{-- GUION --}}
    <div class="bc-panel" id="panel-guion">
        <form action="{{ route('backoffice.bot-configuracion.pasos.update') }}" method="POST">
            @csrf
            @foreach ($pasos as $p)
                <div class="bc-card">
                    @if ($p->bloqueado)
                        <span class="bc-lock">🔒 Este paso tiene lógica que depende del programa elegido (se calcula en el sistema). Puedes editar el texto, pero la condición no cambia acá.</span>
                    @endif
                    <div class="row" style="margin-bottom:0;">
                        <div class="col s2 m1">
                            <strong>#{{ $p->numero_paso }}</strong>
                        </div>
                        <div class="col s7 m9">
                            <input type="text" name="titulo[{{ $p->id }}]" value="{{ old('titulo.'.$p->id, $p->titulo) }}" style="font-weight:600;">
                        </div>
                        <div class="col s3 m2 right-align">
                            <label>
                                <input type="checkbox" name="activo[]" value="{{ $p->id }}" {{ $p->activo ? 'checked' : '' }} class="filled-in" />
                                <span>Activo</span>
                            </label>
                        </div>
                    </div>
                    <textarea name="instrucciones[{{ $p->id }}]">{{ old('instrucciones.'.$p->id, $p->instrucciones) }}</textarea>
                </div>
            @endforeach
            <div style="text-align:right; margin-bottom:30px;">
                <button type="submit" class="btn waves-effect waves-light">
                    Guardar guion
                    <i class="material-icons right">save</i>
                </button>
            </div>
        </form>
    </div>

    {{-- MASAJES (solo lectura) --}}
    <div class="bc-panel" id="panel-masajes">
        <div class="bc-banner">
            Este catálogo se lee en vivo desde <strong>Masajes → Valores</strong>. No se edita acá para evitar
            tener el precio duplicado en dos lugares. Para cambiar un nombre, precio o duración,
            <a href="{{ route('backoffice.masajes.valores') }}">ve al módulo de Masajes</a>.
        </div>
        @foreach ($masajes as $categoria => $tipos)
            <div class="bc-masaje-cat">{{ $categoria }}</div>
            @foreach ($tipos as $t)
                @foreach ($t->precios as $precio)
                    <div class="bc-masaje-row">
                        {{ $t->nombre }} — {{ $precio->duracion_minutos }} min:
                        ${{ number_format($precio->precio_unitario, 0, ',', '.') }}
                        @if ($precio->precio_pareja)
                            (pareja: ${{ number_format($precio->precio_pareja, 0, ',', '.') }})
                        @endif
                    </div>
                @endforeach
            @endforeach
        @endforeach
        @if ($masajes->isEmpty())
            <p>No hay masajes activos con precio cargado. El bot no ofrecerá masajes adicionales hasta que se cargue al menos uno en el módulo de Masajes.</p>
        @endif
    </div>
</div>
@endsection

@section('foot')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var buttons = document.querySelectorAll('.bc-tab-btn');
    var panels = {
        perfil: document.getElementById('panel-perfil'),
        recinto: document.getElementById('panel-recinto'),
        guion: document.getElementById('panel-guion'),
        masajes: document.getElementById('panel-masajes')
    };
    var saveBtn = document.getElementById('btn-guardar-secciones');

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            buttons.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            Object.keys(panels).forEach(function (key) {
                panels[key].classList.toggle('active', key === btn.dataset.tab);
            });
            saveBtn.style.display = (btn.dataset.tab === 'perfil' || btn.dataset.tab === 'recinto') ? 'block' : 'none';
        });
    });
});
</script>
@endsection
