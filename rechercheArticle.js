document.addEventListener('DOMContentLoaded', () => {
    // 1. Sélection des éléments
    const searchContainer = document.querySelector('.recherche_article');
    const input = searchContainer.querySelector('input');
    const btnSearch = searchContainer.querySelector('.btn-secondary');
    const container = document.querySelector('.large_article_container');

    // On récupère tous les articles
    const articles = Array.from(container.querySelectorAll('article'));

    // --- FONCTION DE FILTRAGE ---
    function filtrerArticles() {
        const saisie = input.value.toLowerCase().trim();

        articles.forEach(article => {
            const titre = article.querySelector('h3').textContent.toLowerCase();
            // Si le titre contient les caractères saisis, on affiche, sinon on cache
            if (titre.includes(saisie)) {
                article.style.display = "flex";
            } else {
                article.style.display = "none";
            }
        });
    }

    // --- FONCTION DE TRI ALPHABÉTIQUE ---
    function trierArticles() {
        const articlesTries = articles.sort((a, b) => {
            const titreA = a.querySelector('h3').textContent.trim().toLowerCase();
            const titreB = b.querySelector('h3').textContent.trim().toLowerCase();
            return titreA.localeCompare(titreB);
        });

        // On vide le container et on ré-insère les articles dans l'ordre
        articlesTries.forEach(article => container.appendChild(article));
    }

    // Événements
    btnSearch.addEventListener('click', filtrerArticles);

    // Recherche en temps réel quand on tape
    input.addEventListener('input', filtrerArticles);

    // Lancer un tri alphabétique automatique au chargement (optionnel)
    trierArticles();
});