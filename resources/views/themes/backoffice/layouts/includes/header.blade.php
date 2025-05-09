<header class="page-topbar" id="header">
    <div class="navbar-fixed">
        <nav class="navbar-color gradient-45deg-light-blue-cyan">
            <div class="nav-wrapper">

                <ul class="left">
                    <li>
                        <h1 class="logo-wrapper">
                            <a class="brand-logo darken-1" href="index.html">
                                <img alt="botacura logo" src="/images/logo/icono.png" >
                                <span class="logo-text hide-on-med-and-down">BotacurApp</span>
                            </a>
                        </h1>
                    </li>
                </ul>

                <div class="header-search-wrapper hide-on-med-and-down">
                    <form action="" class="">
                    <i class="material-icons">search</i>
                    <input class="header-search-input z-depth-2" name="search" placeholder="¿Qué deseas buscar?" type="text"/>
                    </form>
                </div>

                <ul class="right hide-on-med-and-down">
                    
                    <li>
                        <a class="waves-effect waves-block waves-light toggle-fullscreen" href="javascript:void(0);">
                            <i class="material-icons">settings_overscan</i>
                        </a>
                    </li>
                    <li>
                        <a class="waves-effect waves-block waves-light profile-button dropdown-trigger" data-activates="profile-dropdown" data-target="profile-dropdown" href="javascript:void(0);">
                            <span class="avatar-status avatar-online">
                                <img alt="avatar" src="/images/avatar/avatar-7.png">
                                <i></i>
                            </span>
                        </a>
                    </li>

                </ul>

                <!-- profile-dropdown -->
                <ul class="dropdown-content" id="profile-dropdown">
                    <li>
                        <a class="grey-text text-darken-1" href="#">
                            <i class="material-icons">face</i>
                            Perfil
                        </a>
                    </li>
                    @if (Auth::user()->has_any_role([config('app.garzon_role') , config('app.anfitriona_role') , config('app.barman_role'), config('app.cocina_role'), config('app.jefe_local_role')]))     
                    <li>
                        <a class="grey-text text-darken-1" href="{{route('backoffice.sueldo.view', Auth::user())}}">
                            <i class="material-icons">account_balance_wallet</i>
                            Estado de cuenta
                        </a>
                    </li>
                    @endif
                    @if (Auth::user()->has_any_role([config('app.masoterapeuta_role')]))     
                    <li>
                        <a class="grey-text text-darken-1" href="{{route('backoffice.sueldo.view_maso', Auth::user())}}">
                            <i class="material-icons">account_balance_wallet</i>
                            Estado de cuenta
                        </a>
                    </li>
                    @endif
                    <li>
                        <a class="grey-text text-darken-1" href="#">
                            <i class="material-icons">settings</i>
                            Ajustes
                        </a>
                    </li>
                    <li>
                        <a class="grey-text text-darken-1" href="#">
                            <i class="material-icons">live_help</i>
                            Ayuda
                        </a>
                    </li>
                    <li class="divider"></li>
                    <li>
                        <a class="grey-text text-darken-1" href="{{ route('logout') }}" onclick="event.preventDefault();
                  document.getElementById('logout-form').submit();">
                            <i class="material-icons">keyboard_tab</i>
                            {{ __('Logout') }}
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>



 <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
     @csrf
 </form>
</header>