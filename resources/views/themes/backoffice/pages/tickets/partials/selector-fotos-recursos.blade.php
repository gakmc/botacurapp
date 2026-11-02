{{-- CSS y JS del selector de fotos. Incluir una vez por página, en @section('foot'). --}}
<style>
    /* En Materialize 0.100.2 btn-block solo hace display:block */
    .btn-block { width: 100%; }
    /* En celular el padding de .btn (2rem) no deja espacio al texto: salta de línea y queda oculto */
    @media only screen and (max-width: 600px) {
        .tk-btn-foto { padding: 0 8px; text-align: left; }
        .tk-btn-foto i.left { margin-right: 6px; }
    }
    /* Miniaturas cuadradas con botón para quitar */
    .tk-previa { position: relative; padding-top: 100%; margin-bottom: 10px; overflow: hidden; border-radius: 4px; }
    .tk-previa img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
    .tk-previa .btn-floating { position: absolute; top: 4px; right: 4px; }
</style>
<script>
window.TkFotos = window.TkFotos || (function () {
    var LADO_MAX = 1600;

    // Las fotos de celular pesan varios MB: se reducen a LADO_MAX px en JPEG antes de subir
    function reducir(file) {
        return new Promise(function (resolve) {
            var img = new Image(), url = URL.createObjectURL(file);
            img.onload = function () {
                var f = Math.min(1, LADO_MAX / Math.max(img.width, img.height));
                var c = document.createElement('canvas');
                c.width = Math.round(img.width * f); c.height = Math.round(img.height * f);
                c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
                URL.revokeObjectURL(url);
                c.toBlob(function (b) { resolve(b ? new File([b], 'foto.jpg', { type: 'image/jpeg' }) : file); }, 'image/jpeg', 0.8);
            };
            img.onerror = function () { URL.revokeObjectURL(url); resolve(file); };
            img.src = url;
        });
    }

    /** Activa un selector (div.tk-selector-fotos): acumula fotos de cámara y galería. */
    function iniciar(contenedor) {
        var max = parseInt(contenedor.getAttribute('data-max'), 10),
            previas = contenedor.querySelector('.tk-previas'),
            contador = contenedor.querySelector('.tk-contador'),
            error = contenedor.querySelector('.tk-error[data-error=fotos]'),
            fotos = [];

        function pintar() {
            previas.innerHTML = '';
            fotos.forEach(function (f, i) {
                var col = document.createElement('div'); col.className = 'col s4 m3';
                var caja = document.createElement('div'); caja.className = 'tk-previa z-depth-1';
                var img = document.createElement('img'); img.src = f.url; img.alt = 'Foto ' + (i + 1);
                var quitar = document.createElement('a'); quitar.href = '#!'; quitar.title = 'Quitar foto';
                quitar.className = 'btn-floating btn-small waves-effect waves-light red';
                quitar.innerHTML = '<i class="material-icons">close</i>';
                quitar.addEventListener('click', function (e) { e.preventDefault(); URL.revokeObjectURL(f.url); fotos.splice(i, 1); pintar(); });
                caja.appendChild(img); caja.appendChild(quitar); col.appendChild(caja); previas.appendChild(col);
            });
            contador.textContent = fotos.length + '/' + max;
        }

        contenedor.querySelectorAll('input[type=file]').forEach(function (input) {
            input.addEventListener('change', function () {
                Array.prototype.forEach.call(input.files, function (file) {
                    if (fotos.length >= max) return;
                    fotos.push({ file: file, url: URL.createObjectURL(file) });
                });
                if (input.files.length && fotos.length >= max && error) error.textContent = 'Máximo ' + max + ' fotos.';
                input.value = '';
                pintar();
            });
        });

        return {
            cantidad: function () { return fotos.length; },
            reducidas: function () { return Promise.all(fotos.map(function (f) { return reducir(f.file); })); }
        };
    }

    /**
     * Envía el formulario por fetch con las fotos ya reducidas (así no se pierden si falla
     * la validación). Resuelve con { status, json }.
     */
    function enviar(form, selector) {
        return selector.reducidas().then(function (reducidas) {
            var datos = new FormData(form);
            datos.delete('fotos[]');
            reducidas.forEach(function (f) { datos.append('fotos[]', f); });

            return fetch(form.action, {
                method: 'POST', body: datos, credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
        }).then(function (resp) {
            return resp.json().catch(function () { return {}; }).then(function (json) { return { status: resp.status, json: json }; });
        });
    }

    return { iniciar: iniciar, enviar: enviar, reducir: reducir };
})();
</script>
