@extends('themes.backoffice.layouts.admin')

@section('title', 'Reservas')

@section('breadcrumbs')
<li><a href="{{route ('backoffice.reservas.registros') }}">Reservas</a></li>
@endsection

@section('dropdown_settings')
{{-- <li><a href="{{route ('backoffice.reservas.registros') }}" class="grey-text text-darken-2">Reservas</a></li> --}}
@endsection

@section('head')
@endsection

@section('content')

<div class="section">
    <a href="?page=1"><p class="caption"><strong>Reservas {{$fechaF}}</strong></p></a>

    <div class="row">
        <div class="col s2 green-text offset-s1">
            <i class='material-icons left'>done_all</i>Registro completo
        </div>
        <div class="col s2 green-text offset-s1">
            <i class='material-icons left'>check</i>Registro incompleto
        </div>
        <div class="col s2 red-text offset-s1">
            <i class='material-icons left'>close</i>No Registra
        </div> 
        <div class="col s2 red-text offset-s1">
            <i class='material-icons left'>do_not_disturb_alt</i>No Aplica
        </div> 
    </div>
    {{-- ===== Widget del clima del día (Open-Meteo, sin clave) ===== --}}
    <div class="clx" id="clx" data-lat="-33.596" data-lon="-70.406" data-fecha="{{ $fechaF ?: now()->format('Y-m-d') }}">
        <div class="clx-head">
            <div class="clx-title">Clima del día <span id="clxLugar">· El Manzano, Cajón del Maipo</span></div>
            <div class="clx-upd" id="clxUpd">Cargando pronóstico…</div>
        </div>
        <div class="clx-body">
            <div class="clx-hoy" id="clxHoy"></div>
        </div>
        <div class="clx-alertas" id="clxAlertas"></div>
        <div class="clx-horas" id="clxHoras"></div>
    </div>
    <style>
    .clx{background:linear-gradient(135deg,#f0f9ff,#ffffff 60%);border:1px solid #e2e8f0;border-radius:18px;padding:16px 18px;margin:0 0 20px;box-shadow:0 8px 24px -14px rgba(15,23,42,.18);font-family:inherit;color:#0f172a}
    .clx-head{display:flex;justify-content:space-between;align-items:baseline;gap:10px;flex-wrap:wrap;margin-bottom:10px}
    .clx-title{font-weight:700;font-size:1.02rem}
    .clx-title span{font-weight:500;color:#64748b;font-size:.85rem}
    .clx-upd{font-size:.78rem;color:#94a3b8}
    .clx-body{display:flex;gap:16px;flex-wrap:wrap;align-items:stretch}
    .clx-hoy{display:flex;align-items:center;gap:14px;min-width:240px;flex:1}
    .clx-ico{font-size:2.8rem;line-height:1}
    .clx-temp{font-size:2.2rem;font-weight:700;line-height:1}
    .clx-desc{color:#475569;font-size:.9rem;margin-top:4px}
    .clx-meta{display:flex;gap:10px;flex-wrap:wrap;margin-top:6px;font-size:.8rem;color:#475569}
    .clx-meta b{color:#0f172a}
    .clx-dias{display:flex;gap:8px;flex-wrap:wrap;flex:2;justify-content:flex-end}
    .clx-dia{min-width:74px;padding:8px 6px;border-radius:12px;text-align:center;background:#fff;border:1px solid #e2e8f0;cursor:pointer;transition:all .15s;font-size:.8rem}
    .clx-dia:hover{border-color:#93c5fd}
    .clx-dia.on{background:#eff6ff;border-color:#3B82F6}
    .clx-dia .n{font-weight:700;color:#334155;text-transform:capitalize}
    .clx-dia .i{font-size:1.4rem;margin:2px 0}
    .clx-dia .t{color:#0f172a}
    .clx-dia .t small{color:#94a3b8}
    .clx-dia .r{color:#2563eb;font-size:.74rem}
    .clx-alertas{display:flex;gap:6px;flex-wrap:wrap;margin-top:10px}
    .clx-al{font-size:.78rem;font-weight:600;padding:4px 10px;border-radius:999px;background:#fef3c7;color:#92400e}
    .clx-al.azul{background:#dbeafe;color:#1e40af}.clx-al.rojo{background:#fee2e2;color:#b91c1c}
    .clx-horas{display:grid;grid-template-columns:repeat(10,minmax(48px,1fr));gap:4px;margin-top:12px;overflow-x:auto}
    .clx-h{text-align:center;padding:6px 2px;border-radius:10px;background:#f8fafc;font-size:.75rem;color:#475569}
    .clx-h b{display:block;color:#0f172a;font-size:.88rem}
    .clx-h .i{font-size:1.05rem}
    .clx-h .r{color:#2563eb}
    </style>
    <script>
    (function () {
        var box = document.getElementById('clx');
        if (!box) return;
        var lat = box.dataset.lat, lon = box.dataset.lon, fecha = box.dataset.fecha;
        var WMO = {0:['☀️','Despejado'],1:['🌤️','Mayormente despejado'],2:['⛅','Parcial nublado'],3:['☁️','Nublado'],45:['🌫️','Neblina'],48:['🌫️','Neblina con escarcha'],
            51:['🌦️','Llovizna débil'],53:['🌦️','Llovizna'],55:['🌧️','Llovizna intensa'],56:['🌧️','Llovizna helada'],57:['🌧️','Llovizna helada'],61:['🌦️','Lluvia débil'],63:['🌧️','Lluvia'],65:['🌧️','Lluvia intensa'],
            66:['🌧️','Lluvia helada'],67:['🌧️','Lluvia helada'],71:['🌨️','Nieve débil'],73:['🌨️','Nieve'],75:['❄️','Nieve intensa'],77:['🌨️','Granizo fino'],80:['🌦️','Chubascos'],81:['🌧️','Chubascos'],82:['⛈️','Chubascos fuertes'],
            85:['🌨️','Chubascos de nieve'],86:['❄️','Chubascos de nieve'],95:['⛈️','Tormenta'],96:['⛈️','Tormenta con granizo'],99:['⛈️','Tormenta con granizo']};
        function w(c) { return WMO[c] || ['🌡️', '—']; }
        function r(n) { return Math.round(n); }
        var dias = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'];
        var data = null, sel = 0;
        function horasDe(i) {
            var fecha = data.daily.time[i], out = [];
            for (var k = 0; k < data.hourly.time.length; k++) {
                var t = data.hourly.time[k];
                if (t.indexOf(fecha) === 0) {
                    var h = +t.slice(11, 13);
                    if (h >= 10 && h <= 19) out.push(k);
                }
            }
            return out;
        }
        function alertas(i) {
            var d = data.daily, a = [], hs = horasDe(i), minH = 99, viento = 0;
            hs.forEach(function (k) { minH = Math.min(minH, data.hourly.temperature_2m[k]); viento = Math.max(viento, data.hourly.wind_speed_10m[k]); });
            if ((d.precipitation_probability_max[i] || 0) >= 60) a.push(['Lluvia probable (' + d.precipitation_probability_max[i] + '%): revisar terraza y mantas', 'azul']);
            if (minH <= 8) a.push(['Frío en el horario de atención (' + r(minH) + '°): mantas y bata', 'azul']);
            if (viento >= 35) a.push(['Viento fuerte (' + r(viento) + ' km/h)', '']);
            if (d.temperature_2m_max[i] >= 30) a.push(['Calor (' + r(d.temperature_2m_max[i]) + '°): sombra y agua', 'rojo']);
            if ((d.uv_index_max[i] || 0) >= 8) a.push(['UV muy alto (' + r(d.uv_index_max[i]) + '): bloqueador', 'rojo']);
            if (d.weather_code[i] >= 71 && d.weather_code[i] <= 77) a.push(['Nieve: revisar el camino', 'rojo']);
            return a;
        }
        function pintar() {
            var d = data.daily, i = 0, hoy = new Date().toLocaleDateString('sv-SE') === fecha && data.current;
            var wc = hoy ? w(data.current.weather_code) : w(d.weather_code[i]);
            var f = new Date(fecha + 'T12:00:00');
            var titulo = (hoy ? 'Ahora · ' : '') + dias[f.getDay()] + ' ' + fecha.slice(8, 10) + '/' + fecha.slice(5, 7);
            document.getElementById('clxHoy').innerHTML =
                '<div class="clx-ico">' + wc[0] + '</div><div>' +
                '<div class="clx-temp">' + (hoy ? r(data.current.temperature_2m) : r(d.temperature_2m_max[i])) + '°</div>' +
                '<div class="clx-desc">' + titulo + ' · ' + wc[1] + '</div>' +
                '<div class="clx-meta"><span>Máx <b>' + r(d.temperature_2m_max[i]) + '°</b></span><span>Mín <b>' + r(d.temperature_2m_min[i]) + '°</b></span>' +
                '<span>Lluvia <b>' + (d.precipitation_probability_max[i] == null ? '–' : d.precipitation_probability_max[i] + '%') + '</b> · ' + (d.precipitation_sum[i] || 0).toFixed(1) + ' mm</span>' +
                '<span>Viento <b>' + r(d.wind_speed_10m_max[i]) + ' km/h</b></span><span>UV <b>' + (d.uv_index_max[i] == null ? '–' : r(d.uv_index_max[i])) + '</b></span></div></div>';
            document.getElementById('clxAlertas').innerHTML = alertas(i).map(function (a) {
                return '<span class="clx-al ' + a[1] + '">' + a[0] + '</span>';
            }).join('');
            document.getElementById('clxHoras').innerHTML = horasDe(i).map(function (k) {
                var x = w(data.hourly.weather_code[k]);
                return '<div class="clx-h">' + data.hourly.time[k].slice(11, 16) + '<div class="i">' + x[0] + '</div><b>' + r(data.hourly.temperature_2m[k]) + '°</b>' +
                    '<span class="r">' + (data.hourly.precipitation_probability[k] == null ? '–' : data.hourly.precipitation_probability[k] + '%') + '</span></div>';
            }).join('');
        }
        function listo(d) {
            data = d;
            var hhmm = new Date().toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit' });
            document.getElementById('clxUpd').textContent = 'Pronóstico Open-Meteo · actualizado ' + hhmm;
            pintar();
        }
        var hoyStr = new Date().toLocaleDateString('sv-SE');
        var dif = Math.round((new Date(fecha + 'T12:00:00') - new Date(hoyStr + 'T12:00:00')) / 86400000);
        if (dif > 15 || dif < -90) {
            document.getElementById('clxUpd').textContent = dif > 15 ? 'Aún no hay pronóstico para esta fecha (disponible desde 16 días antes).' : 'Sin datos para esta fecha.';
            return;
        }
        var clave = 'clx_' + lat + '_' + lon + '_' + fecha, cache = null;
        try { cache = JSON.parse(sessionStorage.getItem(clave) || 'null'); } catch (e) {}
        if (cache && Date.now() - cache.t < 30 * 60 * 1000) { listo(cache.d); return; }
        var url = 'https://api.open-meteo.com/v1/forecast?latitude=' + lat + '&longitude=' + lon +
            (dif === 0 ? '&current=temperature_2m,weather_code,wind_speed_10m' : '') +
            '&hourly=temperature_2m,precipitation_probability,weather_code,wind_speed_10m' +
            '&daily=weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max,precipitation_sum,wind_speed_10m_max,uv_index_max' +
            '&timezone=America%2FSantiago&start_date=' + fecha + '&end_date=' + fecha;
        fetch(url).then(function (res) { return res.json(); }).then(function (d) {
            if (!d || !d.daily) throw new Error('sin datos');
            try { sessionStorage.setItem(clave, JSON.stringify({ t: Date.now(), d: d })); } catch (e) {}
            listo(d);
        }).catch(function () {
            document.getElementById('clxUpd').textContent = 'No se pudo cargar el pronóstico. Reintenta más tarde.';
        });
    })();
    </script>

    
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="card-panel ">

            @foreach ($reservasPaginadas as $fecha => $reservas)
            <div id="work-collections">
                <div class="row">

                  @php
                      $totalDesayunos = 0;
                      $totalOnces = 0;
                      foreach ($reservas as $reservaBuffet) {
                          foreach ($reservaBuffet->desayunoOnce as $itemBuffet) {
                              if ($itemBuffet->tipo === 'desayuno') {
                                  $totalDesayunos += (int) ($reservaBuffet->cantidad_personas ?? 0);
                              } elseif ($itemBuffet->tipo === 'once') {
                                  $totalOnces += (int) ($reservaBuffet->cantidad_personas ?? 0);
                              }
                          }
                      }
                  @endphp

                  <div class="col s12 m4 l4">
                    <ul class="collection">
                        <li class="collection-item avatar">
                          <i class="material-icons circle red">group_add</i>
                          <span class="title">Cantidad de Asistentes</span>
                          <p>Total:</p>
                          {{-- <a href="#!" class="secondary-content"><i class="material-icons">group_add</i></a> --}}
                          <span class="secondary-content" style="color: #039B7B">
                            {{$reservas->sum('cantidad_personas')}} {{$reservas->sum('cantidad_personas') > 1 ? "Personas" : "Persona"}}
                          </span>
                        </li>
                      </ul>
                  </div>

                  <div class="col s12 m8 l8">
                    @php $fechaApi = \Carbon\Carbon::createFromFormat('d-m-Y', $fecha)->format('Y-m-d'); @endphp
                    @include('themes.backoffice.partials.disponibilidad-resumen', ['fecha' => $fechaApi])
                  </div>

                </div>

                @if ($totalDesayunos > 0 || $totalOnces > 0)
                <div class="row">

                  @if ($totalDesayunos > 0)
                  <div class="col s12 m4 l4">
                    @include('themes.backoffice.pages.reserva.includes.desayuno_once_card', ['titulo' => 'Desayuno', 'total' => $totalDesayunos])
                  </div>
                  @endif

                  @if ($totalOnces > 0)
                  <div class="col s12 m4 l4 offset-m4">
                    @include('themes.backoffice.pages.reserva.includes.desayuno_once_card', ['titulo' => 'Once', 'total' => $totalOnces])
                  </div>
                  @endif

                </div>
                @endif
                                <a href="#modalSaunaDisponible" data-target="modal-sauna-disponible" class="waves-effect waves-light btn modal-trigger right hide-on-small-only hide-on-med-only">Horas Disponibles <i class='material-icons right'>access_time</i></a>
                                <a href="#modalLugaresDisponible" data-target="modal-lugares-disponible" class="waves-effect waves-light btn modal-trigger right hide-on-small-only hide-on-med-only">Lugares Disponibles <i class='material-icons right'>beach_access</i></a>
            </div>

                <p class="caption"><strong>Reservas: {{ $fecha }}</strong></p>
                    @if (!isset($reservas))
                        <h5 class="center">
                            No se registran reservas
                        </h5>
                    @else

                            <table class="bordered centered responsive-table">
                                <thead>
                                <tr>
                                    <th>Recepcionado</th>
                                    <th>Nombre</th>
                                    <th>WhatsApp</th>
                                    <th>Cant. Personas</th>
                                    <th>Programa</th>
                                    <th>Ubicación</th>
                                    <th>Desayuno / Once</th>
                                    <th>Menus</th>
                                    <th>Spa</th>
                                    <th>Masajes</th>
                                </tr>
                                </thead>
                        
                                <tbody>

                                @foreach ($reservas as $reserva)
                                    @php
                                        $primeraVisita  = $reserva->visitas->first();
                                        $ultimaVisita   = $reserva->visitas->last();
                                        $visitas        = $reserva->visitas;
                                        $primerMasaje = $reserva->masajes->first();
                                        
                                        $menus = $reserva->menus ?? collect();
                                        $masajes = $reserva->masajes ?? collect();
                                        //$masajes = $ultimaVisita->masajes;

                                        $totalMenus = optional($menus)->count();
                                        $totalMasajes = optional($masajes)->count();
                                        $totalVisitas = optional($visitas)->count();

                                        $menusConProducto = $menus->filter(function($menu){ 
                                            return $menu->id_producto_entrada !== null || $menu->id_producto_fondo !== null;
                                        })->count();

                                        $masajesConHorario = $masajes->filter(function($masaje){ 
                                            return $masaje->horario_masaje !== null;
                                        })->count();

                                        $visitasConHorario = $visitas->filter(function($visita){
                                            return $visita->horario_sauna !== null || $visita->horario_tinaja !== null;
                                        })->count();

                                        if ($totalMenus > 0 && ($menusConProducto === $totalMenus)) {
                                            $iconoMenu = 'done_all';
                                            $colorMenu = 'green';
                                            $linkMenu = '#';
                                        } elseif ($menusConProducto > 0) {
                                            $iconoMenu = 'check';
                                            $colorMenu = 'green';
                                            $linkMenu = route('backoffice.reserva.menus', ['reserva' => $reserva]);
                                        } elseif (!$reserva->programa->incluye_almuerzos && !$reserva->incluye_almuerzos_extra) {
                                            $iconoMenu = 'do_not_disturb_alt';
                                            $colorMenu = 'red';
                                            $linkMenu = '#';
                                        } else {
                                            $iconoMenu = 'close';
                                            $colorMenu = 'red';
                                            $linkMenu = ($ultimaVisita) ? route('backoffice.reserva.menus', ['reserva' => $reserva]) : route('backoffice.reserva.visitas.create', ['reserva' => $reserva->id]);
                                        }

                                        if ($totalMasajes > 0 && ($masajesConHorario === $totalMasajes)) {
                                            $iconoMasaje = 'done_all';
                                            $colorMasaje = 'green';
                                            $linkMasaje = '#';
                                        } elseif ($masajesConHorario > 0) {
                                            $iconoMasaje = 'check';
                                            $colorMasaje = 'green';
                                            $linkMasaje = route('backoffice.reserva.masajes', ['reserva' => $reserva]);
                                        } elseif ( !$reserva->programa->incluye_masajes && !$reserva->incluye_masajes_extra) {
                                            $iconoMasaje = 'do_not_disturb_alt';
                                            $colorMasaje = 'red';
                                            $linkMasaje = '#';
                                        } else {
                                            $iconoMasaje = 'close';
                                            $colorMasaje = 'red';
                                            $linkMasaje = ($ultimaVisita) ? route('backoffice.reserva.masajes', ['reserva' => $reserva]) : route('backoffice.reserva.visitas.create', ['reserva' => $reserva->id]);
                                        }

                                        if ($totalVisitas > 0 && ($visitasConHorario === $totalVisitas)) {
                                            $iconoVisita = 'done_all';
                                            $colorVisita = 'green';
                                            $linkVisita = '#';
                                        } elseif ($visitasConHorario > 0) {
                                            $iconoVisita = 'check';
                                            $colorVisita = 'green';
                                            $linkVisita = route('backoffice.reserva.visitas.spa', ['reserva' => $reserva, 'visita' => $reserva->visitas->first()]);
                                        } else {
                                            $iconoVisita = 'close';
                                            $colorVisita = 'red';
                                            $linkVisita = ($ultimaVisita) ? route('backoffice.reserva.visitas.spa', ['reserva' => $reserva, 'visita' => $reserva->visitas->first()]) : route('backoffice.reserva.visitas.create', ['reserva' => $reserva->id]);
                                        }

                                    @endphp
                                    <tr>

                                        <td>
                                            @if (is_null($reserva->estadoRecepcion))
                                                <a id="icono-reserva-{{ $reserva->id }}" onclick="recepcionar({{ $reserva->id }})" class="btn-floating white">
                                                    <i id="icono-reserva-{{ $reserva->id }}" class="material-icons red-text">exit_to_app</i>
                                                </a>
                                            @else
                                                <i class="material-icons green-text tooltipped" style="cursor: pointer" data-position="top" data-tooltip="Recepcionado por {{ $reserva->estadoRecepcion->user->name }}">person_pin</i>
                                            @endif
                                        </td>
                                                                                    

                                        <td>
                                            <a href="{{ route('backoffice.reserva.show', $reserva) }}">
                                                {{($reserva->venta->tiene_gc) ? '🎁' : ''}} {{$reserva->cliente->nombre_cliente}} 
                                            </a>
                                        </td>
                                        <td>
                                            @if(is_null($reserva->cliente->whatsapp_cliente)) 
                                                No Registra
                                            @else
                                                <a href="https://api.whatsapp.com/send?phone={{$reserva->cliente->whatsapp_cliente}}" target="_blank">+{{$reserva->cliente->whatsapp_cliente}}</a>
                                            @endif
                                        </td>
                                        <td>
                                            {{$reserva->cantidad_personas}}
                                        </td>
                                        <td>

                                            {{$reserva->programa->nombre_programa}}

                                        </td>
                                        <td>
                                            @if(isset($ultimaVisita))
                                                @if (is_null($ultimaVisita->id_ubicacion) && is_null($ultimaVisita->horario_sauna))
                                                    {{-- <a href="{{route('backoffice.visita.edit_ubicacion',['visitum'=>$reserva->visitas->first()])}}" class="blue"> --}}
                                                    <a href="{{route('backoffice.visita.registrar',$reserva)}}" class="blue-text">
                                                        Falta informacion
                                                    </a>
                                                @else
                                                    {{optional($ultimaVisita->ubicacion)->nombre ?? 'Sin ubicación'}}
                                                @endif

                                            @else
                                                <a href="{{ route('backoffice.reserva.visitas.create', ['reserva' => $reserva->id]) }}">
                                                    <p class="red-text"><strong>No se guardo Visita</strong></p>
                                                </a>
                                            @endif
                                            
                                        </td>
                                        <td class="dyo-td">
                                            @if (\App\Services\DesayunoOnceService::incluye($reserva))
                                                <?php
                                                    $dyoFD = $reserva->desayunoOnce->firstWhere('tipo', 'desayuno');
                                                    $dyoFO = $reserva->desayunoOnce->firstWhere('tipo', 'once');
                                                    $dyoVal = $dyoFD ? 'desayuno' : ($dyoFO ? 'once' : '');
                                                    $dyoFila = $dyoFD ?: $dyoFO;
                                                    $dyoOk = $dyoFila && !empty($dyoFila->confirmado_at);
                                                    $dyoConf = route('backoffice.reserva.desayuno_once.confirmar', $reserva);
                                                ?>
                                                @if ($dyoFD && $dyoFO)
                                                    <div class="dyo-ambos" data-personas="{{ (int) $reserva->cantidad_personas }}" data-conf="{{ $dyoConf }}">
                                                        <button type="button" class="dyo-chip tooltipped {{ !empty($dyoFD->confirmado_at) ? 'ok' : '' }}" data-tipo="desayuno" data-position="top" data-tooltip="{{ \App\Services\DesayunoOnceService::textoConfirmado($dyoFD) ?: '3 comidas · confirmar que pasó a desayuno' }}"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg><svg class="dyo-ck" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></button>
                                                        <button type="button" class="dyo-chip tooltipped {{ !empty($dyoFO->confirmado_at) ? 'ok' : '' }}" data-tipo="once" data-position="top" data-tooltip="{{ \App\Services\DesayunoOnceService::textoConfirmado($dyoFO) ?: '3 comidas · confirmar que pasó a once' }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9h12v5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5V9z"/><path d="M16 11h1.5a2.5 2.5 0 0 1 0 5H16M8 3v3M12 3v3"/></svg><svg class="dyo-ck" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></button>
                                                    </div>
                                                @else
                                                    <div class="dyo-wrap">
                                                        <div class="dyo {{ $dyoOk ? 'bloq' : '' }}" role="radiogroup" aria-label="Desayuno u once" data-personas="{{ (int) $reserva->cantidad_personas }}" data-valor="{{ $dyoVal }}" data-url="{{ route('backoffice.reserva.desayuno_once.rapido', $reserva) }}">
                                                            <span class="dyo-thumb" aria-hidden="true"></span>
                                                            <label class="dyo-op tooltipped" data-position="top" data-tooltip="Pasaron a desayuno">
                                                                <input type="radio" class="dyo-in" name="dyo_{{ $reserva->id }}" value="desayuno" {{ $dyoFD ? 'checked' : '' }}>
                                                                <em class="dyo-txt"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>Desayuno</em>
                                                            </label>
                                                            <label class="dyo-op tooltipped" data-position="top" data-tooltip="Queda para la once">
                                                                <input type="radio" class="dyo-in" name="dyo_{{ $reserva->id }}" value="once" {{ $dyoFO ? 'checked' : '' }}>
                                                                <em class="dyo-txt"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9h12v5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5V9z"/><path d="M16 11h1.5a2.5 2.5 0 0 1 0 5H16M8 3v3M12 3v3"/></svg>Once</em>
                                                            </label>
                                                        </div>
                                                        <button type="button" class="dyo-conf tooltipped {{ $dyoOk ? 'ok' : '' }} {{ $dyoVal ? '' : 'off' }}" data-conf="{{ $dyoConf }}" data-position="top" data-tooltip="{{ $dyoOk ? \App\Services\DesayunoOnceService::textoConfirmado($dyoFila) : ($dyoVal ? 'Confirmar que pasó a ' . $dyoVal : 'Elige desayuno u once primero') }}" aria-disabled="{{ $dyoVal ? 'false' : 'true' }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></button>
                                                    </div>
                                                @endif
                                            @else
                                                <span class="dyo-na">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{$linkMenu}}">
                                                <i class='material-icons {{$colorMenu}}-text'>{{$iconoMenu}}</i>
                                            </a>
                                        </td>
                                        <td>
                                            <a href="{{$linkVisita}}">
                                                <i class='material-icons {{$colorVisita}}-text'>{{$iconoVisita}}</i>
                                            </a>
                                            @if (isset($primeraVisita) && $primeraVisita->horario_sauna)
                                                <span>{{$primeraVisita->horario_sauna}}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{$linkMasaje}}">
                                                <i class='material-icons {{$colorMasaje}}-text'>{{$iconoMasaje}}</i>
                                            </a>
                                            @if(isset($primerMasaje) && $primerMasaje->horario_masaje)
                                                <span>{{ $primerMasaje->horario_masaje }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    
                                @endforeach
                                


                                </tbody>
                            </table>


                    @endif


                    @endforeach
                </div>


                    @include('themes.backoffice.pages.reserva.includes.modal_sauna_disponible')
                    @include('themes.backoffice.pages.reserva.includes.modal_lugares_disponible')

    </div>
</div>



<div class="fixed-action-btn toolbar hide-on-large-only" style="bottom: 45px; right: 24px;">
    <a class="btn-floating btn-large blue">
      <i class="material-icons large">apps</i>
    </a>
    <ul>
            <li>
                <a href="#modalLugaresDisponible" data-target="modal-lugares-disponible" class="waves-effect waves-light btn modal-trigger"><i class="material-icons">beach_access</i></a>
            </li>
            <li>
                <a href="#modalSaunaDisponible" data-target="modal-sauna-disponible" class="waves-effect waves-light btn modal-trigger"><i class="material-icons right">access_time</i></a>
            </li>
    </ul>

</div>
@endsection

@section('foot')
<script>
    // function activar_alerta(cliente)
    // {
    //     console.log(cliente);
        
    //     Swal.fire({
    //         toast: true,
    //         icon: 'warning',
    //         title: `${cliente} no registra masajes`,
    //         color: 'white',
    //         iconColor: 'white',
    //         background: "#039B7B",
    //         showConfirmButton: false,
    //         timer: 5000,
    //         timerProgressBar: true,
    //             didOpen: (toast) => {
    //             toast.onmouseenter = Swal.stopTimer;
    //             toast.onmouseleave = Swal.resumeTimer;
    //             }
    //     });
    // }
</script>

<script>
    $(document).ready(function () {
        $('.modal').modal();
    });
</script>

<script>
    function recepcionar(reservaId) { 
        $.ajax({
            url: '{{route("backoffice.estado_recepcion.store")}}',
            method: 'POST',
            data: {
                reserva_id: reservaId,
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {

                const icono = document.querySelector(`#icono-reserva-${reservaId}`);
                if (icono) {
                    icono.outerHTML = `
                        <i class="material-icons green-text tooltipped"
                        style="cursor: pointer"
                        data-position="top"
                        data-tooltip="Recepcionado por ${response.user_name}">
                        person_pin
                        </i>`;
                    $('.tooltipped').tooltip(); // reactivar tooltips nuevos
                }

                Swal.fire({
                    toast: true,
                    position: 'center', // Esto lo centra en la pantalla
                    icon: 'success',
                    title: response.message,
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'swal2-toast' // importante para conservar estilo toast
                    },
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                    }
                });
            },
            error: function () {
                Swal.fire('Error', 'No se pudo registrar la recepción.', 'error');
            }
        });
        }
</script>

<script>
$(document).ready(function () {
    if (typeof window.Echo !== 'undefined') {
    window.Echo.channel('recepcion-cliente')
    .listen('.cliente-recepcionado', (e) => {
        const icono = document.querySelector(`#icono-reserva-${e.reservaId}`);
        if (icono) {
            icono.outerHTML = `
                <i class="material-icons green-text tooltipped"
                style="cursor: pointer"
                data-position="top"
                data-tooltip="Recepcionado por ${e.userName}">
                person_pin
                </i>`;
            $('.tooltipped').tooltip();
        }
    });
    }else{
        console.error('Echo no está inicializado.');
    }
});
</script>

<style>
.dyo{--dyo-pad:3px;position:relative;display:inline-grid;grid-template-columns:repeat(2,minmax(0,1fr));align-items:center;padding:var(--dyo-pad);border-radius:999px;background:#f1f5f9;box-shadow:inset 0 0 0 1px #e2e8f0;min-width:184px;user-select:none;transition:box-shadow .2s,opacity .2s}
.dyo[data-valor=""]{background:#fffbeb;box-shadow:inset 0 0 0 1px #fcd34d}
.dyo-thumb{position:absolute;top:var(--dyo-pad);left:var(--dyo-pad);width:calc(50% - var(--dyo-pad));height:calc(100% - var(--dyo-pad)*2);border-radius:999px;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.08),0 4px 12px -4px rgba(15,23,42,.18);transition:transform .32s cubic-bezier(.3,1.4,.5,1),background .25s,opacity .2s;opacity:0;transform:scale(.6)}
.dyo[data-valor="desayuno"] .dyo-thumb{opacity:1;transform:translateX(0);background:linear-gradient(135deg,#fef3c7,#fde68a)}
.dyo[data-valor="once"] .dyo-thumb{opacity:1;transform:translateX(100%);background:linear-gradient(135deg,#e0e7ff,#c7d2fe)}
.dyo-op{position:relative;z-index:1;margin:0;cursor:pointer;display:block;text-align:center}
.dyo .dyo-in{position:absolute!important;opacity:0!important;width:1px!important;height:1px!important;margin:0!important;pointer-events:none!important}
.dyo-txt{display:inline-flex;align-items:center;justify-content:center;gap:5px;width:100%;padding:6px 8px;border-radius:999px;font-style:normal;font-size:.8rem;font-weight:600;line-height:1.1;color:#64748b;transition:color .2s}
.dyo-txt svg{width:14px;height:14px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;flex:none}
.dyo-op:hover .dyo-txt{color:#334155}
.dyo[data-valor="desayuno"] .dyo-op:first-of-type .dyo-txt{color:#92400e}
.dyo[data-valor="once"] .dyo-op:last-of-type .dyo-txt{color:#3730a3}
.dyo .dyo-in:focus-visible+.dyo-txt{outline:2px solid #3B82F6;outline-offset:1px}
.dyo.guardando{opacity:.65;pointer-events:none}
.dyo.guardando .dyo-thumb{animation:dyoPulse .9s ease-in-out infinite}
@keyframes dyoPulse{50%{filter:brightness(.92)}}
.dyo.error{animation:dyoShake .35s}
@keyframes dyoShake{25%{transform:translateX(-4px)}75%{transform:translateX(4px)}}
.dyo-na{color:#cbd5e1}
.dyo-wrap{display:inline-flex;align-items:center;gap:6px}
.dyo-conf{flex:none;width:30px;height:30px;padding:0;border-radius:50%;display:inline-grid;place-items:center;background:#fff;border:1.5px dashed #cbd5e1;color:#94a3b8;cursor:pointer;transition:background .2s,border-color .2s,color .2s,transform .15s}
.dyo-conf svg,.dyo-chip svg{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:2.2;stroke-linecap:round;stroke-linejoin:round}
.dyo-conf:hover:not(.off){border-color:#22c55e;color:#16a34a;transform:scale(1.06)}
.dyo-conf.ok{background:#22c55e;border:1.5px solid #22c55e;color:#fff;animation:dyoPop .3s}
.dyo-conf.ok:hover{background:#16a34a;border-color:#16a34a;color:#fff}
.dyo-conf.off{opacity:.35;cursor:not-allowed}
.dyo-conf:focus-visible,.dyo-chip:focus-visible{outline:2px solid #3B82F6;outline-offset:2px}
.dyo.bloq{pointer-events:none;box-shadow:inset 0 0 0 1.5px #86efac;background:#f0fdf4}
.dyo.bloq .dyo-thumb{box-shadow:0 1px 2px rgba(15,23,42,.08)}
.dyo-ambos{display:inline-flex;gap:5px}
.dyo-chip{display:inline-flex;align-items:center;gap:3px;padding:5px 9px;border-radius:999px;border:1.5px dashed transparent;cursor:pointer;transition:background .2s,border-color .2s,color .2s}
.dyo-chip[data-tipo=desayuno]{background:#fef3c7;color:#92400e;border-color:#fcd34d}
.dyo-chip[data-tipo=once]{background:#e0e7ff;color:#3730a3;border-color:#a5b4fc}
.dyo-chip .dyo-ck{display:none;width:13px;height:13px}
.dyo-chip.ok{background:#dcfce7;color:#15803d;border-style:solid;border-color:#86efac;animation:dyoPop .3s}
.dyo-chip.ok .dyo-ck{display:inline}
.dyo-chip.cargando,.dyo-conf.cargando{opacity:.5;pointer-events:none}
@keyframes dyoPop{50%{transform:scale(1.15)}}
.dyo-res{display:flex;justify-content:center;gap:5px;margin-top:4px;font-weight:600;font-size:.72rem;white-space:nowrap}
.dyo-res span{padding:2px 7px;border-radius:999px}
.dyo-res .d{background:#fef3c7;color:#92400e}
.dyo-res .o{background:#e0e7ff;color:#3730a3}
.dyo-res .lleno{background:#dcfce7;color:#15803d}
.dyo-td{white-space:nowrap}
.dyo-scroll{width:100%;max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}
.clx{max-width:100%;box-sizing:border-box;min-width:0}
.clx-horas{grid-template-columns:repeat(10,minmax(54px,1fr))!important;overflow-x:auto}
@media (max-width:1440px){
  .dyo{min-width:0;grid-template-columns:repeat(2,38px)}
  .dyo-txt{font-size:0;gap:0;padding:7px 0}
  .dyo-txt svg{width:16px;height:16px}
}
.dyo-toast{position:fixed;left:50%;bottom:24px;transform:translate(-50%,12px);opacity:0;display:flex;align-items:center;gap:8px;background:#0f172a;color:#fff;padding:10px 16px;border-radius:999px;font-size:.88rem;z-index:3000;box-shadow:0 12px 30px -10px rgba(0,0,0,.45);transition:transform .25s,opacity .25s}
.dyo-toast.ver{transform:translate(-50%,0);opacity:1}
.dyo-toast i{width:18px;height:18px;border-radius:50%;display:inline-grid;place-items:center;font-style:normal;font-size:.7rem;background:#22c55e}
.dyo-toast.mal i{background:#ef4444}
</style>
<script>
(function () {
    var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '{{ csrf_token() }}';
    var KEY = 'dyo_scroll';
    // la tabla se desplaza dentro de su caja en vez de ensanchar la página
    var tb = document.querySelector('.dyo-td') && document.querySelector('.dyo-td').closest('table');
    if (tb && !tb.parentElement.classList.contains('dyo-scroll')) {
        var w = document.createElement('div'); w.className = 'dyo-scroll';
        tb.parentNode.insertBefore(w, tb); w.appendChild(tb);
    }
    try { var y = sessionStorage.getItem(KEY); if (y !== null) { sessionStorage.removeItem(KEY); window.scrollTo(0, +y); } } catch (e) {}
    function toast(t, mal) {
        var d = document.createElement('div'); d.className = 'dyo-toast' + (mal ? ' mal' : '');
        d.innerHTML = '<i>' + (mal ? '!' : '✓') + '</i><span></span>'; d.lastChild.textContent = t;
        document.body.appendChild(d); requestAnimationFrame(function () { d.classList.add('ver'); });
        setTimeout(function () { d.classList.remove('ver'); setTimeout(function () { d.remove(); }, 300); }, 2200);
    }

    // tooltips con el mismo estilo del tema (Materialize .tooltipped), con respaldo para 0.x y 1.x
    function tipInit(el) {
        try {
            if (window.M && M.Tooltip) {
                var i = M.Tooltip.getInstance(el); if (i) i.destroy();
                M.Tooltip.init(el, { enterDelay: 150 });
            } else if (window.jQuery && jQuery.fn.tooltip) {
                jQuery(el).tooltip('remove'); jQuery(el).tooltip({ delay: 150 });
            }
        } catch (e) {}
    }
    function tip(el, texto) {
        el.setAttribute('data-tooltip', texto);
        try { var i = window.M && M.Tooltip && M.Tooltip.getInstance(el); if (i) { i.close && i.close(); } } catch (e) {}
        tipInit(el);
    }
    function tipsPendientes() {
        document.querySelectorAll('.dyo-td .tooltipped, .dyo-res .tooltipped').forEach(function (el) {
            var hecho = (el.M_Tooltip) || el.getAttribute('data-tooltip-id');
            if (!hecho) tipInit(el);
        });
    }
    window.addEventListener('load', function () { setTimeout(tipsPendientes, 50); });
    function resumen() {
        var t = { d: [0, 0], o: [0, 0] };
        document.querySelectorAll('.dyo-wrap').forEach(function (w) {
            var d = w.querySelector('.dyo'), v = d.dataset.valor, p = +d.dataset.personas || 0;
            if (!v) return; var k = v === 'desayuno' ? 'd' : 'o';
            t[k][1] += p; if (w.querySelector('.dyo-conf.ok')) t[k][0] += p;
        });
        document.querySelectorAll('.dyo-ambos').forEach(function (a) {
            var p = +a.dataset.personas || 0;
            a.querySelectorAll('.dyo-chip').forEach(function (c) {
                var k = c.dataset.tipo === 'desayuno' ? 'd' : 'o';
                t[k][1] += p; if (c.classList.contains('ok')) t[k][0] += p;
            });
        });
        var th = Array.prototype.find.call(document.querySelectorAll('th'), function (h) { return /Desayuno\s*\/\s*Once/.test(h.textContent); });
        if (!th) return;
        var r = th.querySelector('.dyo-res');
        if (!r) { r = document.createElement('div'); r.className = 'dyo-res'; th.appendChild(r); }
        r.querySelectorAll('.tooltipped').forEach(function (el) { try { var i = window.M && M.Tooltip && M.Tooltip.getInstance(el); if (i) i.destroy(); else if (window.jQuery && jQuery.fn.tooltip) jQuery(el).tooltip('remove'); } catch (e) {} });
        r.innerHTML = '';
        [['d', 'Desayuno'], ['o', 'Once']].forEach(function (x) {
            if (!t[x[0]][1]) return;
            var s = document.createElement('span');
            s.className = x[0] + (t[x[0]][0] === t[x[0]][1] ? ' lleno' : '');
            s.textContent = x[1] + ' ' + t[x[0]][0] + '/' + t[x[0]][1];
            s.className += ' tooltipped'; s.setAttribute('data-position', 'top');
            s.setAttribute('data-tooltip', x[1] + ': ' + t[x[0]][0] + ' de ' + t[x[0]][1] + ' personas confirmadas');
            r.appendChild(s); tipInit(s);
        });
    }
    resumen();
    document.addEventListener('click', function (e) {
        var b = e.target.closest && e.target.closest('.dyo-conf, .dyo-chip');
        if (!b || b.classList.contains('off')) return;
        var esChip = b.classList.contains('dyo-chip');
        var caja = esChip ? b.closest('.dyo-ambos') : b.closest('.dyo-wrap').querySelector('.dyo');
        var tipo = esChip ? b.dataset.tipo : caja.dataset.valor;
        if (!tipo) return;
        var nombre = tipo === 'desayuno' ? 'desayuno' : 'once';
        var valor = !b.classList.contains('ok');
        if (!valor && !window.confirm('¿Quitar la confirmación de ' + nombre + '?')) return;
        b.classList.add('cargando');
        fetch(b.dataset.conf || caja.dataset.conf, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ tipo: tipo, valor: valor ? 1 : 0 })
        }).then(function (r) { return r.json().then(function (j) { return [r.ok, j]; }); })
          .then(function (res) {
              if (!res[0] || !res[1].ok) throw new Error((res[1] && res[1].mensaje) || 'Error');
              b.classList.remove('cargando');
              b.classList.toggle('ok', valor);
              tip(b, valor ? ('Confirmado ' + res[1].hora + (res[1].por ? ' · ' + res[1].por : '')) : ((esChip ? '3 comidas · ' : '') + 'Confirmar que pasó a ' + nombre));
              if (!esChip) caja.classList.toggle('bloq', valor);
              var p = +caja.dataset.personas || 0;
              toast(valor ? (nombre.charAt(0).toUpperCase() + nombre.slice(1) + ' confirmado · ' + p + (p === 1 ? ' persona' : ' personas')) : 'Confirmación quitada');
              resumen();
          })
          .catch(function (err) {
              b.classList.remove('cargando');
              toast('No se pudo confirmar: ' + err.message, true);
          });
    });
    document.addEventListener('change', function (e) {
        var inp = e.target;
        if (!inp.classList || !inp.classList.contains('dyo-in')) return;
        var box = inp.closest('.dyo'), antes = box.dataset.valor;
        box.dataset.valor = inp.value;
        box.classList.add('guardando');
        fetch(box.dataset.url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ tipo: inp.value })
        }).then(function (r) { return r.json().then(function (j) { return [r.ok, j]; }); })
          .then(function (res) {
              if (!res[0] || !res[1].ok) throw new Error((res[1] && res[1].mensaje) || 'Error');
              toast((inp.value === 'desayuno' ? 'Desayuno' : 'Once') + ' guardado. Actualizando totales…');
              try { sessionStorage.setItem(KEY, String(window.scrollY)); } catch (e) {}
              setTimeout(function () { location.reload(); }, 800);
          })
          .catch(function (err) {
              box.classList.remove('guardando');
              box.dataset.valor = antes;
              box.querySelectorAll('.dyo-in').forEach(function (i) { i.checked = (i.value === antes); });
              box.classList.remove('error'); void box.offsetWidth; box.classList.add('error');
              toast('No se pudo guardar: ' + err.message, true);
          });
    });
})();
</script>
@endsection