@extends('themes.backoffice.layouts.admin')

@section('title','Dar de alta un nuevo Cliente')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.cliente.index') }}">Nuestros Clientes</a></li>
<li>Crear Cliente</li>
@endsection



@section('content')

<div class="section">
              <p class="caption">Introduce los datos para crear un nuevo cliente</p>
              <div class="divider"></div>
              <div id="basic-form" class="section">
                <div class="row">
                  <div class="col s12 m8 offset-m2 ">
                    <div class="card-panel">
                      <h4 class="header2">Crear Cliente</h4>
                      <div class="row">
                        <form class="col s12" method="post" action="{{route('backoffice.cliente.store')}}">


                        {{csrf_field() }}
 
                            

                            <div class="row">
                                <div class="input-field col s12 m6">
                                  <input id="nombre_cliente" type="text" class="form-control @error('nombre_cliente') is-invalid @enderror" name="nombre_cliente" value="{{ request('nombre') ?? old('nombre_cliente') }}" autocomplete="name" autofocus>
                                      <label for="nombre_cliente">Nombre del cliente</label>

                                  @error('nombre_cliente')
                                  <span class="invalid-feedback" role="alert">
                                      <strong style="color:red">{{ $message }}</strong>
                                  </span>
                                  @enderror
                                </div>

                                <div class="input-field col s12 m6">
                                  <input id="correo" type="email" name="correo" value="{{ old('correo') }}">
                                  <label for="correo">Correo electrónico</label>
                                    @error('correo')
                                          <span class="invalid-feedback" role="alert">
                                              <strong style="color:red">{{ $message }}</strong>
                                          </span>
                                      @enderror
                                </div>

                            </div>     

                         
                          <div class="row">
                            <div class="input-field col s12 m6">
                              <input id="whatsapp_cliente" type="text" name="whatsapp_cliente" class="form-control @error('nombre_cliente') is-invalid @enderror" value="{{ old('whatsapp_cliente') }}">
                              <label for="whatsapp_cliente">Whatsapp Cliente</label>
                                @error('whatsapp_cliente')
                                      <span class="invalid-feedback" role="alert">
                                          <strong style="color:red">{{ $message }}</strong>
                                      </span>
                                  @enderror
                            </div>

                            <div class="input-field col s12 m6">
                              <input id="instagram_cliente" type="text" name="instagram_cliente" class="form-control @error('nombre_cliente') is-invalid @enderror" value="{{ old('instagram_cliente') }}">
                              <label for="instagram_cliente">Instagram Cliente</label>
                                @error('instagram_cliente')
                                      <span class="invalid-feedback" role="alert">
                                          <strong style="color:red">{{ $message }}</strong>
                                      </span>
                                  @enderror
                            </div>

                            
                          </div>
                         

                          <div class="row">
                            <div class="input-field col s12 m6">
                              <select id="sexo" name="sexo">
                                <option value="" disabled selected>-- Seleccione --</option>
                                <option value="Masculino" {{ old('sexo') == 'Masculino' ? 'selected' : '' }}>Masculino</option>
                                <option value="Femenino" {{ old('sexo') == 'Femenino' ? 'selected' : '' }}>Femenino</option>
                                <option value="na" {{ old('sexo') == 'na' ? 'selected' : '' }}>Prefiero no responder</option>
                              </select>
                              <label>Género</label>
                              @error('sexo')
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

  $(document).ready(function () {
    // $('select').formSelect();
    // $('select').material-select();

  });
</script>

{{-- <script>
  $(document).ready(function () {

    $('#whatsapp_cliente').on('blur', function () {
      const numero = $(this).val().trim();

      if (numero !== '') {
        $.ajax({
          url: '{{ route("backoffice.validar.whatsapp") }}',
          method: 'POST',
          data: {
            whatsapp_cliente: numero,
            _token: '{{ csrf_token() }}'
          },
          success: function (response) {
            if (!response.disponible) {
              if (!$('#error-whatsapp').length) {
                // $('#whatsapp_cliente').after('<span id="error-whatsapp" style="color:red">Este número ya está registrado.</span>');
                $('#whatsapp_cliente').after('<span id="error-whatsapp" style="color:red">Este número ya está registrado.</span>');
              }
            } else {
              $('#error-whatsapp').remove();

              $('#whatsapp_cliente').after('<i class="material-icons green-text">check_circle</i>');
                  
            }
          },
          error: function () {
            const Toast = Swal.mixin({
            toast: true,
            position: "center",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
              toast.onmouseenter = Swal.stopTimer;
              toast.onmouseleave = Swal.resumeTimer;
            }
          });
          Toast.fire({
            icon: "error",
            title: "Error al validar número de WhatsApp."
          });
          }
        });
      }
    });
  });
</script> --}}


