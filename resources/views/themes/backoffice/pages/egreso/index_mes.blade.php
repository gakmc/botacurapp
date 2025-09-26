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



        {{-- <form action="{{route('backoffice.egreso.pago.store')}}" method="POST">

                      <table class="striped responsive-table">
                        <thead>
                          <tr>
                            <th>Tipo</th>
                            <th>Categoría</th>
                            <th>Subcategoría</th>
                            <th>Proveedor</th>
                            <th>Neto</th>
                            <th>IVA</th>
                            <th>Monto Base</th>
                            <th>Monto Pagado</th>
                            <th>Fecha Pago</th>
                            <th>Acciones</th>
                          </tr>
                        </thead>
                        <tbody>
                          @if ($fijos->isNotEmpty())
                          @foreach ($fijos as $e)
                            <tr>
                              <td>{{ $e->tipo_documento->nombre ?? '-' }}</td>
                              <td>{{ $e->categoria->nombre ?? '-' }}</td>
                              <td>{{ $e->subcategoria->nombre ?? '-' }}</td>
                              <td>{{ $e->proveedor->nombre ?? '-' }}</td>

                              <td>${{ number_format($e->neto, 0, ',', '.') }}</td>
                              <td>${{ number_format($e->iva, 0, ',', '.') }}</td>
                              <td><strong>${{ number_format($e->total, 0, ',', '.') }}</strong></td>
                              <td><strong>${{ number_format($e->total, 0, ',', '.') }}</strong></td>
                              <td>    
                                @php
                                    $pagosMes = $e->pagos->whereBetween('fecha_pago', [
                                        now()->startOfMonth(),
                                        now()->endOfMonth()
                                    ]);
                                @endphp

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
                                <form id="form-eliminar-{{$e->id}}" action="{{ route('backoffice.egreso.destroy', $e->id) }}" method="POST" style="display:inline;">
                                  @csrf 
                                  @method('DELETE')
                                  <button type="button" class="btn-floating btn-small red" onclick="confirmarEliminacion({{$e->id}})"><i class='material-icons'>delete</i></button>
                                </form>

                              </td>
                            </tr>
                          @endforeach

                          @else
                            <tr>
                              <td colspan="2"></td>
                              <td><h5>No se registran egresos fijos</h5></td>
                            </tr>
                          @endif
                        </tbody>
                      </table>

        </form> --}}


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
                <th>Seleccionar
                  @if($fijos->contains(function($egreso) {
                          return $egreso->pagos->whereBetween('fecha_pago', [now()->startOfMonth(), now()->endOfMonth()])->isEmpty();
                        }))
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
                      $pagosMes = $e->pagos->whereBetween('fecha_pago', [now()->startOfMonth(), now()->endOfMonth()]);
                    @endphp
                <tr>
                  <td>{{ $e->tipo_documento->nombre ?? '-' }}</td>

                  <td>{{ $e->subcategoria->nombre ?? '-' }}</td>
                  <td>{{ $e->proveedor->nombre ?? '-' }}</td>
                  <td>${{ number_format($e->neto, 0, ',', '.') }}</td>
                  <td>${{ number_format($e->iva, 0, ',', '.') }}</td>
                  <td><strong>${{ number_format($e->total, 0, ',', '.') }}</strong></td>
                  <td>
                    @if ($pagosMes->isNotEmpty())
                      @foreach($pagosMes as $pago)
                        <strong>${{ number_format($pago->monto, 0, ',', '.') }}</strong><br>
                      @endforeach

                    @else
                      {{-- <strong>${{ number_format($e->total, 0, ',', '.') }}</strong> --}}

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
                  </td>
                  <td>
                    <a href="{{ route('backoffice.egreso.edit', $e->id) }}" class="btn-floating btn-small purple">
                      <i class='material-icons'>edit</i>
                    </a>

                    {{-- SUGERENCIA: evita forms anidados dentro de este form principal. 
                        Mueve este form de eliminar fuera del <form id="form-pagos"> o usa un modal de confirmación. --}}
                    {{-- <form id="form-eliminar-{{$e->id}}" ...> --}}
                  </td>
                  {{-- <td>
                    <label>
                      <input type="checkbox" class="filled-in chk-egreso" name="egresos[]" value="{{ $e->id }}"/>
                      <span></span>
                    </label>
                  </td> --}}

                  <td>
                    @if($pagosMes->isEmpty())
                      <label>
                        <input type="checkbox" class="filled-in chk-egreso" name="egresos[]" value="{{ $e->id }}"/>
                        <span></span>
                      </label>
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



                      {{-- <table class="striped responsive-table">
                        <thead>
                          <tr>
                            <th>Tipo</th>
                            <th>Categoría</th>
                            <th>Subcategoría</th>
                            <th>Proveedor</th>
                            <th>Folio</th>
                            <th>Neto</th>
                            <th>IVA</th>
                            <th>Total</th>
                            <th>Acciones</th>
                            <th>Pagar</th>
                          </tr>
                        </thead>
                        <tbody>
                          @php $totalTabla = 0; @endphp
                          @if ($variables->isNotEmpty())
                          @foreach ($variables as $e)
                            @php
                              $esFactura = isset($e->tipo_documento) && strcasecmp($e->tipo_documento->nombre,'Factura')===0;
                              $pagos = $pagosPorEgreso[$e->id]['pagos'] ?? []; // array de pagos previos
                              $montoPagado = $pagosPorEgreso[$e->id]['monto_pagado'] ?? 0;
                              $yaPagadoFijoMes = $bloqueosFijoMes[$e->subcategoria_id] ?? false; // para fijos
                              $pendiente = max(($e->total - $montoPagado),0);
                              $totalTabla += $e->total;
                            @endphp
                            <tr>
                              <td>{{ $e->tipo_documento->nombre ?? '-' }}</td>
                              <td>{{ $e->categoria->nombre ?? '-' }}</td>
                              <td>{{ $e->subcategoria->nombre ?? '-' }}</td>
                              <td>{{ $e->proveedor->nombre ?? '-' }}</td>
                              <td>{{ $e->folio ?? '-' }}</td>

                              <td>${{ number_format($e->neto, 0, ',', '.') }}</td>
                              <td>${{ number_format($e->iva, 0, ',', '.') }}</td>
                              <td><strong>${{ number_format($e->total, 0, ',', '.') }}</strong></td>
                              <td>
                                
                                <a href="{{ route('backoffice.egreso.edit', $e->id) }}" class="btn-floating btn-small purple">
                                  <i class='material-icons'>edit</i>
                                </a>
                                <form id="form-eliminar-{{$e->id}}" action="{{ route('backoffice.egreso.destroy', $e->id) }}" method="POST" style="display:inline;">
                                  @csrf 
                                  @method('DELETE')
                                  <button type="button" class="btn-floating btn-small red" onclick="confirmarEliminacion({{$e->id}})"><i class='material-icons'>delete</i></button>
                                </form>

                              </td>


                              <td>
                                @if($e->categoria->nombre === 'Gastos Fijos')
                                  @if($yaPagadoFijoMes)
                                    <span class="tooltipped" data-position="bottom" data-tooltip="Pagado este mes">
                                      <i class="material-icons tiny" style="color:#039B7B">monetization_on</i> Pagado
                                    </span>
                                  @else
                                    <div class="row" style="margin-bottom:0">
                                      <div class="col s7">
                                        <input type="number" min="0" step="1" name="items[{{ $e->id }}][monto]" value="{{ $e->total }}" class="browser-default" style="height:32px;padding:0 6px;">
                                      </div>
                                      <div class="col s5">
                                        <label>
                                          <input type="checkbox" class="checkbox-pago" name="items[{{ $e->id }}][check]" data-total="{{ $e->total }}" data-tipo="fijo">
                                          <span>Pagar</span>
                                        </label>
                                      </div>
                                    </div>
                                  @endif
                                @else
                                  <div class="row" style="margin-bottom:0">
                                    <div class="col s7">
                                      <input type="number" min="0" step="1" name="items[{{ $e->id }}][monto]" value="{{ $pendiente }}" class="browser-default" style="height:32px;padding:0 6px;">
                                      <small>Pagado: ${{ number_format($montoPagado,0,',','.') }} / Pend.: ${{ number_format($pendiente,0,',','.') }}</small>
                                    </div>
                                    <div class="col s5">
                                      <label>
                                        <input type="checkbox" class="checkbox-pago" name="items[{{ $e->id }}][check]" data-total="{{ $pendiente }}" data-tipo="variable">
                                        <span>Pagar</span>
                                      </label>
                                    </div>
                                  </div>
                                @endif
                              </td>
                            </tr>
                          @endforeach

                          @else
                            <tr>
                              <td colspan="2"></td>
                              <td><h5>No se registran egresos Variables</h5></td>
                            </tr>
                          @endif
                        </tbody>
                      </table>


                                <div class="right-align" style="margin-top:10px">
            <span class="mr-2" id="contador-">0 seleccionados - $0</span>
            <button type="submit" class="btn waves-effect waves-light">
              Registrar pagos <i class="material-icons right">monetization_on</i>
            </button>
          </div> --}}


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
                    $pagos = $pagosPorEgreso[$e->id]['pagos'] ?? []; // historial si lo necesitas mostrar
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
                      {{-- <form id="form-eliminar-{{$e->id}}" action="{{ route('backoffice.egreso.destroy', $e->id) }}" method="POST" style="display:inline;">
                        @csrf @method('DELETE')
                        <button type="button" class="btn-floating btn-small red" onclick="confirmarEliminacion({{$e->id}})">
                          <i class="material-icons">delete</i>
                        </button>
                      </form> --}}
                    </td>

                    <td>
                      @if($pendiente > 0)
                        <div class="row" style="margin-bottom:0">
                          <div class="col s7">
                            <input type="text"
                                  name="items[{{ $e->id }}][monto]"
                                  value="${{ number_format($pendiente,0,',','.') }}"
                                  class="row-input input-monto"
                                  data-id="{{ $e->id }}"
                                  style="height:32px;padding:0 6px;">
                            <small>Pagado: ${{ number_format($montoPagado,0,',','.') }} / Pend.: ${{ number_format($pendiente,0,',','.') }}</small>
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
    function confirmarEliminacion(id) {
        Swal.fire({
            title: '¿Eliminar este egreso?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('form-eliminar-' + id).submit();
            }
        });
    }
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







@endsection