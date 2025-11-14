@extends('themes.backoffice.layouts.admin')

@section('title','Remuneraciones')

@section('head')
@endsection

@section('breadcrumbs')
{{-- <li><a href="{{route('backoffice.admin.ingresos')}}">Ingresos detallados</a></li> --}}
<li>Remuneraciones <strong>{{--$fecha->locale('es')->isoFormat('DD [de] MMMM [de] YYYY')--}}</strong></li>
@endsection


@section('dropdown_settings')
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Remuneraciones</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12">
                <div class="card-panel">
                    <h4 class="header2">Remuneraciones mes de <strong>{{ ucfirst(\Carbon\Carbon::create()->month($mes)->locale('es')->isoFormat('MMMM')) }}</strong></h4>

                    {{-- Formulario para seleccionar mes y año --}}
                    {{-- <form method="GET" action="{{ route('backoffice.sueldos.index') }}"> --}}

                            <div class="row">
                                {{-- <div class="input-field col s12 m3 offset-m2">
                                    <label for="mes">Mes:</label>
                                    <select name="mes" id="mes">
                                        @foreach (range(1, 12) as $month)
                                            <option value="{{ $month }}" {{ $mes == $month ? 'selected' : '' }}>
                                                {{ ucfirst(\Carbon\Carbon::create()->month($month)->locale('es')->isoFormat('MMMM')) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            
                                <div class="input-field col s12 m3">
                                    
                                    <label for="anio">Año:</label>
                                    <select name="anio" id="anio">
                                        @foreach (range(now()->year - 2, now()->year) as $year)
                                        <option value="{{ $year }}" {{ $anio == $year ? 'selected' : '' }}>
                                            {{ $year }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div> --}}

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


                            {{-- <div class="input-field col s12 m3">
                                    <button type="submit" class="btn">Filtrar</button>
                                </div>
                             --}}
                        </div>
                    {{-- </form> --}}

                    {{-- Tabla de sueldos --}}
                    @php
                        $sueldoMes = 0;
                        $totalSueldoBruto = 0;
                    @endphp
                    <table class="centered">
                        {{-- <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Dias</th>
                                <th>Sueldos</th>
                                <th>Total Propinas</th>
                                <th>Total a Pagar</th>
                            </tr>
                        </thead> --}}
                        <tbody>


    {{-- @forelse ($semanas as $rango => $usuariosSemana)
        @php
            $semanaId = Str::slug($rango); // por ejemplo: "09-jun-15-jun"
        @endphp
            <h5><strong>{{ $rango }}</strong></h5>
            <div class="row">
                <div class="input-field col s12 m2 right">
                    <label for="motivo">Motivo</label>
                    <input id="motivo" placeholder="Navidad, Fiestas Patrias, etc." type="text" name="motivo" class="">
                </div>
                <div class="input-field col s12 m2 right">
                    <label for="bono">Bono</label>
                    <input id="bono" placeholder="" type="text" name="bono" class="money-format">
                </div>
            </div>
            <table class="">
                <thead>
                    <tr>
                        <th>Funcionario</th>
                        <th>Días / Masajes</th>
                        <th>Sueldo Base</th>
                        <th>Propinas</th>
                        <th>Total</th>
                        <th>Pagar</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalSemana = 0; @endphp
                    @foreach ($usuariosSemana as $usuario)
                        <tr>
                            @if(Auth::user()->has_role(config('app.admin_role')))
                                <form action="{{ route('backoffice.sueldo-pagado.store') }}" method="POST">
                                    @csrf
                            @endif
                            <td style="width: 264.22px;">
                                <a href="{{ route('backoffice.sueldo.view.admin', ['user' => $usuario['user_id'], $anio, $mes]) }}">
                                    {{ $usuario['name'] }}
                                </a>
                            </td>
                            <td>{{ $usuario['dias'] }}</td>
                            <td>${{ number_format($usuario['sueldos'], 0, '', '.') }}</td>
                            @php
                                $totalSueldoBruto += $usuario['sueldos'];
                            @endphp
                            <td>${{ number_format($usuario['propinas'], 0, '', '.') }}</td>
                            <td>${{ number_format($usuario['total'], 0, '', '.') }}</td>
                            @php
                                $sueldoMes += $usuario['total'];
                            @endphp
                            <td>


                            @php
                                $yaPagado = $pagosRealizados->contains(function ($pago) use ($usuario) {
                                    return $pago->user_id == $usuario['user_id']
                                        && $pago->semana_inicio == $usuario['inicio']
                                        && $pago->semana_fin == $usuario['fin'];
                                });

                                // dd($pagosRealizados);
                                    // Si ya fue pagado, buscamos la fecha exacta
                                $pago = $yaPagado
                                    ? $pagosRealizados->first(function ($pago) use ($usuario) {
                                        return $pago->user_id == $usuario['user_id']
                                            && $pago->semana_inicio == $usuario['inicio']
                                            && $pago->semana_fin == $usuario['fin'];
                                    })
                                    : null;
                            @endphp

                            @if ($yaPagado)
                                <span class="tooltipped" data-position="bottom" data-delay="50" data-tooltip="Pagado el {{ \Carbon\Carbon::parse($pago->fecha_pago)->locale('es')->isoFormat('D [de] MMMM') }}" style="color: #039B7B;">
                                    <i class="material-icons tiny">
                                        monetization_on
                                    </i> Pagado</span>
                            @else
                            
                            
                                @if(Auth::user()->has_role(config('app.admin_role')))
                                    <label>
                                        <input type="checkbox" name="sueldos_seleccionados[]" class="checkbox-sueldo" data-semana="{{$semanaId}}" data-total="{{$usuario['total']}}" value="{{ json_encode([
                                            'user_id' => $usuario['user_id'],
                                            'total' => $usuario['total'],
                                            'inicio' => $usuario['inicio'],
                                            'fin' => $usuario['fin'],
                                        ]) }}">
                                        <span>Pagar</span>
                                    </label>

                                @endif
                            @endif

                            </td>
                        </tr>
                        @php 
                            $totalSemana += $usuario['total'];
                        @endphp
                    @endforeach
                    <tr>
                        <td colspan="4" class="right-align"><strong>Total semana</strong></td>
                        <td><strong>${{ number_format($totalSemana, 0, '', '.') }}</strong></td>
                    </tr>
                </tbody>
            </table>

            @if(Auth::user()->has_role(config('app.admin_role')))
                    <div id="acciones-{{ $semanaId }}" class="right-align" style="margin-top: 15px; display:none;">
                        <span id="contador-{{$semanaId}}">0 Seleccionados</span>
                        <button type="submit" class="btn waves-effect waves-light">
                            Pagar seleccionados <i class="material-icons right">monetization_on</i>
                        </button>
                    </div>
                </form>
            @endif


            @empty
            <p>No hay registros para este período.</p>
    @endforelse --}}


    @forelse ($semanas as $rango => $usuariosSemana)
        @php
            $semanaId = Str::slug($rango); // por ejemplo: "09-jun-15-jun"
        @endphp

        @if(Auth::user()->has_role(config('app.admin_role')))
            {{-- FORMULARIO POR SEMANA --}}
            <form action="{{ route('backoffice.sueldo-pagado.store') }}" method="POST" id="form-{{ $semanaId }}">
                @csrf
        @endif

        <h5><strong>{{ $rango }}</strong></h5>

        <div class="row">
            <div class="input-field col s12 m2 right">
                <label for="motivo-{{ $semanaId }}">Motivo</label>
                <input id="motivo-{{ $semanaId }}" placeholder="Navidad, Fiestas Patrias, etc."
                    type="text" name="motivo" class="">
            </div>
            <div class="input-field col s12 m2 right">
                <label for="bono-{{ $semanaId }}">Bono</label>
                <input id="bono-{{ $semanaId }}" placeholder="" type="text"
                    name="bono" class="money-format">
            </div>
        </div>

        <table class="">
            <thead>
                <tr>
                    <th>Funcionario</th>
                    <th>Días / Masajes</th>
                    <th>Sueldo Base</th>
                    <th>Propinas</th>
                    <th>Bono</th>
                    <th>Motivo</th>
                    <th>Total</th>
                    <th>Pagar</th>
                </tr>
            </thead>
            <tbody>
                @php $totalSemana = 0; @endphp
                @foreach ($usuariosSemana as $usuario)
                    <tr>
                        <td style="width: 264.22px;">
                            <a href="{{ route('backoffice.sueldo.view.admin', ['user' => $usuario['user_id'], $anio, $mes]) }}">
                                {{ $usuario['name'] }}
                            </a>
                        </td>
                        <td>{{ $usuario['dias'] }}</td>
                        {{-- @php
                            $totalSueldoBruto += $usuario['sueldos'];
                        @endphp --}}

                        @php
                            // Detectar si es masoterapeuta según los roles  guardados en $semanas
                            $esMaso = is_array($usuario['role'])
                                ? in_array('Masoterapeuta', $usuario['role'])
                                : (stripos((string)$usuario['role'], 'Masoterapeuta') !== false);

                            if ($esMaso) {
                                // Para masoterapeutas: sumar el TOTAL sin el bono
                                $bono = (int) ($usuario['bono'] ?? 0);
                                $totalSueldoBruto += ($usuario['total'] - $bono);
                            } else {
                                // Para el resto: sumar solo sueldo base
                                $totalSueldoBruto += $usuario['sueldos'];
                            }
                        @endphp
                        
                        <td>${{ $esMaso ? ($usuario['total'] - $bono) : number_format($usuario['sueldos'], 0, '', '.') }}</td>

                        <td>${{ number_format($usuario['propinas'], 0, '', '.') }}</td>

                        <td>${{ number_format($usuario['bono'], 0, '', '.') ?? '-' }}</td>

                        <td>{{ $usuario['motivo'] ?? '-' }}</td>



                        <td>${{ number_format($usuario['total'], 0, '', '.') }}</td>
                        @php
                            $sueldoMes += $usuario['total'];
                        @endphp
                        <td>
                            @php
                                $yaPagado = $pagosRealizados->contains(function ($pago) use ($usuario) {
                                    return $pago->user_id == $usuario['user_id']
                                        && $pago->semana_inicio == $usuario['inicio']
                                        && $pago->semana_fin == $usuario['fin'];
                                });

                                $pago = $yaPagado
                                    ? $pagosRealizados->first(function ($pago) use ($usuario) {
                                        return $pago->user_id == $usuario['user_id']
                                            && $pago->semana_inicio == $usuario['inicio']
                                            && $pago->semana_fin == $usuario['fin'];
                                    })
                                    : null;
                            @endphp

                            @if ($yaPagado)
                                <span class="tooltipped" data-position="bottom" data-delay="50"
                                    data-tooltip="Pagado el {{ \Carbon\Carbon::parse($pago->fecha_pago)->locale('es')->isoFormat('D [de] MMMM') }}" style="color: #039B7B;">
                                    <i class="material-icons tiny">monetization_on</i> Pagado
                                </span>
                            @else
                                @if(Auth::user()->has_role(config('app.admin_role')))
                                    <label>
                                        <input type="checkbox"
                                            name="sueldos_seleccionados[]"
                                            class="checkbox-sueldo"
                                            data-semana="{{$semanaId}}"
                                            data-total="{{$usuario['total']}}"
                                            value="{{ json_encode([
                                                    'user_id' => $usuario['user_id'],
                                                    'total'   => $usuario['total'],
                                                    'inicio'  => $usuario['inicio'],
                                                    'fin'     => $usuario['fin'],
                                                ]) }}">
                                        <span>Pagar</span>
                                    </label>
                                @endif
                            @endif
                        </td>
                    </tr>
                    @php 
                        $totalSemana += $usuario['total'];
                    @endphp
                @endforeach
                <tr>
                    <td colspan="4" class="right-align"><strong>Total semana</strong></td>
                    <td><strong>${{ number_format($totalSemana, 0, '', '.') }}</strong></td>
                </tr>
            </tbody>
        </table>

        @if(Auth::user()->has_role(config('app.admin_role')))
            <div id="acciones-{{ $semanaId }}" class="right-align" style="margin-top: 15px; display:none;">
                <span id="contador-{{$semanaId}}">0 Seleccionados</span>
                <button type="submit" class="btn waves-effect waves-light">
                    Pagar seleccionados <i class="material-icons right">monetization_on</i>
                </button>
            </div>
            </form>
        @endif
    @empty
        <p>No hay registros para este período.</p>
