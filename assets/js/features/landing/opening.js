/* =========================================================
   CITY LEGAL OFFICE
   DOCUMENT TRACKING SYSTEM
   OPENING PAGE JAVASCRIPT
========================================================= */

/* =========================================================
   LOADING SCREEN
========================================================= */

window.addEventListener("load", function () {

    const loadingScreen =
        document.getElementById("loadingScreen");

    if (!loadingScreen) {
        return;
    }

    setTimeout(function () {

        loadingScreen.classList.add("hidden");

    }, 1000);

});

/* =========================================================
   SIGN IN BUTTON
========================================================= */

const signInButton =
    document.getElementById("signInButton");

if (signInButton) {

    signInButton.addEventListener(
        "click",
        function () {

            const button =
                this;

            button.innerHTML = `

                <span
                    class="spinner-border
                           spinner-border-sm"
                    role="status"
                    aria-hidden="true">
                </span>

                <span>
                    Signing In...
                </span>

            `;

        }
    );

}

/* =========================================================
   ENTER SYSTEM BUTTON
========================================================= */

const enterButton =
    document.getElementById("enterButton");

if (enterButton) {

    enterButton.addEventListener(
        "click",
        function () {

            const button =
                this;

            button.innerHTML = `

                <span
                    class="spinner-border
                           spinner-border-sm"
                    role="status"
                    aria-hidden="true">
                </span>

                <span>
                    Opening...
                </span>

            `;

        }
    );

}

/* =========================================================
   PREVENT DOUBLE CLICK
========================================================= */

const buttons =
    document.querySelectorAll(
        ".enter-btn"
    );

buttons.forEach(function (button) {

    button.addEventListener(
        "click",
        function () {

            setTimeout(function () {

                buttons.forEach(
                    function (btn) {

                        btn.style.pointerEvents =
                            "none";

                    }
                );

            }, 10);

        }
    );

});
