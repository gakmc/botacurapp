@extends('themes.backoffice.layouts.admin')

@section('title', 'Egresos')

@section('head')
@endsection


@section('breadcrumbs')
<li><a href="{{route('backoffice.egreso.index')}}">Egresos Anuales</a></li>
<li>Egresos {{ucfirst(\Carbon\Carbon::create()->month($mes)->year($anio)->locale('es')->isoFormat('MMMM [de] YYYY'))}}</li>
@endsection

@section('dropdown_settings')
<li><a href="{{ route('backoffice.egreso.create') }}" class="grey-text text-darken-2">Crear Egreso</a></li>
@endsection

@section('content')
<div class="section">
    <p class="caption">Listado de Egresos <strong>{{ucfirst(\Carbon\Carbon::create()->month($mes)->year($anio)->locale('es')->isoFormat('MMMM [de] YYYY'))}}</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">


  {{-- <h5 class="center-align">Total Mensual:</h5> --}}

                {{-- <div class="row">
                  <div class="col s10 m3">
                    <div class="card-panel gradient-45deg-light-blue-cyan gradient-shadow center">
                      <span class="white-text"><strong>Neto: </strong>${{ number_format($totalMes['neto'], 0, ',', '.') }}</span>
                    </div>
                  </div>
                  <div class="col s10 m3">
                    <div class="card-panel gradient-45deg-light-blue-cyan gradient-shadow center">
                      <span class="white-text"><strong>IVA (19%): </strong>${{ number_format($totalMes['iva'], 0, ',', '.') }}</span>
                    </div>
                  </div>
                  <div class="col s10 m3">
                    <div class="card-panel gradient-45deg-light-blue-cyan gradient-shadow center">
                      <span class="white-text"><strong>Impuesto Adicional: </strong>${{ number_format($totalMes['impuesto_incluido'], 0, ',', '.') }}</span>
                    </div>
                  </div>
                  <div class="col s10 m3">
                    <div class="card-panel gradient-45deg-light-blue-cyan gradient-shadow center">
                      <span class="white-text"><strong>Total: </strong>${{ number_format($totalMes["total"], 0, ',', '.') }}</span>
                    </div>
                  </div>


                </div> --}}

      <div class="divider" style="margin: 40px 0;"></div>
        <div class="row">
            <div class="col s12 ">
                <div class="card-panel">
                    <div class="row">
                      <h5 class="">Gastos Fijos:</h5>



        <form action="{{ route('backoffice.egreso.pago_fijo') }}" method="POST" id="form-pagos">
          @csrf


          <table class="striped responsive-table">
            <thead>
              <tr>
                <th>Tipo</th>
                <th>Categoría</th>

                <th>Proveedor</th>
                <th>Neto</th>
                <th>IVA</th>
                <th>Monto Base</th>
                <th>Monto Pagado</th>
                <th>Fecha Pago</th>
                <th>Acciones</th>
                @php
                  // Checkbox “seleccionar todos” solo si hay fijos SIN pago en el período
                  $hayPendientesPeriodo = $fijos->contains(function($egreso) {
                      return $egreso->pagos->isEmpty(); // pagos ya vienen filtrados por mes/año desde el controlador
                  });
                @endphp
                <th>Seleccionar
                  @if($hayPendientesPeriodo)
                    <label style="margin-left:8px;">
                      <input type="checkbox" class="filled-in" id="chk-all"/>
                      <span></span>
                    </label>
                  @endif
                </th>
              </tr>
            </thead>
            <tbody>
              @forelse($fijos as $e)
                    @php
                      $pagosMes    = $e->pagos;           // YA VIENEN del mes/año
                      $totalPagado = $pagosMes->sum('monto');
                    @endphp
                <tr>
                  <td>{{ $e->tipo_documento->nombre ?? '-' }}</td>

                  <td>{{ $e->subcategoria->nombre ?? '-' }}</td>
                  <td>{{ $e->proveedor->nombre ?? '-' }}</td>
                  <td>${{ number_format($e->neto, 0, ',', '.') }}</td>
                  <td>${{ number_format($e->iva, 0, ',', '.') }}</td>
                  <td><strong>${{ number_format($e->total, 0, ',', '.') }}</strong></td>

                  {{-- <td>
                    @if ($pagosMes->isNotEmpty())
                      @foreach($pagosMes as $pago)
                        <strong>${{ number_format($pago->monto, 0, ',', '.') }}</strong><br>
                      @endforeach

                    @else

                            <input type="text" name="monto_pagado[{{$e->id}}]" id="monto_pagado[{{$e->id}}]" required value="${{ number_format($e->total, 0, ',', '.') }}">
                      
                    @endif
                  </td>
                  <td>

                    @if($pagosMes->isNotEmpty())
                      @foreach($pagosMes as $pago)
                        <strong>{{ \Carbon\Carbon::parse($pago->fecha_pago)->format('d-m-Y') }}</strong><br>
                      @endforeach
                    @else
                      -
                    @endif
                  </td> --}}


                  <td>
                    @if ($totalPagado > 0)
                      @foreach($pagosMes as $pago)
                        <strong>${{ number_format($pago->monto, 0, ',', '.') }}</strong><br>
                      @endforeach
                    @else
                      <input type="text" name="monto_pagado[{{$e->id}}]" id="monto_pagado[{{$e->id}}]"
                            required value="${{ number_format($e->total, 0, ',', '.') }}">
                    @endif
                  </td>

                  <td>
                    @if($pagosMes->isNotEmpty())
                      @foreach($pagosMes as $pago)
                        <strong>{{ \Carbon\Carbon::parse($pago->fecha_pago)->format('d-m-Y') }}</strong><br>
                      @endforeach
                    @else
                      -
                    @endif
                  </td>

                  <td>
                    <a href="{{ route('backoffice.egreso.edit', $e->id) }}" class="btn-floating btn-small purple">
                      <i class='material-icons'>edit</i>
                    </a>

                      <button type="button"
                              class="btn-floating btn-small red btn-delete-egreso"
                              data-action="{{ route('backoffice.egreso.destroy', $e->id) }}"
                              data-label="{{ $e->subcategoria->nombre ?? 'Egreso' }}">
                        <i class="material-icons">delete_forever</i>
                      </button>
  
                  </td>

                  <td>
                    @if($pagosMes->isEmpty())
                      <label>
                        <input type="checkbox" class="filled-in chk-egreso" name="egresos[]" value="{{ $e->id }}"/>
                        <span></span>
                      </label>
                    @else
                      <span class="chip" style="background:#e8f5e9;color:#2e7d32">
                        <i class="material-icons tiny" style="vertical-align:middle;margin-right:4px">done</i> Pagado
                      </span>
                    @endif
                  </td>

                </tr>
              @empty
                <tr>
                  <td colspan="11" class="center-align"><h5>No se registran egresos fijos</h5></td>
                </tr>
              @endforelse
            </tbody>
          </table>

          <div class="section">
            <button type="submit" id="btn-pagar" class="btn waves-effect right" disabled>
              Pagar seleccionados
              <i class="material-icons right">send</i>
            </button>
          </div>
        </form>




                    </div>



              </div>
            </div>
        </div>


      <div class="divider" style="margin: 40px 0;"></div>
        <div class="row">
            <div class="col s12 ">
                <div class="card-panel">
                    <div class="row">
                      <h5 class="">Gastos Variables:</h5>



          <form action="{{ route('backoffice.egreso.pago_variable') }}" method="POST" id="form-pagos-variables">
            @csrf

            <table class="striped responsive-table">
              <thead>
                <tr>
                  <th>Tipo</th>
                  <th>Categoría</th>
                  <th>Proveedor</th>
                  <th>Folio</th>
                  <th>Neto</th>
                  <th>IVA</th>
                  <th>Impuesto incluido</th>
                  {{-- <th>Total</th> --}}
                  <th>Acciones</th>
                  <th>Pagar

                      <label style="margin-left:8px;">
                        <input type="checkbox" class="filled-in" id="chk-all-var"/>
                        <span></span>
                      </label>

                  </th>
                </tr>
              </thead>

              <tbody>
                @php $totalTabla = 0; @endphp

                @forelse ($variables as $e)
                  @php
                    // $pagos = $pagosPorEgreso[$e->id]['pagos'] ?? []; // historial si lo necesitas mostrar
                    $valor = 0;
                    $pagos = $e->pagos;
                    $montoPagado = $pagosPorEgreso[$e->id]['monto_pagado'] ?? 0;
                    $pendiente   = max(($e->total - $montoPagado), 0);
                    $totalTabla += $e->total;
                  @endphp

                  <tr>
                    <td>{{ $e->tipo_documento->nombre ?? '-' }}</td>
                    <td>{{ $e->subcategoria->nombre ?? '-' }}</td>
                    <td>{{ $e->proveedor->nombre ?? '-' }}</td>
                    <td>
                      {{-- {{ $e->folio ?? '-' }} --}}
                            <input type="text"
                                  name="items[{{ $e->id }}][folio]"
                                  value="{{ $e->folio }}"
                                  data-id="{{ $e->id }}"
                                  class="row-input">

                    </td>

                    <td>
                      {{-- ${{ number_format($e->neto, 0, ',', '.') }} --}}
                                  <input type="text"
                                  name="items[{{ $e->id }}][neto]"
                                  value="{{ $e->neto ?? 0 }}"
                                  data-id="{{ $e->id }}"
                                  class="row-input input-neto">
                    </td>
                    <td>
                      {{-- ${{ number_format($e->iva, 0, ',', '.') }} --}}

                                  <input type="text"
                                  name="items[{{ $e->id }}][iva]"
                                  value="{{ $e->iva ?? 0 }}"
                                  data-id="{{ $e->id }}"
                                  class="row-input input-iva">
                    </td>

                    @php
                      $sub = Str::ascii($e->subcategoria->nombre ?? '');
                      $sub = Str::lower($sub);
                      $permitidas = ['carnes', 'cervezas', 'cerveza', 'botilleria'];
                      $mostrar = in_array($sub, $permitidas, true);
                    @endphp

                    <td>
                      {{-- ${{ number_format($e->iva, 0, ',', '.') }} --}}
                      @if ($mostrar)
                        <input type="text"
                        name="items[{{ $e->id }}][impuesto_incluido]"
                        value="{{ $e->impuesto_incluido ?? 0 }}"
                        data-id="{{ $e->id }}"
                        class="row-input input-imp">
                        
                      @else
                        <span class="grey-text">—</span>
                      @endif
                    </td>
                    {{-- <td><strong>${{ number_format($e->total, 0, ',', '.') }}</strong></td> --}}

                    <td>
                      <a href="{{ route('backoffice.egreso.edit', $e->id) }}" class="btn-floating btn-small purple">
                        <i class="material-icons">edit</i>
                      </a>

                        <button type="button"
                                class="btn-floating btn-small red btn-delete-egreso"
                                data-action="{{ route('backoffice.egreso.destroy', $e->id) }}"
                                data-label="{{ $e->subcategoria->nombre ?? 'Egreso' }}">
                          <i class="material-icons">delete_forever</i>
                        </button>
                    </td>

                    <td>
                      @if($pendiente > 0)
                        @php 
                          if (isset($pagos)) {
                              foreach ($pagos as $pago) {
                                  $valor += $pago->monto;
                              }
                          }
                        @endphp
                        <div class="row" style="margin-bottom:0">
                          <div class="col s7">
                            <input type="text"
                                  name="items[{{ $e->id }}][monto]"
                                  value="${{ number_format($pendiente,0,',','.') }}"
                                  class="row-input input-monto"
                                  data-id="{{ $e->id }}"
                                  style="height:32px;padding:0 6px;">
                            <small>
                              Base: ${{ number_format($pendiente,0,',','.') }}
                              / 
                              Pagado: @if ($valor > 0)
                              <a class="waves-effect waves-light modal-trigger" href="#modal-{{$e->id}}">${{ number_format($valor,0,',','.') }} </a>

                              @else
                                ${{ number_format($valor,0,',','.') }}
                              @endif
                            </small>
                          </div>
                          <div class="col s5">
                            <label>
                              <input type="checkbox"
                                    class="checkbox-pago"
                                    name="items[{{ $e->id }}][check]"
                                    data-id="{{ $e->id }}">
                              <span>Pagar</span>
                            </label>
                          </div>
                        </div>
                      @else
                        <span class="tooltipped" data-position="bottom" data-tooltip="Sin saldo pendiente">
                          <i class="material-icons tiny" style="color:#039B7B">check_circle</i> Al día
                        </span>
                      @endif
                    </td>
                  </tr>
                    <div id="modal-{{$e->id}}" class="modal bottom-sheet">
                      <div class="modal-content">
                        <h4>Pagos realizados en el mes</h4>
                        <ul class="collection">
                            @foreach ($pagos as $pago)
                            <li class="collection-item avatar">
                              <i class="material-icons circle green">monetization_on</i>
                              <span class="title">{{$e->subcategoria->nombre}}</span>
                              <p>{{$pago->fecha_pago->format('d-m-Y')}} <br>
                                Neto: ${{number_format($pago->neto,0,'','.')}} /
                                IVA (19%): ${{number_format($pago->iva,0,'','.')}} /
                                Total: ${{number_format($pago->monto,0,'','.')}}
                              </p>
                              {{-- <a href="#!" class="secondary-content"><i class="material-icons">grade</i></a> --}}
                            </li>
                            @endforeach


                        </ul>
                      </div>
                      <div class="modal-footer">
                        <a href="#!" class="modal-action modal-close waves-effect waves-green btn-flat">Aceptar</a>
                      </div>
                    </div>
                @empty
                  <tr>
                    <td colspan="10" class="center-align"><h5>No se registran egresos Variables</h5></td>
                  </tr>
                @endforelse
              </tbody>
            </table>

            <div class="right-align" style="margin-top:10px">
              <span class="mr-2" id="contador-var">0 seleccionados - $0</span>
              <button type="submit" id="btn-registrar-var" class="btn waves-effect waves-light" disabled>
                Registrar pagos <i class="material-icons right">monetization_on</i>
              </button>
            </div>
          </form>



                    </div>



              </div>
            </div>
        </div>
