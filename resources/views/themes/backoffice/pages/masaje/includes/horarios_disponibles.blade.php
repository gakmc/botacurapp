<div class="collection">
    <a class="collection-item active pink white-text">
        <h5>Horarios Disponibles:</h5>
    </a>
    @if(is_null($horasDisponibles))
        <a class="collection-item center-align">No existen horarios disponibles.</a>
    @else
        <div class="container">
            @foreach ($horasDisponibles as $lugar => $horarios)
                <div class="row">
                    <div class="col s12">
                        <span class="collection-item grey lighten-3 pink-text text-darken-2" style="display: inline-block; font-weight: bold;">
                            {{ $lugar == 1 ? $lugares[0]->nombre.': ' : $lugares[1]->nombre.': ' }}
                        </span>
                        <div class="row" style="margin-top: 0;">
                            @foreach ($horarios as $hora)
                                <div class="col s3 m2 l1">
                                    <a class="collection-item center-align pink-text text-darken-1">
                                        {{ $hora }}
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

