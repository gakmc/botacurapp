<div id="breadcrumbs-wrapper">
    <!-- Search for small screen -->
    <div class="header-search-wrapper grey lighten-2 hide-on-large-only">
        <input type="text" name="Search" class="header-search-input z-depth-2" placeholder="Explore Materialize">
    </div>
    <div class="container">
        <div class="row">
            <div class="col s10 m6 l6">
                <h5 class="breadcrumbs-title">@yield('title')</h5>
                <ol class="breadcrumbs">
                    <li><a href="/home">Panel de Administración</a></li>
                    @yield('breadcrumbs')
                </ol>
            </div>

            <div class="col s2 m6 l6">
                @hasSection('dropdown_settings')
                    <a class="btn dropdown-settings waves-effect waves-light breadcrumbs-btn hide-on-small-only hide-on-med-only right" href="#!"
                        data-activates="dropdown1" >
                        {{-- data-target="dropdown1" --}}
                        <i class="material-icons hide-on-med-and-up">settings</i>
                        <span class="">Acciones</span>
                        <i class="material-icons right">arrow_drop_down</i>
                    </a>
                    <ul id="dropdown1" class="dropdown-content"
                        style="white-space: nowrap; opacity: 1; left: 1735.67px; position: absolute; top: 130px; display: none;">
                        @yield('dropdown_settings')
                    </ul>


                    <a class="btn modal-trigger waves-effect waves-light hide-on-large-only right" href="#modal-acciones">
                        <i class="material-icons">settings</i>
                    </a>

                    <!-- Modal estructurado -->
                    <div id="modal-acciones" class="modal">
                        <div class="modal-content">
                            {{-- <h5>Acciones</h5> --}}
                            <ul class="collection with-header">
                                <li class="collection-header"><h5>Acciones</h5></li>
                                @yield('dropdown_settings')
                            </ul>
                        </div>
                    </div>
                @endif
            </div>


        </div>
    </div>
</div>