{{-- <script>
  $(document).ready(function () {
    let validandoWhatsapp = false;

    $('#whatsapp_cliente').on('blur', function () {
      let numero = $(this).val().trim();

      if (validandoWhatsapp) return;

      validandoWhatsapp = true;

      // Eliminar todo lo que no sea número
      numero = numero.replace(/\D/g, '');

      // Si es 8 dígitos, agregar 569
      if (numero.length === 8) {
        numero = '569' + numero;
      }

      // Si empieza con 56 y tiene 11 dígitos, convertirlo a 569XXXXXXX
      if (numero.length === 11 && numero.startsWith('56') && !numero.startsWith('569')) {
        numero = '569' + numero.slice(3);
      }

      // Si ya empieza con 569 y tiene 11 dígitos, está correcto

      if (numero !== '') {
        $.ajax({
          url: '{{ route("backoffice.validar.whatsapp") }}',
          method: 'POST',
          data: {
            whatsapp_cliente: numero,
            _token: '{{ csrf_token() }}'
          },
          success: function (response) {
            
            $('#error-whatsapp').remove();
            $('#whatsapp_cliente').nextAll('.material-icons').remove();

            if (!response.disponible) {
              // const enlace = `<a href="/reserva/create/${response.cliente.id}" style="color:blue; text-decoration:underline; margin-left:10px">Crear reserva para ${response.cliente.nombre_cliente}</a>`;

              // $('#whatsapp_cliente').after(`<span id="error-whatsapp" style="color:red">Este número ya está registrado. ${enlace}</span>`);

                  Swal.fire({
                    icon: 'warning',
                    title: 'Número ya registrado',
                    text: `Este número ya pertenece a ${response.cliente.nombre_cliente}.`,
                    showCancelButton: true,
                    confirmButtonText: 'Crear reserva',
                    cancelButtonText: 'Cambiar número',
                    reverseButtons: true,
                    customClass: {
                      confirmButton: 'btn green darken-1',
                      cancelButton: 'btn red darken-1'
                    },
                    buttonsStyling: false
                  }).then((result) => {
                    if (result.isConfirmed) {
                      // window.open(`/reserva/create/${response.cliente.id}`);
                      window.location.href = `/reserva/create/${response.cliente.id}`;

                    } else {
                      $('#whatsapp_cliente').focus();
                    }

                    validandoWhatsapp = false;
                  });
              
              
            } else {
              $('#whatsapp_cliente').after('<i class="material-icons green-text">check_circle</i>');

              validandoWhatsapp = false;
            }
  
          },
          error: function () {
            Swal.fire({
              icon: "error",
              title: "Error al validar número de WhatsApp.",
              toast: true,
              position: "center",
              timer: 3000,
              showConfirmButton: false
            });

            validandoWhatsapp = false;
          }
        });
      } else {
        validandoWhatsapp = false;
      }
    });
  });

</script> --}}

<script>
$(document).ready(function () {
  let validandoWhatsapp = false;
  let ultimoNumeroValidado = null;

  $('#whatsapp_cliente').on('blur', function () {
    let numero = $(this).val().trim().replace(/\D/g, '');

    // Formateo del número
    if (numero.length === 8) {
      numero = '569' + numero;
    } else if (numero.length === 11 && numero.startsWith('56') && !numero.startsWith('569')) {
      numero = '569' + numero.slice(3);
    }

    if (numero === ultimoNumeroValidado || numero === '') {
      return;
    }

    if (validandoWhatsapp) return;
    validandoWhatsapp = true;
    ultimoNumeroValidado = numero;

    $.ajax({
      url: '{{ route("backoffice.validar.whatsapp") }}',
      method: 'POST',
      data: {
        whatsapp_cliente: numero,
        _token: '{{ csrf_token() }}'
      },
      success: function (response) {
        $('#error-whatsapp').remove();
        $('#whatsapp_cliente').nextAll('.material-icons').remove();
        $('#whatsapp_cliente').removeClass('valid invalid');

        if (!response.disponible) {
          $('#whatsapp_cliente').addClass('invalid');

          Swal.fire({
            icon: 'warning',
            title: 'Número ya registrado',
            text: `Este número ya pertenece a ${response.cliente.nombre_cliente}.`,
            showCancelButton: true,
            confirmButtonText: 'Crear reserva',
            cancelButtonText: 'Cambiar número',
            reverseButtons: true,
            customClass: {
              confirmButton: 'btn green darken-1',
              cancelButton: 'btn red darken-1'
            },
            buttonsStyling: false
          }).then((result) => {
            if (result.isConfirmed) {
              window.location.href = `/reserva/create/${response.cliente.id}`;
            } else {
              $('#whatsapp_cliente').focus();
            }

            validandoWhatsapp = false;
          });

        } else {
          $('#whatsapp_cliente').addClass('valid');
          $('#whatsapp_cliente').after('<i class="material-icons green-text">check_circle</i>');
          validandoWhatsapp = false;
        }
      },
      error: function () {
        Swal.fire({
          icon: "error",
          title: "Error al validar número de WhatsApp.",
          toast: true,
          position: "center",
          timer: 3000,
          showConfirmButton: false
        });
        validandoWhatsapp = false;
      }
    });
  });

  $('#whatsapp_cliente').on('input', function () {
    validandoWhatsapp = false;
    ultimoNumeroValidado = null;
  });
});
</script>


@endsection
