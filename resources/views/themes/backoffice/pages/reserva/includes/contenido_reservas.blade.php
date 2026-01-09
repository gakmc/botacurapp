{{-- Vista Alternativa --}}
@if ($alternativeView)

    @foreach($reservasPaginadas as $fecha => $reservas)
        @include('themes.backoffice.pages.reserva.screens.alternative')
    @endforeach

    <div class="center-align">
        {{ $reservasPaginadas->appends(['alternative' => 1])->links('vendor.pagination.materialize') }}
    </div>

@else

    @foreach ($reservasMovilesPaginadas as $fecha => $reservas)
        @include('themes.backoffice.pages.reserva.screens.mobile', $reservas)
    @endforeach

    @foreach($reservasPaginadas as $fecha => $reservas)
        @include('themes.backoffice.pages.reserva.screens.principal', ['reservasPaginadas'=>$reservasPaginadas])
    @endforeach

    @if ($mobileView === 'masajes')
        <div class="center-align">
            {{ $reservasPaginadas->appends(['mobileview' => 'masajes'])->links('vendor.pagination.materialize') }}
        </div>
    @elseif ($mobileView === 'ubicacion')
        <div class="center-align">
            {{ $reservasPaginadas->appends(['mobileview' => 'ubicacion'])->links('vendor.pagination.materialize') }}
        </div>
    @else
        <div class="center-align">
            {{ $reservasPaginadas->links('vendor.pagination.materialize') }}
        </div>
    @endif

@endif
