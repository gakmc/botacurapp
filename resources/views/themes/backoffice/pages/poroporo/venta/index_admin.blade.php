@extends('themes.backoffice.layouts.admin')

@section('title','Ventas Poro Poro')

@section('head')
@endsection

@section('breadcrumbs')
<li>Ventas <strong>Poro Poro</strong></li>
@endsection

@section('dropdown_settings')
<li><a href="{{ route('backoffice.ventas_poroporo.create') }}">Generar Venta</a></li>
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Ventas Poro Poro</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12">
                <div class="card-panel">
                    <h4 class="header2">Ventas mes de <strong>{{ ucfirst(\Carbon\Carbon::create()->month($mes)->locale('es')->isoFormat('MMMM')) }}</strong></h4>

                    <div class="row">
                        <div class="input-field col s12 m6 offset-m3">
                            <select name="mes_anio" id="mes_anio" onchange="cambiarMesAnio(this.value)">
                                @foreach($fechasDisponibles as $fecha)
                                    @php
                                        $value = $fecha->mes . '-' . $fecha->anio;
                                        $mesNombre = ucfirst(\Carbon\Carbon::create()->month($fecha->mes)->locale('es')->isoFormat('MMMM'));
                                    @endphp
                                    <option value="{{ $value }}" {{ $mes . '-' . $anio == $value ? 'selected' : '' }}>
                                        {{ $mesNombre }} {{ $fecha->anio }}
                                    </option>
                                @endforeach
                            </select>
                            <label for="mes_anio">Selecciona Mes y Año</label>
                        </div>
                    </div>

                    @php $totalMes = 0; @endphp

                    @forelse ($semanas as $rango => $ventasSemana)
                        <h5><strong>{{ $rango }}</strong></h5>
                        <table class="highlight centered">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th>Atendido por</th>
                                    <th>Total</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $totalSemana = 0; @endphp
                                @foreach ($ventasSemana as $venta)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($venta->fecha)->format('d-m-Y') }}</td>
                                        <td>{{ $venta->created_at->format('H:i:s') }}</td>
                                        <td>{{ $venta->user->name }}</td>
                                        <td>${{ number_format($venta->total, 0, '', '.') }}</td>
                                        <td>
                                            <a href="#modal{{ $venta->id }}" class="btn-floating btn-small blue modal-trigger">
                                                <i class="material-icons">visibility</i>
                                            </a>
                                            <a href="{{ route('backoffice.ventas_poroporo.edit', $venta) }}" class="btn-floating btn-small purple">
                                                <i class="material-icons">edit</i>
                                            </a>
                                            <a href="#" class="btn-floating btn-small red btn-eliminar-venta" data-url="{{ route('backoffice.ventas_poroporo.destroy', ['ventas_poroporo' => $venta->id]) }}">
                                                <i class="material-icons">delete</i>
                                            </a>
                                        </td>
                                    </tr>
                                    @php $totalSemana += $venta->total; @endphp

                                    @include('themes.backoffice.pages.poroporo.venta.includes.modal_venta', ['poroVenta' => $venta])
                                @endforeach
                                <tr>
                                    <td colspan="3" class="right-align"><strong>Total semana:</strong></td>
                                    <td colspan=""><strong>${{ number_format($totalSemana, 0, '', '.') }}</strong></td>

                                    @php
                                        $yaPagado = $pagosRealizados->contains(function ($pago) use ($rango,$rangosSemanas) {
                                            return $pago->semana_inicio == $rangosSemanas[$rango]['inicio']
                                                && $pago->semana_fin == $rangosSemanas[$rango]['fin'];
                                        });

                                        // dd($pagosRealizados);
                                            // Si ya fue pagado, buscamos la fecha exacta
                                        $pago = $yaPagado
                                            ? $pagosRealizados->first(function ($pago) use ($rangosSemanas, $rango) {
                                                return $pago->semana_inicio == $rangosSemanas[$rango]['inicio']
                                                    && $pago->semana_fin == $rangosSemanas[$rango]['fin'];
                                            })
                                            : null;
                                    @endphp

                        @if ($yaPagado)
                            <td>
                                <span class="tooltipped" data-position="bottom" data-delay="50" data-tooltip="Pagado el {{ \Carbon\Carbon::parse($pago->fecha_pago)->locale('es')->isoFormat('D [de] MMMM Y') }}" style="color: #039B7B;">
                                    <i class="material-icons tiny">
                                        monetization_on
                                    </i> Pagado</span>
                            </td>
                        @else


                            <td>
                                <form action="{{route("backoffice.poro-pagado.store")}}" method="POST">
                                    @csrf

                                    <input type="text" name="datos" value="{{json_encode([
                                    'monto' => $totalSemana,
                                    'inicio_semana' => $rangosSemanas[$rango]['inicio'],
                                    'fin_semana' => $rangosSemanas[$rango]['fin'],
                                    ])}}" hidden>
                                    <button class="btn"><i class="material-icons right tiny">monetization_on</i>Pagar Semana</button>
                                </form>
                            </td>


                        @endif

                                </tr>
                            </tbody>
                        </table>
                        @php $totalMes += $totalSemana; @endphp
                    @empty
                        <p class="center-align">No hay ventas registradas para este mes.</p>
                    @endforelse

                    <table>
                        <tbody>
                            <tr>
                                <td colspan="3" class="right-align"><strong>Total del mes:</strong></td>
                                <td colspan="2"><strong>${{ number_format($totalMes, 0, '', '.') }}</strong></td>
                            </tr>
                        </tbody>
                    </table>

                    <form id="form-eliminar-venta" method="POST" style="display: none;">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('foot')

<script>
    $(document).ready(function () {
        @if(session('info'))
            Swal.fire({
                toast: true,
                position: '',
                icon: 'info',
                title: '{{ session('info') }}',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                  didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                  }
            });
        @endif

        @if(session('success'))
            Swal.fire({
                toast: true,
                position: '',
                icon: 'success',
                title: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                  didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                  }
            });
        @endif
    });
</script>

<script>
    $(document).ready(function () {
        $('select').material_select();
        $('.modal').modal();

        document.querySelectorAll('.btn-eliminar-venta').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const url = this.dataset.url;

                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "Esta acción no se puede deshacer",
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#aaa',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('form-eliminar-venta');
                        form.setAttribute('action', url);
                        form.submit();
                    }
                });
            });
        });
    });

    function cambiarMesAnio(valor) {
        const [mes, anio] = valor.split('-');
        const form = document.createElement('form');
        form.method = 'GET';
        form.action = "{{ route('backoffice.ventas_poroporo.index') }}";

        const inputMes = document.createElement('input');
        inputMes.name = 'mes';
        inputMes.value = mes;
        form.appendChild(inputMes);

        const inputAnio = document.createElement('input');
        inputAnio.name = 'anio';
        inputAnio.value = anio;
        form.appendChild(inputAnio);

        document.body.appendChild(form);
        form.submit();
    }
</script>
@endsection
