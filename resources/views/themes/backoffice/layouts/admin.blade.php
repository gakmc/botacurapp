<!DOCTYPE html>
<html lang="es">
    <head>
    <title>@yield('title')</title>
    <link rel="shortcut icon" href="{{asset('images/logo/icono.png')}}" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    @include('themes.backoffice.layouts.includes.head')
    </head>

    <style>
        body::-webkit-scrollbar{
            width: 10px;
            height: 10px;
        }

        body::-webkit-scrollbar-thumb{
            background: #3B82F6;
            border: 3px solid #fff;
            border-radius: 10px;
        }


    </style>
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
        {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script> --}}
        @include('themes.backoffice.layouts.includes.foot')
        @yield('foot')
        <script src="{{ asset('js/app.js') }}"></script>
        {{-- <script src="{{ mix('js/app.js') }}"></script> --}}

{{-- <script>console.log('Como?')</script> --}}
    </body>
</html>