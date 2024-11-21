@extends('themes.backoffice.layouts.admin')

@section('title', 'Reservas')

@section('breadcrumbs')
<li><a href="{{route('backoffice.asignacion.index')}}">Registrar equipo</a></li>
@endsection

@section('head')
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Asignación de equipo</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 m8 offset-m2 ">


                <div class="card-panel">
                    <h4 class="header2">Seleccione conformacion de equipo para fecha <strong>{{ $fecha }}</strong></h4>
                    <div class="row">
                        <form class="col s12" method="post" action="{{route('backoffice.asignacion.store')}}">


                            {{csrf_field() }}



                            <div class="row">
                                <div class="input-field col s12 m6" hidden>
                                    <input id="fecha" type="text"
                                        class="form-control @error('fecha') is-invalid @enderror" name="fecha"
                                        value="{{ $fecha }}" required autocomplete="name" autofocus>
                                    <label for="fecha">Fecha</label>

                                    {{-- @error('fecha')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror --}}

                                </div>

                                @foreach ($users as $user)

                                <p>
                                    <label>
                                        <input name="users[]" type="checkbox" class="filled-in" value="{{$user->id}}" />
                                        <span class="black-text">{{$user->name}} - ({{$user->list_roles()}})</span>
                                    </label>
                                </p>
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
@if ($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                toast: true,
                position: 'top',
                icon: 'error',
                title: '{{ $errors->first() }}',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true,
            });
        });
    </script>
@endif

@endsection