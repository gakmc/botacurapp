{{-- @if ($paginator->hasPages())
    @php
        $fechaInicio = \Carbon\Carbon::parse($paginator->items()[0]->fecha_visita ?? now());
        $fechasDisponibles = request()->has('fechas') ? explode(',', urldecode(request()->get('fechas'))) : [];
        // dd($fechasDisponibles);
    @endphp

    <ul class="pagination">

        @if ($paginator->onFirstPage())
            <li class="disabled"><a href="#!"><i class="material-icons">chevron_left</i></a></li>
        @else
            <li><a href="{{ $paginator->previousPageUrl() }}"><i class="material-icons">chevron_left</i></a></li>
        @endif


        @foreach ($elements as $element)
            @if (is_string($element))
                <li class="disabled"><a href="#!">{{ $element }}</a></li>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @php

                        $fechaPagina = $fechaInicio->copy()->addDays($page - 1)->format('d/m');
                        // $fechaPagina = $paginator->items()[1]->fecha_visita->format('d/m');
                    @endphp

                    @if ($page == $paginator->currentPage())
                        <li class="active"><a href="#!">{{ $fechaPagina }}</a></li>
                    @else
                        <li class="waves-effect"><a href="{{ $url }}">{{ $fechaPagina }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach


        @if ($paginator->hasMorePages())
            <!--<li class="waves-effect"><a href="{{ $paginator->nextPageUrl() }}"><i class="material-icons">chevron_right</i></a></li>-->
            <li class="waves-effect">
                <a href="{{ $paginator->nextPageUrl() }}&fechas={{ request()->get('fechas') }}">
                    <i class="material-icons">chevron_right</i>
                </a>
            </li>
        @else
            <li class="disabled"><a href="#!"><i class="material-icons">chevron_right</i></a></li>
        @endif
    </ul>
@endif --}}








{{-- <pre>{{ print_r($fechasDisponibles, true) }}</pre> --}}
{{-- <pre>{{ print_r(request()->get('fechasDisponibles', '[]'), true) }}</pre>--}}




@if ($paginator->hasPages())
    <ul class="pagination">
        {{-- Botón Anterior --}}
        @if ($paginator->onFirstPage())
            <li class="disabled"><a href="#!"><i class="material-icons">chevron_left</i></a></li>
        @else
            <li>
                <a href="{{ $paginator->previousPageUrl() }}">
                    <i class="material-icons">chevron_left</i>
                </a>
            </li>
        @endif

        {{-- Mostrar las fechas paginadas --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <li class="disabled"><a href="#!">{{ $element }}</a></li>
            @endif

            @if (is_array($element))
            @foreach ($paginator->items() as $fechaFormateada)
                @foreach ($element as $page => $url)

                        @if ($page == $paginator->currentPage())
                            <li class="active"><a href="#!">{{ $fechaFormateada }}</a></li>
                        @else
                            <li class="waves-effect"><a href="{{ $url }}">{{ $fechaFormateada }}</a></li>
                        @endif

                    @endforeach
                @endforeach
            @endif
         @endforeach


        {{-- Botón Siguiente --}}
        @if ($paginator->hasMorePages())
            <li>
                <a href="{{ $paginator->nextPageUrl() }}">
                    <i class="material-icons">chevron_right</i>
                </a>
            </li>
        @else
            <li class="disabled"><a href="#!"><i class="material-icons">chevron_right</i></a></li>
        @endif
    </ul>
@endif


