@extends('themes.backoffice.layouts.admin')

@section('title','Datos Bancarios de ' . $user->name)

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.datos-bancarios.index') }}">Datos Bancarios</a></li>
<li>{{$user->name}}</li>
@endsection

@section('content')

<div class="section">
    <p class="caption">Datos bancarios y de boleta de honorarios de {{ $user->name }}</p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 m8 offset-m2">
                <div class="card-panel">
                    <h4 class="header2">{{ $user->name }}</h4>
                    <div class="row">
                        <form class="col s12" method="post" action="{{ route('backoffice.datos-bancarios.update', $user) }}">

                            {{ csrf_field() }}
                            {{ method_field('PUT') }}

                            <div class="row">
                                <div class="input-field col s12">
                                    <input id="rut" type="text" name="rut" value="{{ old('rut', $user->rut) }}" placeholder="Ej: 21073497-K">
                                    <label for="rut" class="active">RUT (sin puntos, con guión)</label>
                                    @error('rut')
                                        <span class="invalid-feedback" role="alert"><strong style="color:red">{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col s12">
                                    <label>
                                        <input type="checkbox" name="boletea" value="1" {{ old('boletea', $user->boletea) ? 'checked' : '' }} />
                                        <span>Emite Boleta de Honorarios Electrónica (BTE)</span>
                                    </label>
                                    @error('boletea')
                                        <span class="invalid-feedback" role="alert"><strong style="color:red">{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="input-field col s12">
                                    <input id="banco" type="text" name="banco" value="{{ old('banco', $user->banco) }}" placeholder="Ej: BancoEstado">
                                    <label for="banco" class="active">Banco</label>
                                    @error('banco')
                                        <span class="invalid-feedback" role="alert"><strong style="color:red">{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="input-field col s12">
                                    <select name="tipo_cuenta_bancaria" id="tipo_cuenta_bancaria">
                                        <option value="" {{ old('tipo_cuenta_bancaria', $user->tipo_cuenta_bancaria) == '' ? 'selected' : '' }}>-- Selecciona tipo de cuenta --</option>
                                        @foreach(['Cuenta Corriente', 'Cuenta Vista', 'Cuenta RUT', 'Cuenta de Ahorro'] as $tipo)
                                            <option value="{{ $tipo }}" {{ old('tipo_cuenta_bancaria', $user->tipo_cuenta_bancaria) == $tipo ? 'selected' : '' }}>{{ $tipo }}</option>
                                        @endforeach
                                    </select>
                                    @error('tipo_cuenta_bancaria')
                                        <span class="invalid-feedback" role="alert"><strong style="color:red">{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="input-field col s12">
                                    <input id="numero_cuenta_bancaria" type="text" name="numero_cuenta_bancaria" value="{{ old('numero_cuenta_bancaria', $user->numero_cuenta_bancaria) }}" placeholder="Ej: 21073497">
                                    <label for="numero_cuenta_bancaria" class="active">Número de cuenta</label>
                                    @error('numero_cuenta_bancaria')
                                        <span class="invalid-feedback" role="alert"><strong style="color:red">{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="input-field col s12">
                                    <input id="correo_personal" type="email" name="correo_personal" value="{{ old('correo_personal', $user->correo_personal) }}" placeholder="correo@ejemplo.com">
                                    <label for="correo_personal" class="active">Correo personal (recibe el comprobante de pago)</label>
                                    @error('correo_personal')
                                        <span class="invalid-feedback" role="alert"><strong style="color:red">{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="input-field col s12">
                                    <a href="{{ route('backoffice.datos-bancarios.index') }}" class="btn-flat waves-effect">Cancelar</a>
                                    <button class="btn waves-effect waves-light right" type="submit">Guardar
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
@endsection
