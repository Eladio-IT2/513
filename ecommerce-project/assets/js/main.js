document.addEventListener('DOMContentLoaded', () => {
    const navToggle = document.querySelector('.nav-toggle');
    const mainNav = document.querySelector('.main-nav');
    const dropdownTriggers = document.querySelectorAll('.nav-dropdown__trigger');

    if (navToggle && mainNav) {
        navToggle.addEventListener('click', () => {
            mainNav.classList.toggle('open');
        });
    }

    dropdownTriggers.forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            const parent = event.currentTarget.closest('.nav-dropdown');
            parent.classList.toggle('open');
        });
    });

    document.addEventListener('click', (event) => {
        dropdownTriggers.forEach((trigger) => {
            const parent = trigger.closest('.nav-dropdown');
            if (!parent.contains(event.target)) {
                parent.classList.remove('open');
            }
        });
    });
});

