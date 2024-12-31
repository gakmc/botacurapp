Vista Edit Visita

@section('foot')
<script>
  @if(session('success'))
  Swal.fire({
      toast: true,
      position: '',
      icon: 'success',
      title: '{{ session('success') }}',
      showConfirmButton: false,
      timer: 5000,
      timerProgressBar: true,
        didOpen: (toast) => {
          toast.onmouseenter = Swal.stopTimer;
          toast.onmouseleave = Swal.resumeTimer;
        }
  });
@endif
</script>
@endsection