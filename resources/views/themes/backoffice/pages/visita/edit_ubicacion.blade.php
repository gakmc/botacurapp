@extends('themes.backoffice.layouts.admin')

@section('title', '')

@section('head')
@endsection


@section('breadcrumbs')
{{-- <li><a href="{{route('backoffice.cliente.index')}}">Clientes del Sistema</a></li> --}}
{{-- <li>{{$cliente->nombre_cliente}}</li> --}}
@endsection

@section('dropdown_settings')
{{-- <li><a href="{{ route('backoffice.reserva.create',$cliente->id) }}" class="grey-text text-darken-2">Crear
        Reserva</a></li> --}}
@endsection


@section('content')
<div class="section">
    <p class="caption"><strong>Modificar Ubicacion</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s8 offset-m2 ">
                <div class="card-panel">
                    <h4 class="header2">Cambiar de Ubicacion <strong>{{$visita->ubicacion->nombre ?? 'No registra ubicacion'}}</strong></h4>
                    <div class="row">



                        {{-- CONTENIDO --}}

                        <form class="col s10 offset-m2" method="post" action="{{route('backoffice.visita.update_ubicacion', $visita)}}">

                            @csrf
                            @method('PUT')

                            <div class="row">
                                {{-- <div class="input-field col s10">

                                    <select name="ubicacion" id="ubicacion" required="">

                                        <option value="{{$visita->ubicacion->id}}" disabled selected>
                                            {{$visita->ubicacion->nombre}}</option>
                                        @foreach($ubicaciones as $ubicacion)
                                        <option value="{{$ubicacion->id}}">{{$ubicacion->nombre}}</option>
                                        @endforeach

                                    </select>

                                    @error('role')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div> --}}

                                @foreach ($ubicaciones as $ubicacion)
                                <div class="col s6">

                                    <p>
                                        <label>
                                            <input value="{{$ubicacion->id}}" class="with-gap" name="ubicacion"
                                                type="radio" />
                                            <span class="black-text">{{$ubicacion->nombre}}</span>
                                        </label>
                                    </p>
                                </div>
                                @endforeach

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
@endsection