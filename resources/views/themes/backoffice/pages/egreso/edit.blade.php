@extends('themes.backoffice.layouts.admin')

@section('title', 'Modificar Egreso')

@section('head')
<link rel="stylesheet" href="{{ asset('assets/pickadate/lib/themes/default.css') }}">
<link rel="stylesheet" href="{{ asset('assets/pickadate/lib/themes/default.date.css') }}">
<link rel="stylesheet" href="{{ asset('assets/pickadate/lib/themes/default.time.css') }}">
@endsection


@section('breadcrumbs')
<li><a href="{{route('backoffice.egreso.index')}}">Egresos</a></li>
<li>Modificando Egreso</li>
@endsection

@section('dropdown_settings')
{{-- <li><a href="{{ route('backoffice.egreso.create') }}" class="grey-text text-darken-2">Crear Egreso</a></li> --}}
@endsection


@section('content')
<div class="section">
    <p class="caption"><strong>Egresos</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 m8 offset-m2 ">
                <div class="card-panel">
                    <h4 class="header2">Midificar Egreso</h4>
                    <div class="row">



                        <form class="col s12" method="POST" action="{{ route('backoffice.egreso.update',$egreso) }}">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <div class="input-field col s12 m6">
                                    <select name="categoria_id" id="categoria_id">
                                        {{-- <option value="" selected>Seleccione una categoria</option> --}}
                                        @foreach ($categorias as $categoria)
                                            <option value="{{ $categoria->id }}" @if ($egreso->categoria_id == $categoria->id) selected @endif>{{ $categoria->nombre }}</option>
                                        @endforeach
                                    </select>
                                    <label for="categoria_id" class="black-text">Categoria</label>

                                    @error('categoria')
                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color: red;">
                                                {{ $message }}
                                            </strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="input-field col s12 m6">
                                    <input type="text" name="monto" id="monto" class="validate" value="${{number_format($egreso->monto,0,'','.')}}" required>
                                    <label for="monto" class="black-text">Monto</label>

                                    @error('monto')
                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color: red;">
                                                {{ $message }}
                                            </strong>
                                        </span>
                                    @enderror
                                </div>

                            </div>

                            <div class="row">
                                <div class="input-field col s12 m6">
                                    <input type="text" name="fecha" id="fecha" value="{{ \Carbon\Carbon::parse($egreso->fecha)->format('d-m-Y') }}" required>

                                    <label for="fecha" class="black-text">Fecha</label>

                                    @error('fecha')
                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color: red;">
                                                {{ $message }}
                                            </strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>


                            <div class="row">
                              <div class="input-field col s12">
                                <button class="btn waves-effect waves-light right" type="submit">Actualizar
                                  <i class="material-icons right">send</i>
                                </button>
                              </div>
                            </div>
                        </form>



                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('foot')
<script src="{{ asset('assets/pickadate/lib/picker.js') }}"></script>
<script src="{{ asset('assets/pickadate/lib/picker.date.js') }}"></script>
<script src="{{ asset('assets/pickadate/lib/picker.time.js') }}"></script>

<script>
  $(document).ready(function () {

    $('#fecha').pickadate({
      format: 'dd-mm-yyyy',
      formatSubmit: 'yyyy-mm-dd',
      hiddenName: true
    })

  });
</script>


<script>
    function formatCLP(number) {
        number = number.toString().replace(/\D/g, ''); // Elimina todo lo que no sea dígito
        return number ? '$' + parseInt(number, 10).toLocaleString('es-CL') : '';
    }

    $('#monto').on('input', function () {
        const cursorPos = this.selectionStart;
        const rawValue = $(this).val().replace(/\D/g, '');
        const formatted = formatCLP(rawValue);
        $(this).val(formatted);

        // Intenta mantener la posición del cursor (opcional)
        // this.setSelectionRange(cursorPos, cursorPos);
    });
</script>



@endsection