document.addEventListener("DOMContentLoaded", () => {
    // ====== BURGER MENU ======
    const burger = document.querySelector(".burger");
    const nav = document.querySelector(".main-nav");
    const navList = document.querySelector(".main-nav ul");

    function cleanupInjected() {
        if (!navList) return;
        navList.querySelectorAll(".nav-injected").forEach((li) => li.remove());
    }

    function injectUserInfoIntoNav() {
        const userInfo = document.querySelector(".header-user");
        if (!userInfo || !navList) return;

        // éviter doublons
        navList.querySelectorAll(".nav-user").forEach((li) => li.remove());

        const li = document.createElement("li");
        li.className = "nav-user nav-injected";

        li.appendChild(userInfo.cloneNode(true));

        // Mettre en haut du menu
        navList.prepend(li);
    }

    function injectCtaLinksIntoNav() {
        if (!navList) return;

        // Toujours clean avant injection
        cleanupInjected();

        // 1) Inject "Connecté : Prénom Nom" si présent
        injectUserInfoIntoNav();

        // 2) Inject les boutons/header links (Se connecter / Dashboard / etc.)
        const cta = document.querySelector(".header-cta");
        if (!cta) return;

        const links = cta.querySelectorAll("a");
        links.forEach((a) => {
            const li = document.createElement("li");
            li.className = "nav-injected";
            li.appendChild(a.cloneNode(true));
            navList.appendChild(li);
        });
    }

    function closeBurger() {
        nav?.classList.remove("is-open");
        burger?.classList.remove("is-open");
        cleanupInjected();
    }

    if (burger && nav) {
        burger.addEventListener("click", () => {
            nav.classList.toggle("is-open");
            burger.classList.toggle("is-open");

            if (nav.classList.contains("is-open")) {
                injectCtaLinksIntoNav();
            } else {
                cleanupInjected();
            }
        });
    }

    // Ferme le menu si on clique un lien
    if (nav) {
        nav.addEventListener("click", (e) => {
            const link = e.target.closest("a");
            if (link) closeBurger();
        });
    }

    // Ferme si on clique en dehors (optionnel)
    document.addEventListener("click", (e) => {
        if (!nav || !burger) return;
        const clickedInsideNav = nav.contains(e.target);
        const clickedBurger = burger.contains(e.target);
        if (!clickedInsideNav && !clickedBurger && nav.classList.contains("is-open")) {
            closeBurger();
        }
    });

    // ====== MODALS ======
    const openModalButtons = document.querySelectorAll(".js-open-modal");
    const closeModalButtons = document.querySelectorAll(".js-close-modal");
    const tabButtons = document.querySelectorAll(".tab-button");

    openModalButtons.forEach((button) => {
        button.addEventListener("click", (e) => {
            e.preventDefault();
            const modalId = button.dataset.modal;
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.add("is-open");
        });
    });

    closeModalButtons.forEach((button) => {
        button.addEventListener("click", (e) => {
            e.preventDefault();
            const modal = button.closest(".modal");
            modal?.classList.remove("is-open");
        });
    });

    document.querySelectorAll(".modal").forEach((modal) => {
        modal.addEventListener("click", (e) => {
            if (e.target === modal) modal.classList.remove("is-open");
        });
    });

    // ====== TABS (dans les modals) ======
    tabButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const tabsContainer = button.closest(".tabs-container");
            const targetId = button.dataset.tab;

            tabsContainer
                ?.querySelectorAll(".tab-button")
                .forEach((btn) => btn.classList.remove("active"));

            tabsContainer
                ?.querySelectorAll(".tab-content")
                .forEach((content) => content.classList.remove("active"));

            button.classList.add("active");
            document.getElementById(targetId)?.classList.add("active");
        });
    });

    // ====== ESC pour fermer les modals ======
    document.addEventListener("keydown", (e) => {
        if (e.key !== "Escape") return;
        document.querySelectorAll(".modal.is-open").forEach((m) => m.classList.remove("is-open"));
    });
});
