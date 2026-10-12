(function () {
    const root = document.documentElement;
    const userId = root.dataset.themeUserId || "guest";
    const storageKey = `clo-theme-user-${userId}`;
    const themePreferenceEndpoint = root.dataset.themePreferenceEndpoint || "";
    const csrfToken = root.dataset.themeCsrf || "";

    function normalizeTheme(theme) {
        return theme === "dark" ? "dark" : "light";
    }

    function setTheme(theme) {
        root.dataset.theme = normalizeTheme(theme);
    }

    function getBrowserThemePreference() {
        try {
            const saved = localStorage.getItem(storageKey);
            return saved === "dark" ? "dark" : "light";
        } catch (error) {
            console.error("Unable to read the saved theme preference.", error);
            return "light";
        }
    }

    const isLoggedInUser = Boolean(userId && userId !== "guest" && userId !== "0");
    const serverTheme = normalizeTheme(root.dataset.theme || "light");

    if (isLoggedInUser) {
        setTheme(serverTheme);
    } else {
        setTheme(getBrowserThemePreference());
    }

    document.addEventListener("DOMContentLoaded", function () {
        const toggle = document.getElementById("darkModeToggle");

        if (!toggle) {
            return;
        }

        function updateToggle() {
            const isDark = root.dataset.theme === "dark";
            const icon = toggle.querySelector("i");

            toggle.setAttribute("aria-pressed", isDark ? "true" : "false");
            toggle.setAttribute("aria-label", isDark ? "Switch to light mode" : "Switch to dark mode");
            toggle.title = isDark ? "Switch to light mode" : "Switch to dark mode";

            if (icon) {
                icon.classList.toggle("bi-moon-stars", !isDark);
                icon.classList.toggle("bi-sun", isDark);
            }
        }

        function persistTheme(nextTheme) {
            const theme = normalizeTheme(nextTheme);

            try {
                localStorage.setItem(storageKey, theme);
            } catch (error) {
                console.error("Unable to save the theme preference locally.", error);
            }

            if (!themePreferenceEndpoint || !csrfToken) {
                return;
            }

            const payload = new URLSearchParams({
                theme: theme,
                csrf_token: csrfToken,
            });

            fetch(themePreferenceEndpoint, {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: payload.toString(),
            }).catch(function () {
                console.warn("Unable to sync the theme preference to the server.");
            });
        }

        toggle.addEventListener("click", function () {
            const nextTheme = root.dataset.theme === "dark" ? "light" : "dark";
            setTheme(nextTheme);
            persistTheme(nextTheme);
            updateToggle();
        });

        updateToggle();
    });
})();
