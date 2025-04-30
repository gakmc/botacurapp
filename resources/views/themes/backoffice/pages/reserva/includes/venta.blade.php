<div class="collection">


    <a href="" class="collection-item active 
        @if (is_null($reserva->venta))
            ''
        @else 
            {{($reserva->venta->total_pagar === 0) ? 'green' : ''}}
        @endif ">

        <h5>Venta: {{(is_null($reserva->venta)) ? '' : ($reserva->venta->total_pagar === 0) ? 'Pagado' : '' }} </h5>
    </a>

    @php
        $asignar = false;
        foreach ($asignados as $index => $asignado) {
            $fecha = \Carbon\Carbon::parse($asignado->fecha)->format('d-m-Y');
            if ($fecha === $reserva->fecha_visita) {
                $asignar = true;
            }
        }
    @endphp

    @if(is_null($reserva->venta))
        <a class="collection-item center">Esta reserva no posee venta. </a>
    @else
        @php
            $consumo = $reserva->venta->consumo;
        @endphp
        <a class="collection-item center-align valign-wrapper left">
            Abono: {{$reserva->venta->abono_programa}}
        </a>
        <a class="collection-item center-align valign-wrapper left">
            Diferencia: {{(is_null($reserva->venta->diferencia_programa)) ? 'Debe Realizar pago' : $reserva->venta->diferencia_programa}}
        </a>

        <a href="#modalVenta{{--$reserva->venta->id--}}"
            class="collection-item center-align valign-wrapper left modal-trigger" 
            data-id="{{ $reserva->venta->id }}"

            data-diferencia="{{ $reserva->venta->diferencia_programa }}"

            data-totalpagar="{{$reserva->venta->total_pagar}}"


            data-consumo="{{$consumo}}" 
            
                {{-- @if (!is_null($consumo) && !is_null($consumo->pagosConsumos) && $consumo->pagosConsumos->where('id_consumo', $consumo->id)->isNotEmpty())
                    data-pagoimg="{{$reserva->venta->consumo->pagosConsumos ? route('backoffice.reserva.consumo.imagen', $reserva->id) : null}}"
                @endif --}}

            >
            <i class='material-icons tooltipped' data-position="bottom" data-tooltip="Ver Venta">remove_red_eye</i>
        </a>

        @if (is_null($reserva->venta->diferencia_programa))

            <a href="{{ $asignar ? route('backoffice.reserva.venta.cerrar', ['reserva' => $reserva, 'ventum' => $reserva->venta]) : 'javascript:void(0)' }}" class="collection-item center-align valign-wrapper left {{ !$asignar ? 'btn-alerta' : '' }}">
                <i class='material-icons tooltipped' data-position="bottom" data-tooltip="Cerrar Venta">attach_money</i>
            </a>
    

        @else

            <a class="collection-item center-align valign-wrapper left" href="{{ route('backoffice.venta.pdf', $reserva) }}"
                target="_blank">
                <i class="material-icons tooltipped" data-position="bottom" data-tooltip="PDF Venta">picture_as_pdf</i>
            </a>

            @if (is_null($consumo))
                {{-- @foreach ($reserva->venta->consumos as $consumo) --}}
                    @if (!is_null($consumo) && !is_null($consumo->pagosConsumos) && $consumo->pagosConsumos->where('id_consumo', $consumo->id)->isNotEmpty())
                        <a href="{{ route('backoffice.consumo.pdf', $reserva) }}" target="_blank"
                        class="collection-item center-align valign-wrapper left">
                        <i class="material-icons tooltipped" data-position="bottom" data-tooltip="PDF Consumo">local_bar</i></a>
                    @endif
                {{-- @endforeach --}}
            @endif

        @endif

    @endif

</div>

<script>
    const rutaAsignacion = "{{ route('backoffice.asignacion.create') . '?' .\Carbon\Carbon::parse($reserva->fecha_visita)->format('Y-m-d') }}";
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Detectar clic en enlaces con clase btn-alerta
        document.querySelectorAll('.btn-alerta').forEach(function(el) {
            el.addEventListener('click', function(e) {
                e.preventDefault(); // Evita navegación
                Swal.fire({
                    icon: 'warning',
                    title: 'No disponible',
                    text: 'Esta reserva aún no tiene un equipo asignado.',
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    confirmButtonText: 'Asignar',
                    cancelButtonText: 'Entendido',
                }).then((result)=>{
                    if (result.isConfirmed) {
                        window.location.href = rutaAsignacion;
                    }
                });
            });
        });
    });
</script>