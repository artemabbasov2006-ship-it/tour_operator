document.addEventListener('DOMContentLoaded', () => {
    // Подтверждение для ссылок с классом js-confirm (выход и т.п.)
    document.querySelectorAll('.js-confirm').forEach(link => {
        link.addEventListener('click', (e) => {
            if (!confirm('Вы уверены?')) {
                e.preventDefault();
            }
        });
    });
});
