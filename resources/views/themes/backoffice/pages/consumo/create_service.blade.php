@extends('themes.backoffice.layouts.admin')

@section('title', 'Ingresar Consumo')

@section('head')
<style>
  /* Solo en este formulario */
  #basic-form .card,
  #basic-form .card-content { overflow: visible !important; }

  /* Asegura que el ul del select pase por encima */
  ul.select-dropdown { z-index: 1005 !important; }
</style>

@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.reserva.show', $venta->id_reserva) }}">Servicios para reserva del cliente</a></li>
<li>Ingresar Servicios Extra</li>
@endsection

@section('content')

<div class="section">
    <p class="caption">Ingrese los datos del servicios extra para la venta.</p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 m8 offset-m2">
                <div class="card-panel">
                    <h4 class="header">Servicios extra para la venta de
                        <strong>{{$venta->reserva->cliente->nombre_cliente}}</strong>
                    </h4>
                    
                    <div class="row">
                        <form class="col s12" method="post"
                            action="{{route('backoffice.venta.consumo.service_store', $venta)}}">
                            {{csrf_field()}}



                            <div class="row">
                                <div class="input-field col s12 m6 l4" hidden>
                                    <input id="id_venta" type="hidden" name="id_venta" value="{{$venta->id}}" required>
                                </div>

                                <div class="card">
                                    <div class="card-content gradient-45deg-light-blue-cyan">
                                        <h5 class="white-text">Selecciona tus servicios</h5>
                                    </div>


                                    <div class="card-tabs">
                                        <ul class="tabs tabs-fixed-width">
                                            @foreach($servicios as $servicio)
                                            <li class="tab">
                                                {{-- <a href="" class="servicio" data-id="{{ $servicio->id }}"
                                                    data-nombre="{{ $servicio->nombre_servicio }}"
                                                    data-precio="{{ $servicio->valor_servicio }}">{{
                                                    $servicio->nombre_servicio }}</a> --}}

                                                <a href="" class="servicio"
                                                    data-id="{{ $servicio->id }}"
                                                    data-nombre="{{ $servicio->nombre_servicio }}"
                                                    data-precio="{{ $servicio->valor_servicio }}"
                                                    data-slug="{{ $servicio->slug }}">
                                                    {{ $servicio->nombre_servicio }}
                                                </a>
                                            </li>
                                            @endforeach
                                        </ul>
                                    </div>

                                    <div class="card-content grey lighten-4">
                                        <!-- Aquí se mostrarán los servicios seleccionados -->
                                        <div id="servicios_seleccionados"></div>
                                    </div>
                                </div>



                            </div>

                    </div>








                    <div class="row">
                        <div class="input-field col s12">
                            <button id="btn-guardar" class="btn waves-effect waves-light right" type="submit">Guardar
                                <i class="material-icons right">send</i>
                            </button>
                        </div>
                    </div>
                    </form>
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
    $('form').on('submit', function (){
      const $btn = $('#btn-guardar');
      $btn.prop('disabled', true);
      $btn.html('<i class="material-icons left">hourglass_empty</i>Guardando...');
    });
  });
</script>

