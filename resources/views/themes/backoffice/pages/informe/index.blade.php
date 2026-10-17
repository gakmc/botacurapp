@extends('themes.backoffice.layouts.admin')

@section('title', 'Informes')

@section('head')
<style>
    .report-card {
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    .report-title {
        font-size: 1.3rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }
    .report-subtitle {
        font-size: 0.9rem;
        color: #777;
        margin-bottom: 1rem;
    }
    .placeholder-chart {
        height: 250px;
        background: #f2f2f2;
        border: 2px dashed #ccc;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        color: #999;
        border-radius: 6px;
    }
</style>
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Panel de Informes</strong></p>
    @php
        $bmHoy  = now()->startOfMonth();
        $bmPrev = $fecha->copy()->startOfMonth()->subMonth()->format('Y-m');
        $bmNext = $fecha->copy()->startOfMonth()->addMonth();
        $bmEsHoy = $fecha->format('Y-m') === $bmHoy->format('Y-m');
        $bmMeses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    @endphp
    <div class="bmx" id="bmx" data-sel="{{ $fecha->format('Y-m') }}" data-max="{{ $bmHoy->format('Y-m') }}" data-url="{{ route('backoffice.informes.index') }}">
        <div class="bmx-bar">
            <a class="bmx-arrow" href="{{ route('backoffice.informes.index', ['mes' => $bmPrev]) }}" title="Mes anterior">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            </a>
            <button type="button" class="bmx-cur" id="bmxCur" aria-expanded="false">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="3"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                <span>{{ $bmMeses[$fecha->month - 1] }} <b>{{ $fecha->year }}</b></span>
                <svg class="bmx-caret" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            @if($bmNext->lte($bmHoy))
                <a class="bmx-arrow" href="{{ route('backoffice.informes.index', ['mes' => $bmNext->format('Y-m')]) }}" title="Mes siguiente">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            @else
                <span class="bmx-arrow is-dis"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></span>
            @endif
        </div>
        @unless($bmEsHoy)
            <a class="bmx-today" href="{{ route('backoffice.informes.index') }}">Volver a {{ mb_strtolower($bmMeses[$bmHoy->month - 1]) }}</a>
        @endunless
        <div class="bmx-pop" id="bmxPop">
            <div class="bmx-yr">
                <button type="button" data-y="-1" aria-label="Año anterior"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg></button>
                <strong id="bmxYear"></strong>
                <button type="button" data-y="1" aria-label="Año siguiente"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></button>
            </div>
            <div class="bmx-grid" id="bmxGrid"></div>
        </div>
    </div>
    <style>
    .bmx{position:relative;display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin:12px 0 22px}
    .bmx-bar{display:inline-flex;align-items:center;gap:4px;padding:5px;background:#fff;border:1px solid #e2e8f0;border-radius:16px;
      box-shadow:0 1px 2px rgba(15,23,42,.04),0 8px 20px -12px rgba(15,23,42,.18)}
    .bmx-arrow{width:38px;height:38px;border-radius:11px;display:grid;place-items:center;color:#475569;transition:background .2s,color .2s}
    .bmx-arrow:hover{background:#f1f5f9;color:#0f172a}
    .bmx-arrow.is-dis{color:#cbd5e1;cursor:not-allowed}
    .bmx-arrow.is-dis:hover{background:none}
    .bmx-cur{display:inline-flex;align-items:center;gap:10px;height:38px;padding:0 14px;border:0;border-radius:11px;background:#f8fafc;
      color:#0f172a;font-size:1rem;font-weight:600;cursor:pointer;font-family:inherit;min-width:190px;justify-content:center;transition:background .2s}
    .bmx-cur:hover,.bmx-cur[aria-expanded="true"]{background:#eff6ff;color:#1d4ed8}
    .bmx-cur:focus{background:#eff6ff}
    .bmx-cur b{font-weight:500;color:#64748b}
    .bmx-caret{transition:transform .25s}
    .bmx-cur[aria-expanded="true"] .bmx-caret{transform:rotate(180deg)}
    .bmx-today{font-size:.88rem;font-weight:600;color:#3B82F6;padding:8px 12px;border-radius:10px;transition:background .2s}
    .bmx-today:hover{background:#eff6ff}
    .bmx-pop{position:absolute;top:calc(100% + 8px);left:0;z-index:50;width:300px;background:#fff;border:1px solid #e2e8f0;border-radius:18px;padding:14px;
      box-shadow:0 24px 48px -16px rgba(15,23,42,.3);opacity:0;transform:translateY(-6px) scale(.98);transform-origin:top left;pointer-events:none;
      transition:opacity .2s,transform .25s cubic-bezier(.34,1.56,.64,1)}
    .bmx-pop.open{opacity:1;transform:none;pointer-events:auto}
    .bmx-yr{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
    .bmx-yr strong{font-size:1.05rem;color:#0f172a}
    .bmx-yr button{width:32px;height:32px;border:0;border-radius:9px;background:transparent;color:#475569;display:grid;place-items:center;cursor:pointer}
    .bmx-yr button:hover:not(:disabled){background:#f1f5f9}
    .bmx-yr button:disabled{color:#cbd5e1;cursor:not-allowed}
    .bmx-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:6px}
    .bmx-grid a,.bmx-grid span{height:40px;border-radius:11px;display:grid;place-items:center;font-size:.9rem;font-weight:500;color:#334155;transition:background .15s,color .15s}
    .bmx-grid a:hover{background:#f1f5f9;color:#0f172a}
    .bmx-grid a.is-now{box-shadow:inset 0 0 0 1.5px #bfdbfe}
    .bmx-grid a.is-sel{background:linear-gradient(135deg,#3B82F6,#2563eb);color:#fff;box-shadow:0 6px 14px -6px rgba(37,99,235,.7)}
    .bmx-grid span{color:#cbd5e1;cursor:not-allowed}
    </style>
    <script>
    (function () {
        var r = document.getElementById('bmx'); if (!r) return;
        var M = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        var sel = r.dataset.sel, max = r.dataset.max, url = r.dataset.url, y = +sel.slice(0, 4), maxY = +max.slice(0, 4);
        var pop = document.getElementById('bmxPop'), cur = document.getElementById('bmxCur'), grid = document.getElementById('bmxGrid');
        var prevB = pop.querySelector('[data-y="-1"]'), nextB = pop.querySelector('[data-y="1"]');
        function draw() {
            document.getElementById('bmxYear').textContent = y;
            nextB.disabled = y >= maxY; prevB.disabled = y <= 2015;
            var h = '';
            for (var i = 0; i < 12; i++) {
                var k = y + '-' + (i < 9 ? '0' : '') + (i + 1);
                if (k > max) h += '<span>' + M[i] + '</span>';
                else h += '<a href="' + url + '?mes=' + k + '" class="' + (k === sel ? 'is-sel ' : '') + (k === max ? 'is-now' : '') + '">' + M[i] + '</a>';
            }
            grid.innerHTML = h;
        }
        function toggle(o) { pop.classList.toggle('open', o); cur.setAttribute('aria-expanded', o); if (o) { y = +sel.slice(0, 4); draw(); } }
        cur.onclick = function (e) { e.stopPropagation(); toggle(!pop.classList.contains('open')); };
        prevB.onclick = function (e) { e.stopPropagation(); y--; draw(); };
        nextB.onclick = function (e) { e.stopPropagation(); y++; draw(); };
        pop.onclick = function (e) { e.stopPropagation(); };
        document.addEventListener('click', function () { toggle(false); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') toggle(false); });
    })();
    </script>
    <div class="divider"></div>
    <div class="row">
        {{-- Informe 1 --}}
        <div class="col s12 m6">
            <div class="card report-card">
                <div class="report-title">10 Bebestibles más consumidos</div>
                <div class="report-subtitle">Mes actual</div>
                <div class="placeholder-chart">  
                    <canvas id="graficoBebestibles"></canvas>
                </div>
                <ul class="collection">
                    @foreach ($bebestiblesMasConsumidos as $index => $bebestible)
                    
                    <li class="collection-item">{{$index+1}}. {{$bebestible->nombre}} - Cantidad:{{$bebestible->total}}</li>
                        
                    @endforeach
                    {{-- <li class="collection-item">2. Jugo Natural</li>
                    <li class="collection-item">3. Cerveza Artesanal</li> --}}
                </ul>
            </div>
        </div>

        {{-- Informe 2 --}}
        <div class="col s12 m6">
            <div class="card report-card">
                <div class="report-title">10 Programas más contratados</div>
                <div class="report-subtitle">Mes actual</div>
                <div class="placeholder-chart">
                    <canvas id="graficoProgramas"></canvas>
                </div>
                <ul class="collection">
                    {{-- <li class="collection-item">1. Programa Relax</li> --}}
                    @foreach ($programasMasContratados as $index=>$programa)
                    <li class="collection-item">{{$index+1}}. {{$programa->programa->nombre_programa}} - Cantidad: {{$programa->total}}</li>
                    @endforeach
                    {{-- <li class="collection-item">2. Programa Familiar</li>
                    <li class="collection-item">3. Programa Premium</li> --}}
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@section('foot')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function(){
    $.get("{{ route('backoffice.informes.bebestibles', ['anio' => $fecha->year]) }}", function(response){
        var ctx = document.getElementById('graficoBebestibles').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: response.labels,
                datasets: [{
                    label: 'Bebestibles consumidos por mes',
                    data: response.data,
                    backgroundColor: 'rgba(2, 123, 123, 0.2)',
                    borderColor: 'rgba(2, 123, 123, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    });
});
</script>

<script>
$(document).ready(function(){
    $.get("{{ route('backoffice.informes.programas', ['anio' => $fecha->year]) }}", function(response){
        var ctx = document.getElementById('graficoProgramas').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: response.labels,
                datasets: [{
                    label: 'Programas consumidos por mes',
                    data: response.data,
                    backgroundColor: 'rgba(2, 123, 123, 0.2)',
                    borderColor: 'rgba(2, 123, 123, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    });
});
</script>

@endsection
