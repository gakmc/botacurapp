@extends('themes.backoffice.layouts.admin')

@section('title','Panel de Administración')

@section('head')
@endsection

@section('breadcrumbs')
<li>Masajes Asignados</li>
@endsection


@section('dropdown_settings')
@endsection

@section('content')

<div class="section">
    <p class="caption"><strong>Masajes</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 ">
                <div class="card-panel">
                    <div class="row">



                        {{-- CONTENIDO --}}

                        <div class="col s12">
                            <div class="card">
                                <div class="card-content blue white-text">
                                    <h5 id="titulo"></h5>
                                </div>
                                <div class="card-tabs">
                                    <ul class="tabs tabs-fixed-width">
                                        @foreach ($masoterapeutas as $masoterapeuta)
                                            <li class="tab"><a id="seleccion" href="#masoterapeuta-{{ $masoterapeuta->id }}">{{ $masoterapeuta->name }}</a></li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="card-content grey lighten-4">
                                    @foreach ($masoterapeutas as $masoterapeuta)
                                        <div id="masoterapeuta-{{ $masoterapeuta->id }}">
                                            <p><strong>Total de esta Semana: {{ $cantidadMasajesPorSemana[$masoterapeuta->id] }}</strong></p>
                        
                                            <h6>Masajes Realizados por Día</h6>
                                            <ul>
                                                @foreach ($cantidadMasajesPorDia[$masoterapeuta->id] as $dia => $cantidad)
                                                    <li>{{ $dia }}: {{ $cantidad }} masajes</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>






                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


@section('foot')
<script>
$(document).ready(function () {
    let seleccion = $('#seleccion').text();

    let nombreInicial = $('.tabs .tab a.active').text();
    if (!nombreInicial) {
        nombreInicial = $('.tabs .tab a:first').text();
    }
    $('#titulo').text(nombreInicial);

    $('.tabs .tab a').on('click',function(e){
        e.preventDefault();
        let nombreMaso = $(this).text();
        $('#titulo').text(nombreMaso);
    });
});
</script>

@endsection