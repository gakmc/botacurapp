{{--
    Partial: disponibilidad-resumen
    Muestra disponibilidad por espacio_tipo y slots de tinaja para una fecha.

    Uso:
        @include('themes.backoffice.partials.disponibilidad-resumen', ['fecha' => '2026-08-15'])

    Parámetros:
        $fecha  string  Fecha en formato Y-m-d
--}}
@php $uid = str_replace('-', '', $fecha); @endphp
<div class="col s12 m6 l6">
<ul class="collection">
    <li class="collection-item avatar">
        <i class="material-icons circle teal">spa</i>
        <span class="title">Slots Spa:</span>
        {{-- <p>Total:</p> --}}
        {{-- <a href="#!" class="secondary-content"><i class="material-icons">group_add</i></a> --}}
        <span class="secondary-content" style="color: #039B7B" id="disp-tinaja-{{ $uid }}" style="font-size:13px; font-weight:600; color:#9e9e9e;">Cargando...</span>
    </li>
</ul>
</div>
<div class="col s12 m6 l6" style="padding: 0 4px;">

    {{-- Chips por espacio_tipo --}}
    <div id="disp-espacios-{{ $uid }}"
         style="display:flex; flex-wrap:wrap; align-items:center; gap:6px; min-height:32px; margin-bottom:6px;">
        <span style="color:#9e9e9e; font-size:13px;">Cargando espacios…</span>
    </div>

    {{-- Tinaja (fila compacta) --}}
    {{-- <div style="display:flex; align-items:center; gap:8px;">
        <i class="material-icons teal-text" style="font-size:18px;">spa</i>
        <span style="font-size:13px; color:#555;">Slots Spa:</span>
        <span id="disp-tinaja-{{ $uid }}"
              style="font-size:13px; font-weight:600; color:#9e9e9e;">Cargando…</span>
    </div> --}}

</div>


<script>
(function () {
    var NOMBRES = {
        estacion_economico:  'Est. Eco',
        estacion_intermedio: 'Est. Inter',
        estacion_full:       'Est. Full',
        terraza:             'Terraza',
        reposera:            'Reposera'
    };

    function colorChip(disponibles, max) {
        if (disponibles === 0)           return { bg: '#F44336', text: '#fff' };
        if (disponibles <= max * 0.33)   return { bg: '#FF9800', text: '#fff' };
        return { bg: '#009688', text: '#fff' };
    }

    var url = '{{ route("backoffice.disponibilidad.resumen", $fecha) }}';

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (res) { return res.json(); })
        .then(function (d) {
            // ── Chips de espacio_tipo ─────────────────────────────
            var wrap = document.getElementById('disp-espacios-{{ $uid }}');
            if (wrap && d.espacios) {
                wrap.innerHTML = '';
                var tipos = Object.keys(d.espacios);
                tipos.forEach(function (tipo) {
                    var e = d.espacios[tipo];
                    var nombre = NOMBRES[tipo] || tipo;
                    var col = colorChip(e.disponibles, e.max);

                    var chip = document.createElement('div');
                    chip.style.cssText =
                        'display:inline-flex;align-items:center;padding:0 10px;height:28px;' +
                        'border-radius:14px;font-size:12px;font-weight:500;white-space:nowrap;' +
                        'background:' + col.bg + ';color:' + col.text + ';';

                    var label = e.disponibles === 0
                        ? nombre + ': Agotado'
                        : nombre + ': ' + e.disponibles + (e.disponibles === 1 ? ' libre' : ' libres');

                    chip.textContent = label;
                    wrap.appendChild(chip);
                });
            }

            // ── Tinaja ────────────────────────────────────────────
            var tinajaEl = document.getElementById('disp-tinaja-{{ $uid }}');
            if (tinajaEl && d.tinaja) {
                var disp = d.tinaja.disponibles;
                tinajaEl.textContent = disp + ' / ' + d.tinaja.max_slots + ' libres';
                tinajaEl.style.color = disp > 5 ? '#009688'
                                     : disp > 0 ? '#FF9800'
                                     : '#F44336';
            }
        })
        .catch(function () { /* silencioso */ });
})();
</script>
