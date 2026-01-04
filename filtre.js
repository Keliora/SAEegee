document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector('.large_article_container');
    const filtreSelect = document.getElementById('filtre-select');

    // On transforme la liste d'articles en tableau (Array) pour pouvoir utiliser .sort()
    // On cible tous les <article> à l'intérieur du container
    const articles = Array.from(container.querySelectorAll('article'));

    filtreSelect.addEventListener('change', () => {
        const choix = filtreSelect.value;
        let articlesTries;

        // On crée une copie du tableau pour ne pas modifier l'original par erreur
        articlesTries = [...articles];

        switch (choix) {
            case 'az':
                // Tri de A à Z sur le titre h3
                articlesTries.sort((a, b) => {
                    const titreA = a.querySelector('h3').textContent.trim();
                    const titreB = b.querySelector('h3').textContent.trim();
                    return titreA.localeCompare(titreB);
                });
                break;

            case 'za':
                // Tri de Z à A
                articlesTries.sort((a, b) => {
                    const titreA = a.querySelector('h3').textContent.trim();
                    const titreB = b.querySelector('h3').textContent.trim();
                    return titreB.localeCompare(titreA);
                });
                break;

            case 'date-desc':
                // Tri par date (du plus récent au plus ancien)
                articlesTries.sort((a, b) => {
                    return new Date(b.getAttribute('data-date')) - new Date(a.getAttribute('data-date'));
                });
                break;

            case 'vues-desc':
                // Tri par nombre de vues (du plus grand au plus petit)
                articlesTries.sort((a, b) => {
                    const vuesA = parseInt(a.getAttribute('data-vues')) || 0;
                    const vuesB = parseInt(b.getAttribute('data-vues')) || 0;
                    return vuesB - vuesA;
                });
                break;
        }

        // --- MISE À JOUR DE L'AFFICHAGE ---
        // On vide visuellement le container
        container.innerHTML = "";

        // On ré-injecte les articles dans le nouvel ordre
        articlesTries.forEach(article => {
            container.appendChild(article);
        });
    });
});