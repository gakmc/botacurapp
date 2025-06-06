@extends('themes.backoffice.layouts.admin')

@section('title', 'Asignar sueldo a Rol')

@section('head')
<link rel="stylesheet" href="{{ asset('assets/pickadate/lib/themes/default.css') }}">
<link rel="stylesheet" href="{{ asset('assets/pickadate/lib/themes/default.date.css') }}">
<link rel="stylesheet" href="{{ asset('assets/pickadate/lib/themes/default.time.css') }}">
@endsection


@section('breadcrumbs')
<li><a href="{{route('backoffice.rango-sueldos.index')}}">Sueldos por Rol</a></li>
<li>Generando sueldo por rol</li>
{{-- <li>{{$cliente->nombre_cliente}}</li> --}}
@endsection

@section('dropdown_settings')
{{-- <li><a href="{{ route('backoffice.reserva.create',$cliente->id) }}" class="grey-text text-darken-2">Crear Reserva</a></li> --}}
@endsection


@section('content')
<div class="section">
    <p class="caption"><strong>Reservaciones</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 ">
                <div class="card-panel">
                    <div class="row">



                        {{-- CONTENIDO --}}
                        <form method="POST" action="{{ isset($rango) ? route('backoffice.rango-sueldos.update', ['rango_sueldo'=>$rango]) : route('backoffice.rango-sueldos.store') }}">
                        @csrf
                        @if(isset($rango)) @method('PUT') @endif

                        <div class="input-field col s12 m6">
                            <select name="role_id" {{(isset($rango) && $rango->role_id) ? 'disabled' : ''}}>
                                <option value="" selected>-- Seleccione --</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ (isset($rango) && $rango->role_id == $role->id) ? 'selected' : '' }}>{{ $role->name }}</option>
                                @endforeach
                            </select>
                            <label>Rol</label>
                        </div>

                        <div class="input-field col s12 m6">
                            <input type="text" id="sueldo_base" name="sueldo_base" value="{{ old('sueldo_base', isset($rango) ? '$'.number_format($rango->sueldo_base, 0, ',', '.') : '$0') }}">
                            <label>Sueldo Base</label>
                        </div>

                        {{-- <div class="input-field col s12 m6">
                            <input type="date" id="vigente_desde" name="vigente_desde" value="{{ old('vigente_desde', $rango->vigente_desde ?? '') }}">
                            <label>Vigente Desde</label>
                        </div>

                        <div class="input-field col s12 m6">
                            <input type="date" id="vigente_hasta" name="vigente_hasta" value="{{ old('vigente_hasta', $rango->vigente_hasta ?? '') }}">
                            <label>Vigente Hasta</label>
                        </div> --}}

                        <div class="row">
                            <div class="input-field col s12">
                            <button class="btn waves-effect waves-light right" type="submit">Generar
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

    $('#vigente_desde').pickadate({
      format: 'dd-mm-yyyy',
      formatSubmit: 'yyyy-mm-dd',
      hiddenName: true
    })

    $('#vigente_hasta').pickadate({
      format: 'dd-mm-yyyy',
      formatSubmit: 'yyyy-mm-dd',
      hiddenName: true
    })

  });
</script>

<script>
    
    $(document).ready(function () {
        function formatCLP(number) {
            number = number.toString().replace(/\D/g, ''); // Elimina todo lo que no sea dígito
            return number ? '$' + parseInt(number, 10).toLocaleString('es-CL') : '';
        }
        
        $('#sueldo_base').on('input', function () {
            const cursorPos = this.selectionStart;
            const rawValue = $(this).val().replace(/\D/g, '');
            const formatted = formatCLP(rawValue);
            $(this).val(formatted);
    
            // Intenta mantener la posición del cursor (opcional)
            // this.setSelectionRange(cursorPos, cursorPos);
        });
    });
</script>
@endsection