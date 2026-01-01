// Burger simple pour mobile
    const burger = document.querySelector('.burger');
    const nav = document.querySelector('.main-nav');

    burger.addEventListener('click', () => {
    nav.classList.toggle('is-open');
    burger.classList.toggle('is-open');
});


    const openModalButtons = document.querySelectorAll('.js-open-modal');
    const closeModalButtons = document.querySelectorAll('.js-close-modal');
    const tabButtons = document.querySelectorAll('.tab-button');


    openModalButtons.forEach(button => {
    button.addEventListener('click', (e) => {
        e.preventDefault();
        const modalId = button.dataset.modal;
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('is-open');
        }
    });
});


    closeModalButtons.forEach(button => {
    button.addEventListener('click', (e) => {
        e.preventDefault();
        const modal = button.closest('.modal');
        modal.classList.remove('is-open');
    });
});


    document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('is-open');
        }
    });
});


    tabButtons.forEach(button => {
    button.addEventListener('click', () => {
        const tabsContainer = button.closest('.tabs-container');
        const targetId = button.dataset.tab;


        tabsContainer.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
        tabsContainer.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));


        button.classList.add('active');
        document.getElementById(targetId).classList.add('active');
    });
});