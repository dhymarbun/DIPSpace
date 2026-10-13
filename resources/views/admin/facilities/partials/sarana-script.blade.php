<script>
    (function () {
        var container = document.getElementById('sarana-rows');
        var addBtn = document.getElementById('sarana-add');
        var template = document.getElementById('sarana-row-template');
        if (!container || !addBtn || !template) return;

        // Indeks selalu naik agar tidak bertabrakan walau ada baris yang dihapus.
        var nextIndex = container.querySelectorAll('[data-sarana-row]').length;

        addBtn.addEventListener('click', function () {
            var html = template.innerHTML.split('__INDEX__').join(String(nextIndex));
            nextIndex++;
            container.insertAdjacentHTML('beforeend', html);
        });

        container.addEventListener('click', function (event) {
            var removeBtn = event.target.closest('[data-sarana-remove]');
            if (!removeBtn) return;
            var row = removeBtn.closest('[data-sarana-row]');
            if (row) row.remove();
        });
    })();
</script>
