{{--
    Selector de fotos (Cámara / Galería) con miniaturas. Requiere el parcial
    selector-fotos-recursos (una vez por página) y TkFotos.iniciar(este div).
    Parámetros: $prefijo (único en la página), $max, $titulo (opcional), $error (opcional).
--}}
<div class="row section tk-selector-fotos" data-max="{{ $max }}">
    <div class="col s12">
        <h6><strong>{{ $titulo ?? 'Fotos' }}</strong> <small class="grey-text">(opcional, hasta {{ $max }}) · <span class="tk-contador">0/{{ $max }}</span></small></h6>
    </div>
    <div class="col s6">
        <label class="btn waves-effect waves-light cyan darken-2 btn-block tk-btn-foto" for="{{ $prefijo }}-camara">
            <i class="material-icons left">photo_camera</i>Cámara
        </label>
    </div>
    <div class="col s6">
        <label class="btn waves-effect waves-light blue-grey lighten-1 btn-block tk-btn-foto" for="{{ $prefijo }}-galeria">
            <i class="material-icons left">photo_library</i>Galería
        </label>
    </div>
    {{-- Sin JS se envían tal cual; con JS se reducen y se mandan desde la lista --}}
    <input type="file" id="{{ $prefijo }}-camara" name="fotos[]" accept="image/*" capture="environment" class="hide">
    <input type="file" id="{{ $prefijo }}-galeria" name="fotos[]" accept="image/*" multiple class="hide">
    <div class="col s12">
        <span class="red-text text-darken-2 tk-error" data-error="fotos">{{ $error ?? '' }}</span>
    </div>
    <div class="col s12">
        <div class="row tk-previas"></div>
    </div>
</div>
