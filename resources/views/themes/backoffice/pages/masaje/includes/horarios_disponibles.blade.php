<div class="collection">


    <a href="" class="collection-item active" style="flex-basis: 100%">
        <h5>Horarios Disponibles:</h5>
    </a>

    @if(is_null($horasDisponibles))
    <a class="collection-item center">No existen horarios disponibles. </a>

    @else
    <div style="display: flex; width: 100%; flex-direction:column;">
        @foreach ($horasDisponibles as $disponible)
            
        <a class="collection-item center-align valign-wrapper">
            En Proceso
        </a>
        @endforeach

    @endif
</div>

</div>