@extends('themes.backoffice.layouts.admin')

@section('title', 'Certificado de Antigüedad')

@section('breadcrumbs')
<li><a href="{{route('backoffice.user.index')}}">Usuarios del Sistema</a></li>
<li>Certificado de Antigüedad</li>
@endsection

@section('content')
<div class="container">
  <h5>Certificado de Antigüedad</h5>

  <div class="card">
    <div class="card-content">
        @php
            $now = now()->format('d-m-Y')
        @endphp
      <form method="POST" action="{{ route('backoffice.certificados.antiguedad.store', $user) }}" target="_blank">
        @csrf

        <div class="row">
          <div class="input-field col s12 m6">
            {{-- <select name="user_id" required>
              <option value="" disabled selected>Seleccione usuario</option>
              @foreach($usuarios as $u)
                <option value="{{ $u->id }}">{{ $u->name }} (ID: {{ $u->id }})</option>
              @endforeach
            </select> --}}
            <input type="text" name="name" id="name" value="{{ $user->name }}" required>
            <label>Usuario</label>
            @error('user_id') <span class="red-text">{{ $message }}</span> @enderror
          </div>

          <div class="input-field col s12 m6">
            <input type="text" name="rut" id="rut" value="" oninput="darFormato(this)" required autofocus>
            <label for="rut">RUT</label>
            @error('rut') <span class="red-text">{{ $message }}</span> @enderror
          </div>


        </div>

        <button class="btn waves-effect waves-light" type="submit">
          Generar certificado
          <i class="material-icons right">description</i>
        </button>
      </form>
    </div>
  </div>
</div>
@endsection

@section('foot')
<script>
function darFormato(input) {
    //Eliminar caracteres no permitidos (dejar solo numeros y K)
    let valor = input.value.replace(/[^0-9kK]/g, "");

    if (valor.lenght < 2) {
        //asignar el valor a la variable
        input.value = valor;
        return;
    }

    //quitar el ultimo digito
    let cuerpo = valor.slice(0, -1);

    //separar el digito verificador
    let dv = valor.slice(-1).toUpperCase();

    //Agregar los puntos cada 3 digitos
    cuerpo = cuerpo.replace(/\B(?=(\d{3})+(?!\d))/g, ".");

    //Retornar el valor al input
    input.value = `${cuerpo}-${dv}`;
}
</script>
@endsection
