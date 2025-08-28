<aside id="left-sidebar-nav">
    <ul class="side-nav fixed leftside-navigation" id="slide-out">
        <li class="user-details cyan darken-2">
            <div class="row">
                <div class="col col s4 m4 l4">
                    <img alt="" class="circle responsive-img valign profile-image cyan"
                        src="/images/avatar/avatar-7.png" />
                </div>
                <div class="col col s8 m8 l8">
                    <ul class="dropdown-content" id="profile-dropdown-nav">
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
                                Pagos
                            </a>
                        </li>
                        @endif
                        @if (Auth::user()->has_any_role([config('app.masoterapeuta_role')]))     
                        <li>
                            <a class="grey-text text-darken-1" href="{{route('backoffice.sueldo.view_maso', Auth::user())}}">
                                <i class="material-icons">account_balance_wallet</i>
                                Pagos
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
                    <a class="btn-flat dropdown-button waves-effect waves-light white-text profile-btn dropdown-trigger"
                        data-activates="profile-dropdown-nav"  href="#">
                        {{Auth::user()->name}}
                        <i class="mdi-navigation-arrow-drop-down right"></i>
                    </a>
                    <p class="user-roal">{{Auth::user()->list_roles()}}</p>
                </div>
            </div>
        </li>
        <li class="no-padding">
            <ul class="collapsible" data-collapsible="accordion">
                <li class="bold hide-on-med-and-up">
                    <a class="waves-effect waves-cyan" href="{{ route ('backoffice.admin.menu') }}">
                        <i class="material-icons">
                            apps
                        </i>
                        <span class="nav-text">
                            Panel del Usuario
                        </span>
                    </a>
                </li>

                @if(Auth::user()->has_role(config('app.admin_role')) || Auth::user()->has_role(config('app.anfitriona_role')) || Auth::user()->has_role(config('app.jefe_local_role')))
                <li class="bold">
                    <a class="waves-effect waves-cyan" href="{{ route ('backoffice.admin.show') }}">
                        <i class="material-icons">
                            pie_chart_outlined
                        </i>
                        <span class="nav-text">
                            Panel de administración
                        </span>
                    </a>
                </li>

                @endif

                @if (Auth::user()->has_role(config('app.admin_role')) || Auth::user()->has_role(config('app.jefe_local_role')))
                    

                <li class="bold">
                    <a class="waves-effect waves-cyan" href="{{ route ('backoffice.cliente.index') }}">
                        <i class="material-icons">
                            airport_shuttle
                        </i>
                        <span class="nav-text">
                            Clientes
                        </span>
                    </a>
                </li>

                {{-- <li class="bold">
                    <a class="waves-effect waves-cyan" href="{{ route ('backoffice.reservas.registros') }}">
                        <i class="material-icons">
                            assignment_ind
                        </i>
                        <span class="nav-text">
                            Reservas
                        </span>
                    </a>
                </li> --}}

                @endif
                
                @if(Auth::user()->has_role(config('app.admin_role')) || Auth::user()->has_role(config('app.anfitriona_role')) || Auth::user()->has_role(config('app.jefe_local_role')) || Auth::user()->has_role(config('app.garzon_role')))
                <li class="bold">
                    <a class="waves-effect waves-cyan" href="{{ 
                        Auth::user()->has_role(config('app.garzon_role')) || Auth::user()->has_role(config('app.anfitriona_role')) 
                        ? route ('backoffice.reservas.registro',["fecha" => now()->format('d-m-Y')]) 
                        : route ('backoffice.reservas.registros') }}">
                        <i class="material-icons">
                            assignment_ind
                        </i>
                        <span class="nav-text">
                            Reservas
                        </span>
                    </a>
                </li>
                @endif

                @if(Auth::user()->has_role(config('app.admin_role')) || Auth::user()->has_role(config('app.anfitriona_role')) || Auth::user()->has_role(config('app.jefe_local_role')))

                <li class="bold">
                    <a class="waves-effect waves-cyan" href="{{ route ('backoffice.reserva.index') }}">
                        <i class="material-icons">
                            assignment
                        </i>
                        <span class="nav-text">
                            Horarios
                        </span>
                    </a>
                </li>

                @endif

                @if(Auth::user()->has_role(config('app.garzon_role')) || Auth::user()->has_role(config('app.anfitriona_role')) || Auth::user()->has_role(config('app.admin_role')) || Auth::user()->has_role(config('app.jefe_local_role')))

                    <li class="bold">
                        <a class="waves-effect waves-cyan" href="{{ route ('backoffice.reserva.venta.cierre') }}">
                            <i class="material-icons">
                                local_drink
                            </i>
                            <span class="nav-text">
                                Gestion Consumo
                            </span>
                        </a>
                    </li>

                @endif

                @if(Auth::user()->has_role(config('app.admin_role')) || Auth::user()->has_role(config('app.jefe_local_role')))
                    <li class="bold">
                        <a class="waves-effect waves-cyan" href="{{ route ('backoffice.programa.index') }}">
                            <i class="material-icons">
                                style
                            </i>
                            <span class="nav-text">
                                Programas
                            </span>
                        </a>
                    </li>
                    <li class="bold">
                        <a class="waves-effect waves-cyan" href="{{ route ('backoffice.servicio.index') }}">
                            <i class="material-icons">
                                room_service
                            </i>
                            <span class="nav-text">
                                Servicios
                            </span>
                        </a>
                    </li>

                @endif

                @if (Auth::user()->has_role(config('app.admin_role')) || Auth::user()->has_role(config('app.cocina_role')) || Auth::user()->has_role(config('app.jefe_local_role'))) 
                    <li class="bold">
                        <a class="waves-effect waves-cyan" href="{{ route ('backoffice.insumo.index') }}">
                            <i class="material-icons">
                                store
                            </i>
                            <span class="nav-text">
                                Insumos
                            </span>
                        </a>
                    </li>

                    <li class="bold">
                        <a class="waves-effect waves-cyan" href="{{ route ('backoffice.producto.index') }}">
                            <i class="material-icons">
                                shopping_basket
                            </i>
                            <span class="nav-text">
                                Productos
                            </span>
                        </a>
                    </li>
                @endif


                @if (Auth::user()->has_role(config('app.cocina_role')) || Auth::user()->has_role(config('app.garzon_role')) || Auth::user()->has_role(config('app.admin_role')))
                    <li class="bold">
                        <a class="waves-effect waves-cyan" href="{{ route ('backoffice.menu.index') }}">
                            <i class="material-icons">
                                restaurant
                            </i>
                            <span class="nav-text">
                                Menús
                            </span>
                        </a>
                    </li>
                @endif

                @if (Auth::user()->has_role(config('app.barman_role')) || Auth::user()->has_role(config('app.admin_role')) || Auth::user()->has_role(config('app.jefe_local_role')))
                    <li class="bold">
                        <a class="waves-effect waves-cyan" href="{{ route ('backoffice.barman.index') }}">
                            <i class="material-icons">
                                local_bar
                            </i>
                            <span class="nav-text">
                                Bebidas
                            </span>
                        </a>
                    </li>
                @endif

                @if (Auth::user()->has_role(config('app.garzon_role')))
                    <li class="bold">
                        <a class="waves-effect waves-cyan" href="{{ route ('backoffice.barman.bebidas') }}">
                            <i class="material-icons">
                                local_bar
                            </i>
                            <span id="bebidasGarzon" class="nav-text">
                                Bebidas 
                            </span>
                        </a>
                    </li>
                @endif

                @if(Auth::user()->has_role(config('app.admin_role')) || Auth::user()->has_role(config('app.masoterapeuta_role')) || Auth::user()->has_role(config('app.jefe_local_role')))

                    <li class="bold">
                        <a class="waves-effect waves-cyan" href="{{ route ('backoffice.masaje.index') }}">
                            <i class="material-icons">
                                airline_seat_flat
                            </i>
                            <span class="nav-text">
                                Masajes
                            </span>
                        </a>
                    </li>

                @endif
                
                @if(Auth::user()->has_role(config('app.admin_role')) || Auth::user()->has_role(config('app.anfitriona_role')) || Auth::user()->has_role(config('app.garzon_role')) || Auth::user()->has_role(config('app.jefe_local_role')))

                    <li class="bold">
                        <a class="waves-effect waves-cyan" href="{{ route ('backoffice.venta_directa.index') }}">
                            <i class="material-icons">
                                local_mall
                            </i>
                            <span class="nav-text">
                                Venta Directa
                            </span>
                        </a>
                    </li>


                    <li class="bold">
                        <a class="waves-effect waves-cyan" href="{{ route ('backoffice.ventas_poroporo.index') }}">
                            <i class="material-icons">
                                local_florist
                            </i>
                            <span class="nav-text">
                                Poro Poro
                            </span>
                        </a>
                    </li>



                @endif

                @if (Auth::user()->has_role(config('app.admin_role')))
                
                <li class="bold">
                    <a class="waves-effect waves-cyan" href="{{ route ('backoffice.informes.index') }}">
                        <i class="material-icons">
                            assessment
                        </i>
                        <span class="nav-text">
                            Informes
                        </span>
                    </a>
                </li>

                @endif
                
                @if (Auth::user()->has_role(config('app.admin_role')) || Auth::user()->has_role(config('app.jefe_local_role')))
                    <li class="bold">
                        <a class="waves-effect waves-cyan" href="{{ route ('backoffice.user.index') }}">
                            <i class="material-icons">
                                people
                            </i>
                            <span class="nav-text">
                                Usuarios del Sistema
                            </span>
                        </a>
                    </li>

                    <li class="bold">
                        <a class="waves-effect waves-cyan" href="{{ route ('backoffice.role.index') }}">
                            <i class="material-icons">
                                perm_identity
                            </i>
                            <span class="nav-text">
                                Roles del Sistema
                            </span>
                        </a>
                    </li>

                    <li class="bold">
                        <a class="waves-effect waves-cyan" href="{{ route ('backoffice.permission.index') }}">
                            <i class="material-icons">
                                vpn_key
                            </i>
                            <span class="nav-text">
                                Permisos del Sistema
                            </span>
                        </a>
                    </li>

                @endif

                @if (Auth::user()->has_role(config('app.admin_role')) || Auth::user()->has_role(config('app.jefe_local_role')))

                    <li class="bold">
                        <a class="waves-effect waves-cyan" href="{{ route ('backoffice.asignacion.index') }}">
                            <i class="material-icons">
                                person_add
                            </i>
                            <span class="nav-text">
                                Asignacion de turno
                            </span>
                        </a>
                    </li>

                @endif


                @if (Auth::user()->has_role(config('app.admin_role')) || Auth::user()->has_role(config('app.jefe_local_role')))


                    @if (Auth::user()->has_role(config('app.jefe_local_role')))
                        <li class="bold">
                            <a class="waves-effect waves-cyan" href="{{ route ('backoffice.egreso.create') }}">
                                <i class="material-icons">
                                    show_chart
                                </i>
                                <span class="nav-text">
                                    Egresos
                                </span>
                            </a>
                        </li>
                        
                    @else
                        <li class="bold">
                            <a class="waves-effect waves-cyan" href="{{ route ('backoffice.egreso.index') }}">
                                <i class="material-icons">
                                    show_chart
                                </i>
                                <span class="nav-text">
                                    Egresos
                                </span>
                            </a>
                        </li>
                        
                    @endif

                    <li class="bold">
                        <a class="waves-effect waves-cyan" href="{{ route ('backoffice.complemento.index') }}">
                            <i class="material-icons">
                                data_usage
                            </i>
                            <span class="nav-text">
                                Complementos
                            </span>
                        </a>
                    </li>

                    <li class="bold">
                        <a class="waves-effect waves-cyan" href="{{ route ('backoffice.cotizacion.index') }}">
                            <i class="material-icons">
                                local_atm
                            </i>
                            <span class="nav-text">
                                Cotizaciones
                            </span>
                        </a>
                    </li>

                    <li class="bold">
                        <a class="waves-effect waves-cyan" href="{{ route ('backoffice.giftcards.index') }}">
                            <i class="material-icons">
                                redeem
                            </i>
                            <span class="nav-text">
                                Gift Cards
                            </span>
                        </a>
                    </li>

                @endif



            </ul>
        </li>
    </ul>

    <a class="sidebar-collapse sidenav-trigger btn-floating btn-medium waves-effect waves-light hide-on-large-only"
        data-activates="slide-out" data-target="slide-out" href="#">
        <i class="material-icons">menu</i>
    </a>


    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>
</aside>
