@extends('themes.backoffice.layouts.admin')

@section('title', 'Editar sueldo individual')

@section('dropdown_settings')
<li><a href="{{ route('backoffice.usuario-sueldo.index') }}" class="grey-text text-darken-2">Volver al listado</a></li>
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Modificar sueldo personalizado</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 ">
                <div class="card-panel">
                    <div class="row">






    <form method="POST" action="{{ route('backoffice.usuario-sueldo.update', $anularSueldoUsuario->id) }}">
        @csrf
        @method('PUT')

        <div class="input-field col s12 m6">
            <input type="text" value="{{$funcionario->name}}" disabled class="black-text">
            <label>Usuario</label>
            <input type="hidden" name="user_id" value="{{ $anularSueldoUsuario->user_id }}">
            @error('user_id')
                <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <div class="input-field col s12 m6">
            <input type="text" name="salario" id="salario" value="{{ old('salario', '$' . number_format($anularSueldoUsuario->salario, 0, ',', '.')) }}">
            <label for="salario">Salario personalizado</label>
            @error('salario')
                <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <div class="input-field col s12">
            <textarea name="motivo" id="motivo" class="materialize-textarea">{{ old('motivo', $anularSueldoUsuario->motivo) }}</textarea>
            <label for="motivo">Motivo de la sobrescritura</label>
            @error('motivo')
                <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <div class="col s12 right-align">
            <button type="submit" class="btn waves-effect waves-light">
                Actualizar
                <i class="material-icons right">edit</i>
            </button>
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
<script>
    function formatCLP(number) {
        number = number.toString().replace(/\D/g, '');
        return number ? '$' + parseInt(number, 10).toLocaleString('es-CL') : '';
    }

    $('#salario').on('input', function () {
        const cursorPos = this.selectionStart;
        const rawValue = $(this).val().replace(/\D/g, '');
        const formatted = formatCLP(rawValue);
        $(this).val(formatted);
    });
</script>
@endsection