</div>

  <form id="form-eliminar-global" method="POST" style="display:inline;">
    @csrf 
    @method('DELETE')
  </form>
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
    function confirmarYEliminar(action, label) {
        Swal.fire({
            title: '¿Eliminar este egreso?',
            text: "Esta acción no se puede deshacer."+ (label ? ' ('+label+')' : ''),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
              var $form = document.getElementById('form-eliminar-global');
              $form.setAttribute('action', action);
              $form.submit();
            }
        });
    }


      // Delegación para todos los botones de borrar
      $(document).on('click', '.btn-delete-egreso', function () {
        const action = $(this).data('action');
        const label  = $(this).data('label') || '';
        confirmarYEliminar(action, label);
      });
</script>


<script>
  $(function () {
    var $btn = $('#btn-pagar');

    function toggleBtn() {
      var anyChecked = $('.chk-egreso:checked').length > 0;
      $btn.prop('disabled', !anyChecked);
    }

    // Marcar / desmarcar todos
    $('#chk-all').on('change', function(){
      $('.chk-egreso').prop('checked', this.checked);
      toggleBtn();
    });

    // Cambios individuales
    $(document).on('change', '.chk-egreso', toggleBtn);

    // Inicial
    toggleBtn();
  });
</script>




<script>
  $(function () {
    const $btn     = $('#btn-registrar-var');
    const $countEl = $('#contador-var');

    const toInt = s => {
      const n = (s||'').toString().replace(/\D+/g,''); // quita $, ., comas, espacios
      return n ? parseInt(n,10) : 0;
    };
    const fmtCLP = n => '$' + new Intl.NumberFormat('es-CL').format(n||0);

    // Enmascara y retorna entero
    function maskInput($inp){
      const val = toInt($inp.val());
      $inp.val(fmtCLP(val));
      return val;
    }

    // Habilita/inhabilita inputs de una fila
    function toggleRowInputs(id, enabled){
      $(`.row-input[data-id="${id}"]`).prop('disabled', !enabled);
      // Si habilitamos, refrescamos monto por si cambió algo
      if (enabled) updateMontoForRow(id);
    }

    // Suma neto + iva + impuesto_incluido (si existe) y deja el total en "monto"
    function updateMontoForRow(id){
      const $neto = $(`.input-neto[data-id="${id}"]`);
      const $iva  = $(`.input-iva[data-id="${id}"]`);
      const $imp  = $(`.input-imp[data-id="${id}"]`); // puede no existir
      const $monto = $(`.input-monto[data-id="${id}"]`);

      const vNeto = $neto.length ? maskInput($neto) : 0;
      const vIva  = $iva.length  ? maskInput($iva)  : 0;
      const vImp  = $imp.length  ? maskInput($imp)  : 0;

      const total = Math.max(vNeto + vIva + vImp, 0);
      $monto.val(fmtCLP(total));
    }

    // Recalcula el resumen global (seleccionados + total)
    function calcResumen() {
      let cant = 0, total = 0;
      $('.checkbox-pago:checked').each(function () {
        const id = $(this).data('id');
        // asegura que monto esté bien antes de sumar
        updateMontoForRow(id);
        const val = toInt($(`.input-monto[data-id="${id}"]`).val());
        if (val > 0) { cant++; total += val; }
      });
      $btn.prop('disabled', cant === 0 || total <= 0);
      $countEl.text(`${cant} seleccionados - ${fmtCLP(total)}`);
    }

    // Eventos de cambio en neto/iva/imp => recalcula monto y resumen
    $(document).on('input', '.input-neto, .input-iva, .input-imp', function () {
      const id = $(this).data('id');
      updateMontoForRow(id);
      calcResumen();
    });

    // Usuario edita "monto" directamente: enmascara y actualiza resumen
    $(document).on('input', '.input-monto', function () {
      maskInput($(this));
      calcResumen();
    });

    // Seleccionar todos
    $('#chk-all-var').on('change', function () {
      $('.checkbox-pago').prop('checked', this.checked).trigger('change');
    });

    // Toggle individual: habilitar/inhabilitar fila y recalcular
    $(document).on('change', '.checkbox-pago', function () {
      const id = $(this).data('id');
      toggleRowInputs(id, this.checked);
      calcResumen();
    });

    // Máscara inicial a todos los inputs con monto/neto/iva/imp
    $('.input-neto, .input-iva, .input-imp, .input-monto').each(function(){ maskInput($(this)); });

    // Al cargar, deshabilita filas no marcadas
    $('.checkbox-pago').each(function(){ toggleRowInputs($(this).data('id'), this.checked); });

    // Antes de enviar: máscara final (el backend igualmente limpia)
    $('#form-pagos-variables').on('submit', function(){
      $('.input-neto, .input-iva, .input-imp, .input-monto').each(function(){ maskInput($(this)); });
    });

    calcResumen();
  });
</script>



<script>
  $(document).ready(function(){
    $('.modal').modal();
  });
</script>



@endsection