<script>
    function eliminarServicio (id) { 
        Swal.fire({
        title: '¿Estás seguro?',
        text: "¡No podrás revertir esta acción!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminarlo'
    }).then((result) => {
        if (result.isConfirmed) {
            $('#servicio_' + id).remove();
            Swal.fire(
                'Eliminado',
                'El servicio ha sido eliminado.',
                'success'
            );
        }
    });
     }

    // $(document).ready(function() {
    //     // Inicializar manualmente los tabs de Materialize
    //     $('ul.tabs').tabs();



    //     // Desasociar cualquier evento anterior y luego asociar el evento click
    //     $('.servicio').off('click').on('click', function(event) {
    //         event.preventDefault(); // Evitar el comportamiento por defecto de los tabs

    //         // Obtener los datos del servicio seleccionado
    //         var id = $(this).data('id');
    //         var nombre = $(this).data('nombre');
    //         var precio = $(this).data('precio');

    //         var esMasaje = nombre.toLowerCase().includes('masaje');

    //         var extraHTML = esMasaje ? `
    //             <div class="col s12">
    //                 <label>
    //                     <input type="checkbox" name="servicios[${id}][tiempo_extra_actual]" />
    //                     <span>¿Agregar tiempo a masaje actual (30 min)?</span>
    //                 </label>
    //                 <label>
    //                     <input type="checkbox" name="servicios[${id}][tiempo_extra]" />
    //                     <span>¿Agregar masaje extra (1 hora)?</span>
    //                 </label>
    //             </div>
    //         ` : '';



    //         // Verificar si el servicio ya fue seleccionado
    //         if ($('#servicio_' + id).length === 0) {
    //             // Si no ha sido seleccionado, añadirlo a la lista de servicios seleccionados
    //             $('#servicios_seleccionados').append(
    //                 '<div class="row" id="servicio_' + id + '">' +
    //                     '<div class="col s1">' +
    //                         '<a href="javascript:void(0);" class="" onclick="eliminarServicio('+ id +')"><i class="material-icons" style="padding-top:25px; color:red;">clear</i></a>'+
    //                     '</div>'+
    //                     '<div class="col s3">' +
    //                         '<blockquote><h5>' + nombre + '</h5></blockquote>' +
    //                     '</div>' +
    //                     '<div class="col s4">' +
    //                         '<h5>$' + precio + '</h5>' +
    //                     '</div>' +
    //                     '<div class="col s4">' +
    //                         '<input type="number" name="servicios[' + id + '][cantidad]" placeholder="Cantidad" min="1">' +
    //                         '<input type="hidden" name="servicios[' + id + '][precio]" value="' + precio + '">' +
    //                     '</div>' + extraHTML +
    //                 '</div>'
    //             );

    //         } else {
    //             // Si ya fue seleccionado, simplemente ignorar o mostrar mensaje
    //             // Swal.fire({
    //                 //     icon: 'warning',
    //                 //     title: 'Servicio ya seleccionado',
    //             //     text: 'Este servicio ya ha sido agregado a la lista.',
    //             //     confirmButtonText: 'OK'
    //             // });
                
    //             const Toast = Swal.mixin({
    //                 toast: true,
    //                 position: "top",
    //                 showConfirmButton: false,
    //                 timer: 3000,
    //                 timerProgressBar: true,
    //                 didOpen: (toast) => {
    //                     toast.onmouseenter = Swal.stopTimer;
    //                     toast.onmouseleave = Swal.resumeTimer;
    //                 }
    //             });
                
    //             Toast.fire({
    //                 icon: "error",
    //                 title: "El servicio ya fue incorporado a la lista, agregue la cantidad"
    //             });
                
    //         }

    //     });

    // });
</script>


