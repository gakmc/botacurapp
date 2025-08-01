@extends('themes.backoffice.layouts.admin')

@section('title')
Gift Card solicitada por {{$gc->de}}
@endsection

@section('head')
<link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
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
    <p class="caption"><strong>Gift Card</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 ">
                <div class="card-panel">
                    <div class="row">



                        <div id="giftcard" class="col s12 m10 offset-m1 l8 offset-l2">
                            <div class="card z-depth-3"
                                style="overflow: hidden; border-radius: 15px; background: linear-gradient(to right, #fff 50%, #00897b1a 50%);">
                                <div class="row no-margin" style="display: flex; flex-wrap: wrap;">
                                    <!-- Lado izquierdo -->
                                    <div class="col s12 m6"
                                        style="padding: 20px; background-image: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('{{ asset('images/gc/fondo-botacura.jpeg') }}'); background-size: cover; background-position: center;">
                                        <h5  style="font-weight: bold; color: #039B7B;">BOTACURA<br><small
                                                style="font-size: 16px;">Cajón del Maipo</small></h5>
                                        <h6 class="white-text center" style="margin-top: 20px; font-size:25px">{{$programa->nombre_programa}}</h6>
                                        <ul class="white-text" style="padding-left: 0; list-style: none;">
                                            @php
                                                $lista = ['masaje','tinaja','sauna'];
                                            @endphp
                                            @foreach ($programa->servicios as $servicio)
                                            <li>✔️ {{$servicio->nombre_servicio}} @if (in_array(strtolower($servicio->nombre_servicio),$lista))
                                                - ({{$servicio->duracion}} mins)
                                            @endif</li>
                                                
                                            @endforeach

                                        </ul>
                                    </div>

                                    <!-- Lado derecho -->
                                    <div class="col s12 m6" style="padding: 20px;">
                                        <h5 style="font-family: 'Pacifico', cursive; color: #00695c;">Gift Card 🎁</h5>
                                        <p><strong>De:</strong> {{$gc->de}}</p>
                                        <p><strong>Para:</strong> {{$gc->para}}</p>
                                        <p><strong>Válido hasta:</strong>{{$gc->valido}}</p>
                                        <p style="margin-top: 40px;">Programa tu horario al WhatsApp:</p>
                                        <h6><strong>+56 9 8272 0582</strong></h6>

                                        <div class="center">

                                            <h5 style="margin-top: 20px;">Código: {{ $gc->codigo }}</h5>
    
                                            <img src="data:image/png;base64,{{ $barcode }}" alt="Código de barras">

                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="col s12 m10 offset-m1 l8 offset-l2">
                            <div class="center">
                                @if ($gc->usada == false)
                                    <a class="btn blue" href="{{route('backoffice.giftcards.enviar',$gc)}}">Enviar<i class='material-icons right'>share</i></a>
                                    <a class="btn pink" href="{{route('backoffice.giftcards.reservar',$gc)}}">Reservar<i class='material-icons right'>person_add</i></a>
                                @endif

                            </div>
                        </div>




                    </div>
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

@endsection