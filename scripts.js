// --- Mode Nuit EGEE ---

(() => {
    const KEY = "egee-theme";
    const btn = document.querySelector(".changement_theme");
    const root = document.documentElement; // <html>

    if (!btn) return;

    // Détermine le thème initial
    const stored = localStorage.getItem(KEY);
    const systemPref = window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches;
    const initial = stored ?? (systemPref ? "dark" : "light");

    apply(initial);

    // Bascule au clic (et clavier)
    btn.addEventListener("click", toggle);
    btn.addEventListener("keydown", (e) => {
        if (e.key === "Enter" || e.key === " ") { e.preventDefault(); toggle(); }
    });

    // Si l’utilisateur n’a rien choisi, on suit un éventuel changement système
    const mql = window.matchMedia("(prefers-color-scheme: dark)");
    mql.addEventListener?.("change", (e) => {
        if (!localStorage.getItem(KEY)) apply(e.matches ? "dark" : "light");
    });

    function toggle() {
        const next = root.classList.contains("dark") ? "light" : "dark";
        apply(next);
        localStorage.setItem(KEY, next);
    }

    function apply(theme) {
        root.classList.toggle("dark", theme === "dark");
        // Accessibilité + icône
        const isDark = theme === "dark";
        btn.setAttribute("aria-pressed", String(isDark));
        btn.setAttribute("title", isDark ? "Désactiver le mode nuit" : "Activer le mode nuit");
        btn.textContent = isDark ? "🌙" : "☀️";
    }
})();
