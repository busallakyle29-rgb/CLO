document.addEventListener("DOMContentLoaded", function () {

    /* =====================================================
       FORGOT PASSWORD FORM
    ====================================================== */

    const form =
        document.getElementById(
            "forgotPasswordForm"
        );

    const button =
        document.getElementById(
            "forgotButton"
        );

    const buttonText =
        document.getElementById(
            "forgotButtonText"
        );

    if (
        form &&
        button &&
        buttonText
    ) {

        form.addEventListener(
            "submit",
            function () {

                /*
                 * Prevent double submission
                 */

                button.disabled = true;

                /*
                 * Change button text
                 */

                buttonText.textContent =
                    "Sending...";

                /*
                 * Change icon
                 */

                const icon =
                    button.querySelector(
                        ".forgot-button-icon i"
                    );

                if (icon) {

                    icon.className =
                        "bi bi-arrow-repeat";

                    icon.style.animation =
                        "forgotSpin 1s linear infinite";

                }

            }
        );

    }

    /* =====================================================
       EMAIL VALIDATION
    ====================================================== */

    const email =
        document.getElementById("email");

    if (email) {

        email.addEventListener(
            "input",
            function () {

                if (
                    email.validity.valid
                ) {

                    email.classList.remove(
                        "is-invalid"
                    );

                }

            }
        );

    }

});
