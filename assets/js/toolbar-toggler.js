document.addEventListener('DOMContentLoaded', function() {
    var toggleButton = document.getElementById('toolbar-toggle-button');
    if (toggleButton) {
        toggleButton.addEventListener('click', function() {
            document.body.classList.toggle('toolbar-visible');
        });
    }
});
