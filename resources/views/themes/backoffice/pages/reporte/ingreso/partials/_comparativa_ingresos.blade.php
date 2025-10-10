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
@endif
