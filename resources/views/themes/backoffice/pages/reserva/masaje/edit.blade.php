@extends('themes.backoffice.layouts.admin')

@section('title','Modificar Masajes')

@section('breadcrumbs')
{{-- <li><a href="{{route('backoffice.cliente.show', $cliente->id) }}">Reservas del cliente</a></li>
<li>Crear Reserva</li> --}}
@endsection

@section('content')
<div class="section">
  <p class="caption">Introduce los datos para Modificar los Masajes</p>
  <div class="divider"></div>
  <div id="basic-form" class="section">
      <div class="row">
          <div class="col s12 m10 offset-m1 ">
              <div class="card-panel">
                  <h4 class="header">Modificar Masajes para reservas de <strong>{{$reserva->cliente->nombre_cliente}}</strong> -
                      Fecha:<strong>{{$reserva->fecha_visita}}</strong></h4>
                  <div class="row">
                      <form class="col s12" method="post"
                          action="{{route('backoffice.reserva.masaje_update', [$reserva])}}">


                          {{csrf_field() }}
                          @method('PUT')



                        @foreach ($masajes as $masaje)
                            <div class="row">

                                <h6><strong>Persona {{$masaje->persona}}</strong></h6>
                                <div class="input-field col s12 m6 l4" @if(!in_array('Masaje', $servicios) & !$masajesExtra) style="display: none;" @endif>

                                    <select id="horario_masaje_{{$masaje->id}}" name="masajes[{{$masaje->id}}][horario_masaje]" @if(!in_array('Masaje', $servicios) && !$masajesExtra) disabled hidden @endif>
      
                                        <option value="{{$masaje->horario_masaje}}" selected>{{$masaje->horario_masaje}}</option>
      
      
                                    </select>
                                    <label for="horario_masaje_{{$masaje->id}}">Horario Masaje</label>
                                    @error('horario_masaje_{{$masaje->id}}')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror
      
                                </div>
      
                                <div class="input-field col s12 m6 l4" @if(!in_array('Masaje', $servicios) && !$masajesExtra) style="display: none;" @endif>
      
                                    <select id="tipo_masaje_{{$masaje->id}}" name="masajes[{{$masaje->id}}][tipo_masaje]" @if(!in_array('Masaje', $servicios) && !$masajesExtra) disabled hidden @endif>
      
      
                                        <option value="Relajante" {{ $masaje->tipo_masaje ==='Relajante' ? 'selected' : ''}}>Relajante</option>
                                        <option value="Descontracturante" {{ $masaje->tipo_masaje ==='Descontracturante' ? 'selected' : '' }}>Descontracturante</option>
      
      
      
                                    </select>
                                    <label for="tipo_masaje">Tipo Masaje</label>
                                    @error('tipo_masaje')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror
      
                                </div>
      
                                <div class="input-field col s12 m6 l4" @if(!in_array('Masaje', $servicios) && !$masajesExtra) style="display: none;" @endif>
                                    <select name="masajes[{{$masaje->id}}][id_lugar_masaje]" id="id_lugar_masaje_{{$masaje->id}}" @if(!in_array('Masaje', $servicios) && !$masajesExtra) disabled hidden @endif>
                                        @foreach ($lugares as $lugar)
                                        <option value="{{$lugar->id}}" {{ $masaje->id_lugar_masaje === $lugar->id ?
                                            'selected' : '' }}>{{$lugar->nombre}}</option>
                                        @endforeach
                                    </select>
                                    @error('id_lugar_masaje_{{$masaje->id}}')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror
                                    <label for="id_lugar_masaje_{{$masaje->id}}">Lugar Masaje</label>
                                </div>


                            </div>

                        @endforeach



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
<script>
    @if(session('success'))
        Swal.fire({
            toast: true,
            icon: 'success',
            title: '{{ session('success') }}',
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true,
                didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
                }
        });
    @endif

    @if(session('error'))
        Swal.fire({
            toast: true,
            icon: 'error',
            title: '{{ session('error') }}',
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true,
                didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
                }
        });
    @endif
</script>


<script>
    $(document).ready(function () {
        // Cargar horarios desde el backend
        const horariosPorLugar = @json($horasMasaje);
        // console.log('Horarios cargados desde el backend:', horariosPorLugar);

        // Inicializa todos los selectores de Materialize
        $('select').material_select();

        // Función para cargar horarios en el select de horario masaje
        function cargarHorariosInicial(lugarId, index, horarioActual = null) {
            const $horarioSelect = $(`#horario_masaje_${index}`);
            lugarId = parseInt(lugarId);
            // console.log(`Cargando horarios para lugar ${lugarId}:`, horariosPorLugar[lugarId]);

            // Destruir Materialize select antes de modificarlo
            $horarioSelect.material_select('destroy');
            $horarioSelect.empty(); // Vacía el select

            // Si hay un horario guardado en la BDD, agregarlo como seleccionado
            if (horarioActual) {
                // console.log(`Horario guardado en la BDD para masaje ${index}: ${horarioActual}`);
                $horarioSelect.append(new Option(horarioActual, horarioActual, true, true));
            } else {
                $horarioSelect.append('<option value="" disabled selected>-- Seleccione --</option>');
            }

            // Agregar horarios disponibles según el lugar seleccionado
            if (Array.isArray(horariosPorLugar[lugarId])) {
                horariosPorLugar[lugarId].forEach(function (horario) {
                    let isSelected = horario === horarioActual; // Marcar si es el guardado
                    $horarioSelect.append(new Option(horario, horario, isSelected, isSelected));
                });
            }

            // Volver a inicializar Materialize select
            $horarioSelect.material_select();

            // console.log(`Horarios actualizados en horario_masaje_${index}:`, $horarioSelect.html());
        }

        // Detectar cambios en el lugar de masaje
        $('[id^="id_lugar_masaje_"]').on('change', function () {
            let lugarId = $(this).val();
            const index = $(this).attr('id').split('_').pop();
            lugarId = parseInt(lugarId);
            // console.log(`Cambiado lugar de masaje para el índice ${index}, nuevo lugar: ${lugarId}`);

            // Obtener el horario actualmente seleccionado antes de cambiarlo
            const horarioActual = $(`#horario_masaje_${index}`).val();
            cargarHorariosInicial(lugarId, index, horarioActual);
        });

        // Carga inicial: busca todos los selects que tienen lugar de masaje ya seleccionado
        $('[id^="id_lugar_masaje_"]').each(function () {
            let lugarId = $(this).val();
            const index = $(this).attr('id').split('_').pop();
            let horarioActual = $(`#horario_masaje_${index}`).val();

            lugarId = parseInt(lugarId);
            // console.log(`Carga inicial del masaje ${index}: Lugar ID ${lugarId}, Horario actual ${horarioActual}`);

            if (lugarId) {
                cargarHorariosInicial(lugarId, index, horarioActual);
            }
        });
    });
</script>
@endsection