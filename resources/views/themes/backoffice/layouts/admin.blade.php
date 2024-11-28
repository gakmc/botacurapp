<!DOCTYPE html>
<html lang="es">
    <head>
    <title>@yield('title')</title>
    
    @include('themes.backoffice.layouts.includes.head')
    </head>
    <body>
        @include('themes.backoffice.layouts.includes.loader')
        @include('themes.backoffice.layouts.includes.header')
        <div id="main">
            <div class="wrapper">
                @include('themes.backoffice.layouts.includes.left-sidebar')
                <section id="content">
                    @include('themes.backoffice.layouts.includes.breadcrumbs')
                    <div class="container">
                        @yield('content')
                    </div>

                    
                </section>
            </div>
        </div>

        @include('themes.backoffice.layouts.includes.footer')
        
        @include('themes.backoffice.layouts.includes.foot')
        @yield('foot')
        <script src="{{ mix('js/app.js') }}"></script>

    </body>
</html>