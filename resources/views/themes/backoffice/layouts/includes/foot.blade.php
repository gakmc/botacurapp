<script src="{{ asset('assents/plugins/jquery-3.2.1.min.js') }}"></script>
<script src="{{ asset('assents/plugins/perfect-scrollbar/perfect-scrollbar.min.js') }}"></script>
<script src="{{ asset('assents/backoffice/js/plugins.js') }}"></script>
<script src="{{ asset('assents/backoffice/js/custom-script.js') }}"></script>
{{-- <script src="{{ asset('assents/backoffice/js/materialize.min.js') }}"></script> --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
<script src="{{ asset('assents/plugins/swal/sweetalert2.all.min.js') }}"></script>
<!-- <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script> -->


<script>
    $(document).ready(function () {
                //Give a time for initialization of combos
                setTimeout(function () {
                    var kelle = $('.select-wrapper');// $('.select-wrapper');
                    $.each(kelle, function (i, t) {
                        t.addEventListener('click', e => e.stopPropagation())
                    });
                }, 500)

    });


</script>

<script>
    $(document).ready(function(){
        $('.tooltipped').tooltip();
    });
    


    $(document).ready(function () {            
        $('.dropdown-settings').dropdown({
            hover: true, // Activa el dropdown al pasar el mouse
            constrainWidth: false, // Opcional: para ajustar el ancho al contenido
            coverTrigger: false
        });       
    });

    $(document).ready(function () {
        // Inicializar los dropdowns
        $('.dropdown-trigger').dropdown({
            hover: true, // Abre el dropdown al pasar el mouse
            constrainWidth: false, // Ajusta el ancho al contenido
            coverTrigger: false // Opcional: Muestra el dropdown fuera del botón
        });
    });


</script>

@include('sweetalert::alert')

@yield('foot')