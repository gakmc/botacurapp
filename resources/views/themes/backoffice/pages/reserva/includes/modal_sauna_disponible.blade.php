<div id="modalSaunaDisponible" class="modal modal-fixed-footer">
    <div class="modal-content">
        <h5 class="center-align">Horarios Disponibles SPA<i class="material-icons center teal-text">spa</i></h5>
        <div class="divider" style="margin: 10px 0 20px;"></div>

        <div class="row" style="margin-bottom: 0;">
            <div class="col s12">
                <div class="">
                    @foreach($horariosDisponibles as $horario)
                        <div class="chip white black-text z-depth-1 hoverable" style="margin: 5px; font-size: 16px;">
                            <i class="material-icons left teal-text">access_time</i>
                            {{ $horario }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <br>
        <br>

        <div class="center-align">
            <h5 class="text-darken-1">
                Horarios Disponibles Masajes
                <i class="material-icons center pink-text">airline_seat_flat</i>
            </h5>
        </div>
        
        <div class="divider" style="margin: 10px 0 20px;"></div>
        
        <div class="row" style="margin-bottom: 0;">
            <div class="col s12">
                <div class="container">
                    @foreach ($horariosDisponiblesMasajes as $lugar => $horarios)
                        <div class="row">
                            <div class="col s12">
                                <span class="collection-item grey lighten-3 text-darken-2" 
                                      style="display: inline-block; font-weight: bold; margin-bottom: 10px;">
                                    {{ $lugar == 1 ? 'Container: ' : 'Toldos: ' }}
                                </span>
        
                                <div class="row" style="margin-top: 0; margin-bottom: 15px;">
                                    <div class="col s12">
                                        <div class="">
                                            @foreach ($horarios as $hora)
                                                <div class="chip white black-text hoverable z-depth-1 lighten-5 text-darken-2" 
                                                style="margin: 5px; font-size: 16px;">
                                                    <i class="material-icons left teal-text text-darken-1" style="font-size: 18px;">access_time</i>
                                                    {{ $hora }}
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
        
                            </div>
                        </div>
                    @endforeach
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




