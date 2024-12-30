@extends('themes.backoffice.layouts.admin')

@section('title', 'Panel de Administración')

@section('breadcrumbs')
<li>Equipos Asignados</li>
@endsection

@section('content')

<div class="section">
    <p class="caption"><strong>Equipos de la semana</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 m8">
                <div class="card-panel">

                    <div class="row">
                        <form method="POST" action="{{ route('backoffice.sueldos.store') }}">
                            @csrf
                            <div class="row">

                                <div class="input-field col m4">
                                    <input type="number" name="sueldoBase" id="sueldoBase" value="{{$base}}" readonly>
                                    <label for="sueldoBase">Sueldo Base</label>
                                    <a href="javascript:void(0);" class="purple-text" id="editarBase" data-state="modificar">Modificar
                                        sueldo base</a>
                                    <a href="javascript:void(0);" class="purple-text" id="guardarBase" data-state="guardar" hidden>Guardar
                                        sueldo base</a>
                                </div>
                                <div class="input-field col m4">
                                    <input type="text" name="bono" id="bono">
                                    <label for="sueldoBase">Bono general</label>
                                </div>
                            </div>
                            @php
                            $counter = 0;
                            @endphp
                            @foreach ($diasSemana as $dia)
                            <div class="col m4 l6">
                                <div class="card z-depth-0">
                                    <div class="card-header">
                                        <h5><strong>{{ $dia }}</strong></h5>
                                    </div>
                                    <div class="card-body">
                                        {{-- Mostrar asignaciones por día --}}
                                        @if (isset($asignacionesPorDia[$dia]))
                                        @foreach ($asignacionesPorDia[$dia] as $asignacion)
                                        @foreach ($asignacion->users as $user)
                                        <p><strong>{{ $user->name }}</strong> - ${{number_format($base,0,',','.')}}

                                            @if (isset($propinasPorDia[$dia]))
                                            - Propinas del dia: ${{ number_format($propinasPorDia[$dia]['propina'], 0,
                                            ',', '.')
                                            }}


                                            {{-- Campos ocultos para el envío del formulario --}}
                                            <input type="text" name="sueldos[{{ $counter }}][dia_trabajado]"
                                                value="{{ $propinasPorDia[$dia]['dia_trabajado'] }}">
                                            <input type="text" name="sueldos[{{ $counter }}][valor_dia]"
                                                value="{{$base}}">
                                            <input type="text" name="sueldos[{{ $counter }}][sub_sueldo]"
                                                value="{{ number_format($base + $propinasPorDia[$dia]['propina'],0,',', '') }}">
                                            <input type="text" name="sueldos[{{ $counter }}][total_pagar]"
                                                value="{{ number_format($base + $propinasPorDia[$dia]['propina'],0,',', '') }}">
                                            <input type="text" name="sueldos[{{ $counter }}][id_user]"
                                                value="{{ $user->id }}">


                                            @php
                                            $counter++
                                            @endphp
                                            @endif
                                        </p>
                                        @endforeach
                                        @endforeach
                                        @else
                                        <p>No hay asignaciones.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                            <button type="submit" class="btn btn-primary right"><i
                                    class='material-icons right'>account_balance_wallet</i>Cerrar sueldos de la
                                semana</button>
                        </form>
                    </div>
                </div>
            </div>


            <div class="col s12 m4">
                @include('themes.backoffice.pages.admin.includes.total_nav')
            </div>
        </div>
    </div>
</div>
@endsection

@section('foot')
<script>
    $(document).ready(function () {
        @if(session('info'))
            Swal.fire({
                toast: true,
                position: '',
                icon: 'info',
                title: '{{ session('info') }}',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                  didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                  }
            });
        @endif

        @if(session('success'))
            Swal.fire({
                toast: true,
                position: '',
                icon: 'success',
                title: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                  didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                  }
            });
        @endif
    });


</script>
<script>
$(document).ready(function () {
    const sueldoBaseInput = $('#sueldoBase');
    const editarBaseBtn = $("#editarBase");
    const guardarBaseBtn = $("#guardarBase");
    
    editarBaseBtn.on('click', function (e) {
        e.preventDefault();
        sueldoBaseInput.prop('readonly', false);
        guardarBaseBtn.prop('hidden', false);
        editarBaseBtn.prop('hidden', true);
        
    });
    
    guardarBaseBtn.on('click', function (e) { 
        e.preventDefault();
        if (sueldoBaseInput.val() === '') {
            const Toast = Swal.mixin({
                  toast: true,
                  position: "",
                  showConfirmButton: false,
                  timer: 3000,
                  timerProgressBar: true,
                  didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                  }
                });
                Toast.fire({
                  icon: "error",
                  title: "Debe especificar un monto"
                });
            
        }else{
            sueldoBaseInput.prop('readonly', true);
            editarBaseBtn.prop('hidden', false);
            guardarBaseBtn.prop('hidden', true);
            window.location.href = `/actualizar-sueldo-base?sueldoBase=${sueldoBaseInput.val()}`;

        }
     });
});


// $(document).ready(function () {
//     // Escucha clic en el botón
//     $('#editarBase').on('click', function (e) {
//         e.preventDefault();

//         // Referencias
//         const sueldoBaseInput = $('#sueldoBase');
//         const editarBaseBtn = $(this);

//         // Si el texto del botón es "Modificar sueldo"
//         if (editarBaseBtn.text().trim() === 'Modificar sueldo') {
//             // Cambiar el texto del botón
//             editarBaseBtn.text('Guardar sueldo');

//             // Hacer el input editable
//             sueldoBaseInput.prop('readonly', false);
//         } else {
//             // Cambiar el texto del botón
//             editarBaseBtn.text('Modificar sueldo');

//             // Obtener el valor del input
//             const sueldoBase = sueldoBaseInput.val();

//             // Validar que el sueldo no esté vacío
//             if (sueldoBase === '') {
//                 alert('El sueldo base no puede estar vacío.');
//                 return;
//             }

//             // Redirigir con el valor del sueldo en la URL
//             window.location.href = `/actualizar-sueldo-base?sueldoBase=${sueldoBase}`;
//         }
//     });
// });


</script>
@endsection