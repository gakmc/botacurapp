{{-- @if($rows->count())
  <table class="striped responsive-table">
    <thead>
      <tr>
        <th>Mes</th>
        <th class="right-align">Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach($rows as $r)
        <tr>
          <td>{{ $r['label'] }}</td>
          <td class="right-align">${{ number_format($r['total'], 0, ',', '.') }}</td>
        </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <th>Total comparado</th>
        <th class="right-align">${{ number_format($rows->sum('total'), 0, ',', '.') }}</th>
      </tr>
    </tfoot>
  </table>

  @if($rows->count() >= 2)
    @php
      $base = $rows->first()['total'] ?: 0;
      $ultimo = $rows->last()['total'] ?: 0;
      $diff = $ultimo - $base;
      $pct  = $base ? ($diff / $base * 100) : null;
    @endphp
    <p class="grey-text" style="margin-top:10px;">
      Variación entre {{ $rows->first()['label'] }} y {{ $rows->last()['label'] }}:
      <strong>
        {{ $diff >= 0 ? '+' : '' }}${{ number_format($diff, 0, ',', '.') }}
        {{ !is_null($pct) ? '(' . number_format($pct, 2, ',', '.') . '%)' : '' }}
      </strong>
    </p>
  @endif
@else
  <p class="grey-text">Sin datos para los meses seleccionados.</p>
@endif --}}




@php
  $labels = $rows->pluck('label')->values();
  $data   = $rows->pluck('total')->values();
@endphp

@if($rows->count())
  <table class="striped responsive-table">
    <thead>
      <tr>
        <th>Mes</th>
        <th class="right-align">Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach($rows as $r)
        <tr>
          <td>{{ $r['label'] }}</td>
          <td class="right-align">${{ number_format($r['total'], 0, ',', '.') }}</td>
        </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <th>Total comparado</th>
        <th class="right-align">${{ number_format($rows->sum('total'), 0, ',', '.') }}</th>
      </tr>
    </tfoot>
  </table>

  {{-- Gráfico --}}
  <div style="margin-top:16px">
    <canvas id="chartComparativa" height="160"></canvas>
  </div>

  @if($rows->count() >= 2)
    @php
      $base = $rows->first()['total'] ?: 0;
      $ultimo = $rows->last()['total'] ?: 0;
      $diff = $ultimo - $base;
      $pct  = $base ? ($diff / $base * 100) : null;
    @endphp
    <p class="grey-text" style="margin-top:10px;">
      Variación entre {{ $rows->first()['label'] }} y {{ $rows->last()['label'] }}:
      <strong>
        {{ $diff >= 0 ? '+' : '' }}${{ number_format($diff, 0, ',', '.') }}
        {{ !is_null($pct) ? '(' . number_format($pct, 2, ',', '.') . '%)' : '' }}
      </strong>
    </p>
  @endif

  <script>
  (function(){
    // Evita doble inicialización al reabrir el modal
    if (window._cmpChart) { try { window._cmpChart.destroy(); } catch(e) {} }

    var ctx = document.getElementById('chartComparativa').getContext('2d');
    var labels = {!! $labels->toJson(JSON_UNESCAPED_UNICODE) !!};
    var data   = {!! $data->toJson() !!};

    window._cmpChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: 'Ingresos',
          data: data,
          // sin colores específicos (simple y legible)
          borderColor: '#039B7B',
          // backgroundColor: '#039B7B',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        legend: { display: false },
        scales: {
          yAxes: [{
            ticks: {
              beginAtZero: true,
              callback: function(v){ return '$' + v.toLocaleString('es-CL'); }
            }
          }]
        },
        tooltips: {
          callbacks: {
            label: function(tooltipItem){
              var v = tooltipItem.yLabel || 0;
              return ' $' + (v+'').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }
          }
        }
      }
    });
  })();
  </script>
@else
  <p class="grey-text">Sin datos para los meses seleccionados.</p>
@endif
