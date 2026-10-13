<script>
    (function () {
        var input = document.getElementById('foto');
        var preview = document.getElementById('foto-preview');
        if (!input || !preview) return;

        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) {
                preview.style.display = 'none';
                preview.removeAttribute('src');
                return;
            }
            preview.src = URL.createObjectURL(file);
            preview.style.display = 'block';
        });
    })();
</script>
