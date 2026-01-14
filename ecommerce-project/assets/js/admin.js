document.addEventListener('DOMContentLoaded', () => {
    const statusForms = document.querySelectorAll('.js-order-status-form');

    statusForms.forEach((form) => {
        form.addEventListener('change', () => {
            form.submit();
        });
    });
});

