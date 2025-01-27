<script src="{{ asset('assents/plugins/jquery-3.2.1.min.js') }}"></script>
<script src="{{ asset('assents/plugins/perfect-scrollbar/perfect-scrollbar.min.js') }}"></script>
<script src="{{ asset('assents/backoffice/js/plugins.js') }}"></script>
<script src="{{ asset('assents/backoffice/js/custom-script.js') }}"></script>
<script src="{{ asset('assents/backoffice/js/materialize.min.js') }}"></script>
{{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script> --}}
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


        $('.side-nav').sidenav();
        

        $('.dropdown-settings').dropdown({
            hover: false, // Deshabilita el hover para que funcione en dispositivos móviles
            constrainWidth: false, // Ajusta el ancho al contenido
            coverTrigger: false, // Muestra el dropdown fuera del botón
            alignment: 'left', // Opcional: Alinea el dropdown a la izquierda
            closeOnClick: true // Cierra el dropdown al hacer clic fuera
        });   
        
        $('.dropdown-trigger').dropdown({
            hover: false, // Deshabilita el hover para que funcione en dispositivos móviles
            constrainWidth: false, // Ajusta el ancho al contenido
            coverTrigger: false, // Muestra el dropdown fuera del botón
            alignment: 'left', // Opcional: Alinea el dropdown a la izquierda
            closeOnClick: true // Cierra el dropdown al hacer clic fuera
        });

    });
    

</script>

@include('sweetalert::alert')

@yield('foot')