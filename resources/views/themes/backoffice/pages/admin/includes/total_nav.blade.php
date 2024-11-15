<div class="collection">

    <a href="" class="collection-item active">Resumen Sueldos</a>
    @foreach ($totalPorUsuario as $nombre => $total)
    <a class="collection-item">{{$nombre}} - ${{number_format($total, 0, ',', '.')}}</a>
    @endforeach
    
    <a class="collection-item"><strong>Total Sueldos: ${{number_format($totalSueldos, 0, ',', '.')}}</strong></a>


</div>