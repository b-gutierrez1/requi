// Formulario Nuevo Autorizador - create
(function() {
    const searchInput = document.getElementById('searchCentrosCreate');
    const centrosCols = document.querySelectorAll('.centro-col-create');
    const noResults = document.getElementById('noResultsCreate');
    const selCountEl = document.getElementById('selCount');
    const checkboxes = document.querySelectorAll('input[name="centro_costo_ids[]"]');

    function updateCount() {
        selCountEl.textContent = document.querySelectorAll('input[name="centro_costo_ids[]"]:checked').length;
    }

    function updateStyle(cb) {
        const item = cb.closest('.centro-item');
        item.classList.toggle('checked', cb.checked);
    }

    searchInput.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        let visible = 0;
        centrosCols.forEach(function(col) {
            const match = col.getAttribute('data-nombre').indexOf(q) !== -1 ||
                          col.getAttribute('data-codigo').indexOf(q) !== -1;
            col.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        noResults.style.display = visible === 0 ? 'block' : 'none';
    });

    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', function() { updateStyle(this); updateCount(); });
    });

    document.getElementById('btnSelAll').addEventListener('click', function() {
        centrosCols.forEach(function(col) {
            if (col.style.display !== 'none') {
                const cb = col.querySelector('input[type="checkbox"]');
                if (cb && !cb.checked) { cb.checked = true; updateStyle(cb); }
            }
        });
        updateCount();
    });

    document.getElementById('btnDeselAll').addEventListener('click', function() {
        centrosCols.forEach(function(col) {
            if (col.style.display !== 'none') {
                const cb = col.querySelector('input[type="checkbox"]');
                if (cb && cb.checked) { cb.checked = false; updateStyle(cb); }
            }
        });
        updateCount();
    });

    // Validacion
    document.getElementById('formNuevoAutorizador').addEventListener('submit', function(e) {
        const nombre = document.getElementById('nombre').value.trim();
        const email = document.getElementById('email').value.trim();
        const selected = document.querySelectorAll('input[name="centro_costo_ids[]"]:checked').length;

        if (nombre.length < 2) {
            e.preventDefault();
            alert('El nombre debe tener al menos 2 caracteres');
            document.getElementById('nombre').focus();
            return false;
        }

        if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            e.preventDefault();
            alert('Por favor ingresa un email valido');
            document.getElementById('email').focus();
            return false;
        }

        if (selected === 0) {
            e.preventDefault();
            alert('Debes seleccionar al menos un centro de costo');
            return false;
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('nombre').focus();
    });
})();