{{-- <script>
  // Catálogo completo desde el backend
  var CATALOGO = @json($catalogoMasajes);

  // Helpers ES5 (compatibles)
  function encontrarCategoriaPorSlug(slug){
    for (var i=0;i<CATALOGO.length;i++){
      if (CATALOGO[i].slug === slug) return CATALOGO[i];
    }
    return null;
  }
  function tiposDeCategoria(slugCat){
    var cat = encontrarCategoriaPorSlug(slugCat);
    return cat ? (cat.tipos || []) : [];
  }
  function encontrarTipoPorSlug(slugTipo){
    for (var i=0;i<CATALOGO.length;i++){
      var tipos = CATALOGO[i].tipos || [];
      for (var j=0;j<tipos.length;j++){
        if (tipos[j].slug === slugTipo) return tipos[j];
      }
    }
    return null;
  }

  function materializeInitSelect(selector){
    // Re-init individual para selects dinámicos (M 0.100.2)
    try { $(selector).material_select(); } catch(e) {}
  }

  // Construye los selects de Masaje para un servicio dado (id del servicio "Masaje")
  function renderControlesMasaje(id){
    var html =
      '<div class="row masaje-config" id="masaje_cfg_'+id+'">' +

        // Categoría
        '<div class="input-field col s12 m4">'+
          '<select id="categoria_'+id+'" name="servicios['+id+'][categoria_slug]">'+
            '<option value="" disabled selected>Elige una categoría</option>'+
            CATALOGO.map(function(cat){
              return '<option value="'+cat.slug+'">'+cat.nombre+'</option>';
            }).join('')+
          '</select>'+
          '<label>Categoría de masaje</label>'+
        '</div>'+

        // Tipo
        '<div class="input-field col s12 m4">'+
          '<select id="tipo_'+id+'" name="servicios['+id+'][slug_tipo_masaje]" disabled>'+
            '<option value="" disabled selected>Elige un tipo</option>'+
          '</select>'+
          '<label>Tipo de masaje</label>'+
        '</div>'+

        // Duración
        '<div class="input-field col s12 m4">'+
          '<select id="duracion_'+id+'" name="servicios['+id+'][duracion]" disabled>'+
            '<option value="" disabled selected>Elige duración</option>'+
          '</select>'+
          '<label>Duración</label>'+
        '</div>'+

        // Info de precios y total estimado
        '<div class="col s12">'+
          '<small id="precio_info_'+id+'" class="grey-text"></small><br>'+
          '<small id="total_estimado_'+id+'" class="grey-text text-darken-2"></small>'+
        '</div>'+

        // Opcional: flags para tu lógica de crear/actualizar masajes
        '<div class="col s12" style="margin-top:8px">'+
          '<label>'+
            '<input type="checkbox" name="servicios['+id+'][tiempo_extra_actual]">'+
            '<span>Subir a 60 min a masajes existentes (hasta la cantidad)</span>'+
          '</label><br>'+
          '<label>'+
            '<input type="checkbox" name="servicios['+id+'][tiempo_extra]">'+
            '<span>Crear nuevos de 60 min (si faltan)</span>'+
          '</label>'+
        '</div>'+

      '</div>';

    return html;
  }

  function poblarTipos(id, slugCat){
    var tipos = tiposDeCategoria(slugCat);
    var $select = $('#tipo_'+id);
    $select.prop('disabled', false).empty()
      .append('<option value="" disabled selected>Elige un tipo</option>');
    for (var i=0;i<tipos.length;i++){
      $select.append('<option value="'+tipos[i].slug+'">'+tipos[i].nombre+'</option>');
    }
    materializeInitSelect('#tipo_'+id);
    // Vacía duración hasta que elijan tipo
    $('#duracion_'+id).prop('disabled', true).empty()
      .append('<option value="" disabled selected>Elige duración</option>');
    materializeInitSelect('#duracion_'+id);
    // Limpia infos
    $('#precio_info_'+id).text('');
    $('#total_estimado_'+id).text('');
  }

  function poblarDuraciones(id, slugTipo){
    var tipo = encontrarTipoPorSlug(slugTipo);
    var $select = $('#duracion_'+id);
    $select.prop('disabled', false).empty()
      .append('<option value="" disabled selected>Elige duración</option>');
    if (tipo && tipo.precios){
      for (var i=0;i<tipo.precios.length;i++){
        var p = tipo.precios[i];
        $select.append('<option value="'+p.duracion_minutos+'">'+p.duracion_minutos+' min</option>');
      }
    }
    materializeInitSelect('#duracion_'+id);
    // Limpia infos
    $('#precio_info_'+id).text('');
    $('#total_estimado_'+id).text('');
  }

  function mostrarInfoPrecio(id){
    var slugTipo = $('#tipo_'+id).val();
    var dur      = parseInt($('#duracion_'+id).val() || '0', 10);
    var tipo     = encontrarTipoPorSlug(slugTipo);
    if (!tipo || !tipo.precios) return;

    var unit=null, pair=null;
    for (var i=0;i<tipo.precios.length;i++){
      if (parseInt(tipo.precios[i].duracion_minutos,10) === dur){
        unit = parseInt(tipo.precios[i].precio_unitario,10);
        pair = tipo.precios[i].precio_pareja ? parseInt(tipo.precios[i].precio_pareja,10) : null;
        break;
      }
    }
    if (unit === null) return;

    var txt = 'Precio unitario: $'+ numberWithDots(unit);
    if (tipo.slug === 'relajacion' && (dur===30 || dur===60) && pair){
      txt += ' • Precio 2x (pareja): $'+ numberWithDots(pair) + ' (se aplica automáticamente por cada 2 personas)';
    }
    $('#precio_info_'+id).text(txt);

    actualizarTotalEstimado(id);
  }

  function actualizarTotalEstimado(id){
    var slugTipo = $('#tipo_'+id).val();
    var dur      = parseInt($('#duracion_'+id).val() || '0', 10);
    var cant     = parseInt($('input[name="servicios['+id+'][cantidad]"]').val() || '0', 10);
    if (!slugTipo || !dur || !cant) { $('#total_estimado_'+id).text(''); return; }

    var tipo = encontrarTipoPorSlug(slugTipo);
    if (!tipo || !tipo.precios) return;

    var unit=null, pair=null;
    for (var i=0;i<tipo.precios.length;i++){
      var p = tipo.precios[i];
      if (parseInt(p.duracion_minutos,10) === dur){
        unit = parseInt(p.precio_unitario,10);
        pair = p.precio_pareja ? parseInt(p.precio_pareja,10) : null;
        break;
      }
    }
    if (unit===null) return;

    var total = 0;
    if (tipo.slug==='relajacion' && (dur===30 || dur===60) && pair){
      var pares = Math.floor(cant/2);
      var resto = cant - pares*2;
      total = pares*pair + resto*unit;
    } else {
      total = cant * unit;
    }

    $('#total_estimado_'+id).text('Total estimado: $'+numberWithDots(total));
  }

  function numberWithDots(x){
    x = x || 0;
    x = x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return x;
  }

  // Hookea tu flujo existente
  $(document).ready(function() {
    // Re-init tabs
    $('ul.tabs').tabs();

    // Click de servicio
    $('.servicio').off('click').on('click', function(event) {
      event.preventDefault();

      var id     = $(this).data('id');
      var nombre = $(this).data('nombre');
      var precio = $(this).data('precio');
      var slug   = $(this).data('slug');

      var esMasaje = (slug === 'masaje') || (nombre && nombre.toLowerCase().indexOf('masaje')>=0);

      // Si no existe el bloque aún, créalo
      if ($('#servicio_' + id).length === 0) {
        var extraHTML = '';

        if (esMasaje) {
          extraHTML = renderControlesMasaje(id);
        }

        $('#servicios_seleccionados').append(
          '<div class="row" id="servicio_' + id + '">' +
            '<div class="col s1">' +
              '<a href="javascript:void(0);" onclick="eliminarServicio('+ id +')"><i class="material-icons" style="padding-top:25px; color:red;">clear</i></a>'+
            '</div>'+
            '<div class="col s3">' +
              '<blockquote><h5>' + nombre + '</h5></blockquote>' +
            '</div>' +
            '<div class="col s4">' +
              // Solo mostramos precio referencial; el backend usa BD, no este valor.
              '<h5>$' + numberWithDots(precio) + '</h5>' +
            '</div>' +
            '<div class="col s4">' +
              '<input type="number" name="servicios[' + id + '][cantidad]" placeholder="Cantidad" min="1" oninput="actualizarTotalEstimado('+id+');">' +
            '</div>' +
            extraHTML +
          '</div>'
        );

        // Si es masaje, inicializa selects y binds
        if (esMasaje) {
          materializeInitSelect('#categoria_'+id);
          materializeInitSelect('#tipo_'+id);
          materializeInitSelect('#duracion_'+id);

          $('#categoria_'+id).on('change', function(){
            var slugCat = $(this).val();
            poblarTipos(id, slugCat);
          });
          $('#tipo_'+id).on('change', function(){
            var slugTipo = $(this).val();
            poblarDuraciones(id, slugTipo);
          });
          $('#duracion_'+id).on('change', function(){
            mostrarInfoPrecio(id);
          });
        }

      } else {
        // Ya existe → toast
        var Toast = Swal.mixin({
          toast: true,
          position: "top",
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
          didOpen: function(toast){ toast.onmouseenter = Swal.stopTimer; toast.onmouseleave = Swal.resumeTimer; }
        });
        Toast.fire({ icon: "error", title: "El servicio ya fue incorporado. Ajusta la cantidad." });
      }
    });
  });
</script> --}}


