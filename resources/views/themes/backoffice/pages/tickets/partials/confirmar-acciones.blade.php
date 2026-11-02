{{-- Formularios .tk-form-accion: confirmación (data-confirmar) y bloqueo del botón para no enviar dos veces --}}
<script>
(function () {
    document.querySelectorAll('.tk-form-accion').forEach(function (form) {
        var confirmado = false;
        form.addEventListener('submit', function (e) {
            var texto = form.getAttribute('data-confirmar');
            if (texto && !confirmado) {
                e.preventDefault();
                Swal.fire({
                    text: texto, icon: 'question', showCancelButton: true,
                    confirmButtonText: 'Sí', cancelButtonText: 'Cancelar', confirmButtonColor: '#0097a7'
                }).then(function (r) {
                    if (r.isConfirmed || r.value) { confirmado = true; form.requestSubmit ? form.requestSubmit() : form.submit(); }
                });
                return;
            }
            form.querySelector('button').disabled = true;
        });
    });
})();
</script>