@endforelse

    
    <table>
        <tbody>
            <tr>
                <td ></td>
                <td ></td>
                <td ></td>
                <td class="center"><strong>Total sueldos: ${{number_format($totalSueldoBruto,0,'','.')}} </strong></td>
                <td class="right"><strong>Total a pagar: ${{number_format($sueldoMes,0,'','.')}} </strong></td>
            </tr>
        </tbody>
    </table>
                        
                    

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('foot')
    <script>
        $(document).ready(function () {
            $('select').material_select({
                classes:"left-text"
            });
        });
    </script>

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
        function cambiarMesAnio(valor) {
            const [mes, anio] = valor.split('-');
            const form = document.createElement('form');
            form.method = 'GET';
            form.action = "{{ route('backoffice.sueldos.index') }}";

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
    
{{-- <script>
    $(document).ready(function () {
        function actualizarUI() {
            let seleccionados = $('.checkbox-sueldo:checked').length;

            if (seleccionados > 0) {
                $('#asignacion-pagados').show();
                $('#contador-pagados').text(seleccionados + ' Seleccionados');
            } else {
                $('#asignacion-pagados').hide();
            }
        }

        // Ejecutar al cargar y cuando se haga clic en un checkbox
        actualizarUI();

        $(document).on('change', '.checkbox-sueldo', function () {
            actualizarUI();
        });
    });
</script> --}}


{{-- 
<script>
    document.addEventListener('DOMContentLoaded', function () {
        $('.checkbox-sueldo').on('change', function () {
            const semana = this.dataset.semana;
            const checkboxes = document.querySelectorAll(`.checkbox-sueldo[data-semana="${semana}"]:checked`);
            const contador = document.getElementById(`contador-${semana}`);
            const contenedor = document.getElementById(`acciones-${semana}`);

            const total = parseInt($(`.checkbox-sueldo:checked`).data('total'));

            var contar = contar + total;
            console.warn(parseInt(total));

            if (checkboxes.length > 0) {
                contador.textContent = `${checkboxes.length} Seleccionados`;
                contenedor.style.display = 'block';
            } else {
                contenedor.style.display = 'none';
            }
        });

        // Ejecutar al cargar la vista (útil si se recarga con checks marcados)
        $('.checkbox-sueldo').each(function () {
            const semana = this.dataset.semana;
            const checkboxes = document.querySelectorAll(`.checkbox-sueldo[data-semana="${semana}"]:checked`);
            const contador = document.getElementById(`contador-${semana}`);
            const contenedor = document.getElementById(`acciones-${semana}`);


            if (checkboxes.length > 0) {
                contador.textContent = `${checkboxes.length} Seleccionados`;
                contenedor.style.display = 'block';
            }
        });
    });
</script> --}}


<script>
    document.addEventListener('DOMContentLoaded', function () {
        function actualizarTotalesPorSemana(semana) {
            const checkboxes = document.querySelectorAll(`.checkbox-sueldo[data-semana="${semana}"]:checked`);
            const contador = document.getElementById(`contador-${semana}`);
            const contenedor = document.getElementById(`acciones-${semana}`);

            let total = 0;
            checkboxes.forEach(cb => {
                total += parseInt(cb.dataset.total) || 0;
            });

            if (checkboxes.length > 0) {
                contador.textContent = `${checkboxes.length} seleccionados - $${total.toLocaleString('es-CL')}`;
                contenedor.style.display = 'block';
            } else {
                contador.textContent = `0 seleccionados - $0`;
                contenedor.style.display = 'none';
            }
        }

        // Al cambiar un checkbox
        document.querySelectorAll('.checkbox-sueldo').forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                const semana = this.dataset.semana;
                actualizarTotalesPorSemana(semana);
            });
        });

        // Ejecutar al cargar si hay checks marcados
        document.querySelectorAll('.checkbox-sueldo:checked').forEach(cb => {
            const semana = cb.dataset.semana;
            actualizarTotalesPorSemana(semana);
        });
    });
</script>

<script>
$(document).ready(function () {
  function formatCLP(valor) {
    return '$' + valor.toLocaleString('es-CL');
  }

  function limpiarNumero(valor) {
    return parseInt(valor.replace(/[$.]/g, '')) || 0;
  }


  $('.money-format').change(function (e) {
    var soloBono = limpiarNumero($('.money-format').val());
    $('.money-format').val(formatCLP(soloBono));
  });
});
</script>

@endsection