<script>
  // ====== Catálogo completo desde el backend (controlador debe pasar $catalogoMasajes) ======
  var CATALOGO = @json($catalogoMasajes);

  // ----- Utilidades ES5 -----
  function numberWithDots(n){ return (n||0).toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.'); }
  function matInit(sel){ 
    
    // try{$(sel).material_select();}catch(e){} 
  
    var $sel = $(sel);

  // 1) destruir si ya estaba
  try { $sel.material_select('destroy'); } catch(e){}

  // 2) reinit
  $sel.material_select();

  // 3) mover dropdown al body (solo una vez)
  var $wrap  = $sel.parent('.select-wrapper');
  var $input = $wrap.children('input.select-dropdown');
  var $ul    = $wrap.children('ul.select-dropdown');

  if ($ul.length && !$ul.data('movido')) {
    $ul.appendTo('body');               // saca del card/tabs
    $ul.data('movido', true);

    // 4) fix “se oculta al primer click”: recalcula posición/tamaño
    $input.off('click.fix-open').on('click.fix-open', function(){
      var off = $input.offset();
      $ul.css({
        position: 'absolute',
        top:  off.top + $input.outerHeight(),
        left: off.left,
        minWidth: $input.outerWidth()
      });
      // pequeño defer para que no se cierre inmediatamente
      setTimeout(function(){ /* nada: deja que dropdown abra bien */ }, 0);
    });
  }

}

  function tipoBySlug(slug){
    for(var i=0;i<CATALOGO.length;i++){
      var tipos = CATALOGO[i].tipos||[];
      for(var j=0;j<tipos.length;j++){ if(tipos[j].slug===slug) return tipos[j]; }
    }
    return null;
  }
  function preciosDeTipo(slug){
    var t = tipoBySlug(slug);
    return t ? (t.precios||[]) : [];
  }
  function precioDe(slugTipo, dur){
    var ps = preciosDeTipo(slugTipo);
    for(var i=0;i<ps.length;i++){
      if(parseInt(ps[i].duracion_minutos,10)===dur){
        return { unit: parseInt(ps[i].precio_unitario,10),
                 pair: ps[i].precio_pareja ? parseInt(ps[i].precio_pareja,10) : null };
      }
    }
    return { unit:null, pair:null };
  }
  function duracionesDisponibles(slugTipo){
    var ps = preciosDeTipo(slugTipo), out=[];
    for(var i=0;i<ps.length;i++){ out.push(parseInt(ps[i].duracion_minutos,10)); }
    return out;
  }

  // ----- Render de controles en una sola fila -----
  function htmlMasajeEnFila(id, nombre){
    var cats = CATALOGO.map(function(c){ return '<option value="'+c.slug+'">'+c.nombre+'</option>'; }).join('');
    return ''+
    '<div class="row valign-wrapper" id="servicio_'+id+'">'+
      '<div class="col s1">'+
        '<a href="javascript:void(0);" onclick="eliminarServicio('+id+')"><i class="material-icons" style="padding-top:25px;color:red;">clear</i></a>'+
      '</div>'+
      '<div class="col s2"><blockquote><h5>'+nombre+'</h5></blockquote></div>'+
      // Precio dinámico a la vista
      '<div class="col s2 right-align">'+
        '<h5 id="precio_unit_'+id+'">$0</h5>'+
      '</div>'+
      // Cantidad
      '<div class="col s2">'+
        '<input type="number" min="1" value="1" name="servicios['+id+'][cantidad]" oninput="recalcTotal('+id+')">'+
      '</div>'+
      // Categoría
      '<div class="input-field col s4">'+
        '<select id="categoria_'+id+'"><option value="" disabled selected>Categoría</option>'+cats+'</select>'+
        '<label>Categoría de masaje</label>'+
      '</div>'+
      // Tipo
      '<div class="input-field col s4">'+
        '<select id="tipo_'+id+'" disabled><option value="" disabled selected>Tipo</option></select>'+
        '<label>Tipo de masaje</label>'+
      '</div>'+
      // Opciones (definen duración y flags)
      '<div class="col s2" id="opciones_'+id+'">'+
        '<label style="margin-right:6px;"><input class="with-gap" name="op_'+id+'" type="radio" value="nuevo30" checked><span>Nuevo 30</span></label>'+
        '<label style="margin-right:6px;"><input class="with-gap" name="op_'+id+'" type="radio" value="subir30"><span>+30 a existentes</span></label>'+
        '<label><input class="with-gap" name="op_'+id+'" type="radio" value="nuevo60"><span>Nuevo 60</span></label>'+
      '</div>'+

      // Hidden para enviar al backend
      '<input type="hidden" id="tipo_hidden_'+id+'" name="servicios['+id+'][slug_tipo_masaje]">'+
      '<input type="hidden" id="dur_hidden_'+id+'"  name="servicios['+id+'][duracion]" value="30">'+
      '<input type="hidden" id="extra_actual_'+id+'" name="servicios['+id+'][tiempo_extra_actual]" value="">'+
      '<input type="hidden" id="extra_nuevo_'+id+'"  name="servicios['+id+'][tiempo_extra]" value="">'+

      // Total estimado
      '<div class="col s12"><small id="total_estimado_'+id+'" class="black-text"></small></div>'+
    '</div>';
  }



  // ----- Lógica de UI -----
  function poblarTiposPorCategoria(id, slugCat){
    // busca la categoría y llena el select de tipos
    var tipos=[], i,j;
    for(i=0;i<CATALOGO.length;i++){
      if(CATALOGO[i].slug===slugCat){ tipos=CATALOGO[i].tipos||[]; break; }
    }
    var $sel = $('#tipo_'+id);
    $sel.prop('disabled', false).empty().append('<option value="" disabled selected>Tipo</option>');
    for(j=0;j<tipos.length;j++){
      $sel.append('<option value="'+tipos[j].slug+'">'+tipos[j].nombre+'</option>');
    }
    matInit('#tipo_'+id);

    // limpiar dependientes
    $('#tipo_hidden_'+id).val('');
    ajustarOpcionesSegunTipo(id, null);
    actualizarPrecioYTotal(id);
  }

  // Oculta/muestra radios según duraciones/negocio
  function ajustarOpcionesSegunTipo(id, slugTipo){
    var $ops = $('#opciones_'+id+' input[type=radio]');
    $ops.prop('disabled', false).parent().show();

    var durs = slugTipo ? duracionesDisponibles(slugTipo) : [];
    var tiene30 = durs.indexOf(30) !== -1;
    var tiene60 = durs.indexOf(60) !== -1;

    // Por negocio: “+30 a existentes” solo tiene sentido en relajación/descontracturante
    var permitirSubir30 = slugTipo==='relajacion' || slugTipo==='descontracturante';

    // Oculta según disponibilidad
    if(!tiene30){
      $('#opciones_'+id+' input[value=nuevo30]').prop('disabled',true).parent().hide();
      $('#opciones_'+id+' input[value=subir30]').prop('disabled',true).parent().hide();
    }
    if(!tiene60){
      $('#opciones_'+id+' input[value=nuevo60]').prop('disabled',true).parent().hide();
    }
    if(tiene30 && !permitirSubir30){
      $('#opciones_'+id+' input[value=subir30]').prop('disabled',true).parent().hide();
    }

    // Selección por defecto coherente
    if(tiene60 && !tiene30){ // solo 60 (ej: Prenatal/Balines)
      $('#opciones_'+id+' input[value=nuevo60]').prop('checked', true);
    }else{
      $('#opciones_'+id+' input[value=nuevo30]').prop('checked', true);
    }

    // Sincroniza hidden
    syncHiddenSegunOpcion(id);
  }

  function syncHiddenSegunOpcion(id){
    var op = $('#opciones_'+id+' input[type=radio]:checked').val();
    // duracion
    var dur = (op==='nuevo60') ? 60 : 30;
    $('#dur_hidden_'+id).val(dur);
    // flags
    $('#extra_actual_'+id).val( op==='subir30' ? 1 : '' );
    $('#extra_nuevo_'+id).val(  op==='nuevo60' ? 1 : '' );
  }

  function actualizarPrecioYTotal(id){
    var slugTipo = $('#tipo_hidden_'+id).val();
    var dur      = parseInt($('#dur_hidden_'+id).val()||'0',10);
    if(!slugTipo || !dur){ $('#precio_unit_'+id).text('$0'); $('#total_estimado_'+id).text(''); return; }

    var p = precioDe(slugTipo, dur);
    if(p.unit===null){ $('#precio_unit_'+id).text('$0'); return; }
    $('#precio_unit_'+id).text('$'+numberWithDots(p.unit));

    recalcTotal(id);
  }

  function recalcTotal(id){
    var slugTipo = $('#tipo_hidden_'+id).val();
    var dur      = parseInt($('#dur_hidden_'+id).val()||'0',10);
    var cant     = parseInt($('input[name="servicios['+id+'][cantidad]"]').val()||'0',10);
    if(!slugTipo || !dur || !cant){ $('#total_estimado_'+id).text(''); return; }

    var p = precioDe(slugTipo, dur);
    var total=0;

    // 2x automático para Relajación 30/60 si hay precio_pareja
    if(slugTipo==='relajacion' && (dur===30 || dur===60) && p.pair){
      var pares = Math.floor(cant/2);
      var resto = cant - pares*2;
      total = pares * p.pair + resto * p.unit;
    }else{
      total = cant * p.unit;
    }
    $('#total_estimado_'+id).text('Total estimado: $'+numberWithDots(total));
  }

  // ==== Hook en tu flujo existente (click en pestaña de servicio) ====
  $(document).ready(function(){
    $('ul.tabs').tabs();

    $('.servicio').off('click').on('click', function(e){
      e.preventDefault();
      var id     = $(this).data('id');
      var nombre = $(this).data('nombre');
      var slug   = $(this).data('slug');
      var esMasaje = (slug==='masaje') || (nombre && nombre.toLowerCase().indexOf('masaje')>=0);

      if($('#servicio_'+id).length){ // ya agregado
        M && M.toast ? M.toast({html:'El servicio ya fue incorporado.'}) : null;
        return;
      }

      if(esMasaje){
        $('#servicios_seleccionados').append( htmlMasajeEnFila(id, nombre) );
        matInit('#categoria_'+id); matInit('#tipo_'+id);

        // Eventos encadenados
        $('#categoria_'+id).on('change', function(){
          poblarTiposPorCategoria(id, $(this).val());
        });
        $('#tipo_'+id).on('change', function(){
          var slugTipo = $(this).val();
          $('#tipo_hidden_'+id).val(slugTipo);
          ajustarOpcionesSegunTipo(id, slugTipo);
          actualizarPrecioYTotal(id);
        });
        $('#opciones_'+id+' input[type=radio]').on('change', function(){
          syncHiddenSegunOpcion(id);
          actualizarPrecioYTotal(id);
        });

      } else {
        // ——— Otros servicios; tu bloque original (cantidad y precio fijo de BD)
        $('#servicios_seleccionados').append(
          '<div class="row valign-wrapper" id="servicio_'+id+'">'+
            '<div class="col s1">'+
              '<a href="javascript:void(0);" onclick="eliminarServicio('+ id +')"><i class="material-icons" style="padding-top:25px;color:red;">clear</i></a>'+
            '</div>'+
            '<div class="col s3"><blockquote><h5>'+nombre+'</h5></blockquote></div>'+
            '<div class="col s4"><h5>$'+numberWithDots($(this).data('precio'))+'</h5></div>'+
            '<div class="col s4"><input type="number" name="servicios['+id+'][cantidad]" placeholder="Cantidad" min="1"></div>'+
            '<input type="hidden" name="servicios['+id+'][precio]" value="'+($(this).data('precio')||0)+'">'+
          '</div>'
        );
      }
    });
  });
</script>


@endsection