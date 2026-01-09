@extends('themes.backoffice.layouts.admin')

@section('title', 'Reservas')

@section('breadcrumbs')
@endsection

@section('dropdown_settings')
<li><a href="{{route ('backoffice.reservas.listar') }}" class="grey-text text-darken-2">Todas las Reservas</a></li>
@endsection

@section('head')
@endsection

@section('content')
<div class="section">
    <a href="?page=1"><p class="caption"><strong>Reservas desde {{ now()->format('d-m-Y') }}</strong></p></a>
    <div class="row"><div class="col s2 green-text offset-s2"><i class='material-icons left'>fiber_manual_record</i>Pagado</div><div class="col s2 orange-text offset-s1"><i class='material-icons left'>fiber_manual_record</i>Por pagar Consumo</div> <div class="col s2 blue-text offset-s1"><i class='material-icons left'>fiber_manual_record</i>Por Pagar</div></div>
    
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="card-panel ">
            <a href="{{ route('backoffice.reserva.index', ['alternative' => !$alternativeView]) }}" class="waves-effect waves-light btn right hide-on-small-only hide-on-med-only">
                @if ($alternativeView)
                Horarios <i class='material-icons right'>list</i>
                @else
                Ubicación <i class='material-icons right'>apps</i>
                @endif</a>
                
                <a href="#modalSaunaDisponible" data-target="modal-sauna-disponible" class="waves-effect waves-light btn modal-trigger right hide-on-small-only hide-on-med-only">Horas Disponibles <i class='material-icons right'>access_time</i></a>

                <a href="#modalLugaresDisponible" data-target="modal-lugares-disponible" class="waves-effect waves-light btn modal-trigger right hide-on-small-only hide-on-med-only">Lugares Disponibles <i class='material-icons right'>beach_access</i></a>
            
            {{-- Vista Alternativa --}}
<div id="reservas-content">
    <div class="center" style="padding:20px;">
        <div class="preloader-wrapper active">
            <div class="spinner-layer spinner-blue-only">
                <div class="circle-clipper left"><div class="circle"></div></div>
                <div class="gap-patch"><div class="circle"></div></div>
                <div class="circle-clipper right"><div class="circle"></div></div>
            </div>
        </div>
        <p>Cargando reservas...</p>
    </div>
</div>

                {{-- Modal para mostrar los horarios disponibles --}}
                @include('themes.backoffice.pages.reserva.includes.modal_sauna_disponible')
                @include('themes.backoffice.pages.reserva.includes.modal_lugares_disponible')

        </div>
    </div>
</div>
@endsection

@section('foot')
<script>
    function activar_alerta(cliente)
    {
        console.log(cliente);
        
        Swal.fire({
            toast: true,
            icon: 'warning',
            title: `${cliente} no registra masajes`,
            color: 'white',
            iconColor: 'white',
            background: "#039B7B",
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true,
                didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
                }
        });
    }
</script>

<script>
    $(document).ready(function() {
        $('.modal').modal();
    });
</script>

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

{{-- Vista Movil --}}
{{-- <script>


    $(document).ready(function () {
        // Ocultar todas las reservas al inicio excepto las activas
        $(".reserva-card").hide();

        // Al hacer clic en un botón flotante, mostrar las reservas correspondientes
        $(".fixed-action-btn a[data-vista]").on("click", function () {
            let tipo = $(this).data("vista"); // Obtener el tipo de vista

            $(".reserva-card").hide(); // Ocultar todas
            $('.reserva-card[data-tipo="' + tipo + '"]').fadeIn(); // Mostrar solo las que coincidan
        });

        // Al inicio mostrar todas las reservas
        $(".reserva-card").fadeIn();
    });

</script> --}}



<script>
document.addEventListener('DOMContentLoaded', function () {

    // evita doble init (por layout/partials)
    if (window.__reservasAjaxInit) return;
    window.__reservasAjaxInit = true;

    const cont = document.getElementById('reservas-content');

    function currentParamsFromUrl(url) {
        const u = new URL(url, window.location.origin);
        return u.searchParams;
    }

    function buildContenidoUrlFromCurrentLocation() {
        const u = new URL(window.location.href);
        // cambiamos a ruta contenido (AJAX)
        const contenidoBase = "{{ route('backoffice.reserva.contenido') }}";
        const out = new URL(contenidoBase, window.location.origin);

        // copiamos query actual (page, alternative, mobileview, etc.)
        u.searchParams.forEach((v, k) => out.searchParams.set(k, v));

        return out.toString();
    }

    function loadContenido(pushStateUrl = null) {
        const url = buildContenidoUrlFromCurrentLocation();

        cont.innerHTML = `
            <div class="center" style="padding:20px;">
                <div class="preloader-wrapper active">
                    <div class="spinner-layer spinner-blue-only">
                        <div class="circle-clipper left"><div class="circle"></div></div>
                        <div class="gap-patch"><div class="circle"></div></div>
                        <div class="circle-clipper right"><div class="circle"></div></div>
                    </div>
                </div>
                <p>Cargando reservas...</p>
            </div>
        `;

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
            .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.text(); })
            .then(html => {
                cont.innerHTML = html;

                // Si tu contenido tiene elementos Materialize que requieran init extra, hazlo aquí:
                // $('.tooltipped').tooltip(); etc.

                // re-inicializar modales si los includes usan modal-trigger
                if (window.M && typeof M.AutoInit === 'function') {
                    // OJO: AutoInit puede duplicar cosas, úsalo solo si lo necesitas
                    // M.AutoInit();
                }
            })
            .catch(err => {
                console.error(err);
                cont.innerHTML = `<p class="red-text">Error cargando reservas: ${err.message}</p>`;
            });

        if (pushStateUrl) {
            window.history.pushState({}, '', pushStateUrl);
        }
    }

    // 1) carga inicial AJAX
    loadContenido();

    // 2) interceptar clicks de paginación dentro del contenido
    document.addEventListener('click', function (e) {
        const a = e.target.closest('#reservas-content .pagination a');
        if (!a) return;

        e.preventDefault();

        // a.href contiene ?page=N&alternative=... etc
        const params = currentParamsFromUrl(a.href);

        // aplicamos esos params a la URL actual (para mantener comportamiento)
        const current = new URL(window.location.href);
        params.forEach((v, k) => current.searchParams.set(k, v));

        // limpiamos si no viene alguno (opcional)
        // current.searchParams.delete('page') ... etc

        window.history.pushState({}, '', current.toString());
        loadContenido(); // recarga contenido según la URL actual
    });

    // 3) back/forward del navegador
    window.addEventListener('popstate', function () {
        loadContenido();
    });

});
</script>


@endsection