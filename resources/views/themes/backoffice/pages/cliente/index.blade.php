@extends('themes.backoffice.layouts.admin')

@section('title','Nuestros Clientes')

@section('head')
@endsection


@section('breadcrumbs')
<li><a href="{{route('backoffice.cliente.index')}}">Nuestros Clientes</a></li>
@endsection


@section('dropdown_settings')
<li><a href="{{route ('backoffice.cliente.create') }}" class="grey-text text-darken-2">Crear Cliente</a></li> 
<!-- <li><a href="" class="grey-text text-darken-2">Crear Usuario</a></li> -->
@endsection


@section('content')

<div class="section">
              <p class="caption"><strong>Nuestros Clientes</strong></p>
              <div class="divider"></div>
              <div id="basic-form" class="section">
                <div class="row">
                  <div class="col s12 ">
                    <div class="card-panel">
                     
                      <div class="row">


                      <table>
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Correo</th>
                                    <th>Whatsapp</th>
                                    <th>Instagram</th>
                                    <th colspan="2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($clientes as $cliente )
                                <tr>
                                  <td class="valign-wrapper">@if ($cliente->lista_negra == true)
                                    <i class='material-icons red-text'>lock</i>
                                  @endif <a href="{{route('backoffice.cliente.show' ,$cliente )}}" class="{{$cliente->lista_negra == true ? 'red-text' : ''}}"> {{$cliente->nombre_cliente}}</a></td>
                                  <td><a href="mailto:{{$cliente->correo}}" class="{{$cliente->lista_negra == true ? 'red-text' : ''}}">{{$cliente->correo}}</a></td>

                                  <td>@if (is_null($cliente->whatsapp_cliente))
                                    No Registra
                                    @else
                                    <a href="https://api.whatsapp.com/send?phone={{$cliente->whatsapp_cliente}}" class="{{$cliente->lista_negra == true ? 'red-text' : ''}}" target="_blank">+{{$cliente->whatsapp_cliente}}</a>
                                  @endif</td>


                                  <td>@if (is_null($cliente->instagram_cliente))
                                    No Registra
                                    @else
                                    <a href="https://www.instagram.com/{{$cliente->instagram_cliente}}" class="{{$cliente->lista_negra == true ? 'red-text' : ''}}" target="_blank">{{$cliente->instagram_cliente}}</a>
                                  @endif</td>
                                  
                                  <td><a href="{{ route('backoffice.cliente.edit', $cliente )}}"><i class="material-icons">mode_edit</i> Editar</a></td>
                                </tr>
                                @endforeach
                              </tbody>
                            </table>

                      </div>
                    </div>
                  </div>
                </div>
              </div>
</div>
@endsection


@section('foot')
<script>
(function () {
    var input   = document.getElementById('search');
    var results = document.getElementById('header-search-results');
    if (!input || !results) return;

    var debounceTimer = null;
    var currentXhr     = null;
    var showUrl        = "{{ route('backoffice.cliente.show', ['cliente' => '__ID__']) }}";

    function hideResults() {
        results.style.display = 'none';
        results.innerHTML = '';
    }

    function renderResults(clientes) {
        results.innerHTML = '';

        if (!clientes.length) {
            var empty = document.createElement('li');
            empty.className = 'collection-item grey-text';
            empty.style.cssText = 'float:none; display:block; width:100%;';
            empty.textContent = 'Sin coincidencias';
            results.appendChild(empty);
            results.style.display = 'block';
            return;
        }

        clientes.forEach(function (cliente) {
            var item = document.createElement('li');
            item.className = 'collection-item' + (cliente.bloqueado ? ' red-text' : '');
            item.style.cssText = 'float:none; display:block; width:100%; cursor:pointer;';
            item.textContent = cliente.nombre_cliente
                + (cliente.correo ? ' — ' + cliente.correo : '')
                + (cliente.whatsapp_cliente ? ' — +' + cliente.whatsapp_cliente : '');

            item.addEventListener('click', function () {
                window.location.href = showUrl.replace('__ID__', cliente.id);
            });

            results.appendChild(item);
        });

        results.style.display = 'block';
    }

    input.addEventListener('input', function () {
        var query = input.value.trim();

        clearTimeout(debounceTimer);

        if (query.length < 2) {
            hideResults();
            return;
        }

        debounceTimer = setTimeout(function () {
            if (currentXhr) currentXhr.abort();

            currentXhr = $.ajax({
                url: "{{ route('backoffice.cliente.index') }}",
                method: 'GET',
                data: { search: query },
                dataType: 'json'
            }).done(function (clientes) {
                renderResults(clientes);
            });
        }, 250);
    });

    document.addEventListener('click', function (e) {
        if (e.target !== input && !results.contains(e.target)) {
            hideResults();
        }
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') hideResults();
    });
})();
</script>
@endsection


