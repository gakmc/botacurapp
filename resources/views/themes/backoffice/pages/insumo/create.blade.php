@extends('themes.backoffice.layouts.admin')

@section('title','Ingresar Insumo')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{ route('backoffice.insumo.index') }}">Insumos</a></li>
<li>Ingresar Insumo</li>
@endsection



@section('content')

<div class="section">
    <p class="caption">Ingrese los datos para Registrar Insumo.</p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 m8 offset-m2 ">
                <div class="card-panel">
                    <h4 class="header">Generar <strong>Insumo</strong></h4>
                    <div class="row">
                        <form class="col s12" method="post" action="{{route('backoffice.insumo.store')}}">


                            {{csrf_field() }}



                            <div class="row">
                                {{-- <div class="input-field col s12 m6 l4" hidden>
                                    <input id="id_venta" type="hidden" class="form-control" name="id_venta"
                                        value="{{$venta->id}}" required>
                                </div> --}}

                                <div class="input-field col s12 m6 l4">

                                    <label for="nombre">Nombre</label>
                                    <input id="nombre" type="text" name="nombre" class="" value="{{ old('nombre') }}">
                                    @error('nombre')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>



                                <div class="input-field col s12 m6 l4">

                                    <label for="valor">Valor</label>
                                    <input id="valor" type="number" name="valor" class="" value="{{ old('valor') }}">
                                    @error('valor')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>

                                <div class="input-field col s12 m6 l4">

                                    <label for="cantidad">Cantidad</label>
                                    <input id="cantidad" type="number" name="cantidad" class=""
                                        value="{{ old('cantidad') }}">
                                    @error('cantidad')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>

                                <div class="input-field col s12 m6 l4">

                                    <label for="stock_critico">Stock Critico</label>
                                    <input id="stock_critico" type="number" name="stock_critico" class=""
                                        value="{{ old('stock_critico') }}">
                                    @error('stock_critico')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>




                                <div class="input-field col s12 m6 l4">

                                    <select id="id_unidad_medida" name="id_unidad_medida" class=""
                                        value="{{ old('id_unidad_medida') }}">
                                        <option value="">-- Seleccione --</option>
                                        @foreach ($unidades as $unidad)
                                            <option value="{{$unidad->id}}">{{$unidad->nombre}}</option>
                                        @endforeach
                                    </select>
                                    <label for="id_unidad_medida">Unidad de Medida</label>

                                    @error('id_unidad_medida')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror

                                </div>


                                <div class="input-field col s12 m6 l4">

                                    <select id="id_sector" name="id_sector" class="" value="{{ old('id_sector') }}">
                                        <option value="">-- Seleccione --</option>
                                        @foreach ($sectores as $sector)
                                            <option value="{{$sector->id}}">{{$sector->nombre}}</option>
                                        @endforeach
                                    </select>
                                    <label for="id_sector">Sectores</label>

                                    @error('id_sector')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror

                                </div>



                            </div>






                            <div class="row">
                                <div class="input-field col s12">
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
<script>

</script>

@endsection