<div id="modalLugaresDisponible" class="modal modal-fixed-footer">
    <div class="modal-content">
        <br><br>
        <div class="center-align">
            <h5 class="text-darken-1">
                Lugares Disponibles Masajes
                <i class="material-icons center pink-text">beach_access</i>
            </h5>
        </div>

        <div class="divider" style="margin: 10px 0 20px;"></div>

        <div class="row" style="margin-bottom: 0;">
            <div class="col s12">
                <div class="container">
                    <div class="row" style="margin-top: 0; margin-bottom: 15px;">
                        @foreach ($lugaresDisponibles as $lugar => $lugares)
                            <div class="col s12 m4 l3"> {{-- 4 columnas en desktop, 3 en large --}}
                                <div class="chip white black-text hoverable z-depth-1 lighten-5 text-darken-2" style="margin: 5px; font-size: 16px;">
                                    <i class="material-icons left teal-text text-darken-1" style="font-size: 18px;">location_on</i>
                                    {{ $lugares['nombre'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>        

    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-light btn teal lighten-1 white-text">
            <i class="material-icons left">close</i>Cerrar
        </a>
    </div>
</div>
