<script src="{{ asset('assents/plugins/jquery-3.2.1.min.js') }}"></script>
<script src="{{ asset('assents/plugins/perfect-scrollbar/perfect-scrollbar.min.js') }}"></script>
<script src="{{ asset('assents/backoffice/js/plugins.js') }}"></script>
<script src="{{ asset('assents/backoffice/js/custom-script.js') }}"></script>
<script src="{{ asset('assents/backoffice/js/materialize.min.js') }}"></script>
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






@include('sweetalert::alert')

@yield('foot')