{{--
    Partial: desayuno_once_card
    Tarjeta con el total de asistentes para Desayuno u Once.

    Parámetros:
        $titulo string  Ej: 'Desayuno' o 'Once'
        $total  int
--}}
<ul class="collection">
    <li class="collection-item avatar">
      <i class="material-icons circle purple">free_breakfast</i>
      <span class="title">{{ $titulo }}</span>
      <p>Total:</p>
      <span class="secondary-content" style="color: #039B7B">
        {{ $total }} {{ $total > 1 ? "Personas" : "Persona" }}
      </span>
    </li>
</ul>
