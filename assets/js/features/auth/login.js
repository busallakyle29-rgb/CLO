document.addEventListener("DOMContentLoaded", function () {

    const registerForm = document.getElementById("registerForm");
    const registerAccountStep = registerForm?.querySelector(".register-step-account");
    const registerSecurityStep = registerForm?.querySelector(".register-step-security");
    const registerStepActions = registerForm?.querySelector(".register-step-actions");
    const registerNextButton = document.getElementById("registerNextButton");
    const registerBackButton = document.getElementById("registerBackButton");
    const registerSubmitButton = document.getElementById("registerSubmitButton");
    const registerProgressSteps = registerForm?.querySelectorAll("[data-progress-step]") || [];
    const registerPassword = document.getElementById("password");
    const registerConfirmPassword = document.getElementById("confirm_password");
    const registerPasswordStrength = document.getElementById("passwordStrength");
    const registerPasswordMatch = document.getElementById("passwordMatch");

    const updateRegisterReview = function () {
        if (!registerForm) {
            return;
        }

        const getValue = function (id) {
            return registerForm.querySelector(`#${id}`)?.value.trim() || "-";
        };

        const department = registerForm.querySelector("#department_id_display")
            || registerForm.querySelector("#department_id");
        const role = registerForm.querySelector("#role_id");
        const firstName = getValue("first_name");
        const lastName = getValue("last_name");

        const reviewValues = {
            employee_no: getValue("employee_no"),
            full_name: `${firstName} ${lastName}`.trim() || "-",
            email: getValue("email"),
            department_id: department?.selectedOptions[0]?.textContent.trim() || "-",
            role_id: role?.selectedOptions[0]?.textContent.trim() || "Administrator",
        };

        Object.entries(reviewValues).forEach(function ([key, value]) {
            const target = registerForm.querySelector(`[data-review="${key}"]`);
            if (target) {
                target.textContent = value;
            }
        });
    };

    const updatePasswordFeedback = function () {
        if (!registerPassword || !registerPasswordStrength) {
            return;
        }

        const passwordValue = registerPassword.value;
        const score = [
            passwordValue.length >= 8,
            /[a-z]/.test(passwordValue),
            /[A-Z]/.test(passwordValue),
            /\d/.test(passwordValue),
            /[^A-Za-z0-9]/.test(passwordValue),
        ].filter(Boolean).length;

        registerPasswordStrength.className = "password-strength";

        if (passwordValue === "") {
            registerPasswordStrength.textContent = "Use at least 8 characters.";
        } else if (score <= 2) {
            registerPasswordStrength.textContent = "Weak password";
            registerPasswordStrength.classList.add("is-weak");
        } else if (score <= 4) {
            registerPasswordStrength.textContent = "Medium password";
            registerPasswordStrength.classList.add("is-medium");
        } else {
            registerPasswordStrength.textContent = "Strong password";
            registerPasswordStrength.classList.add("is-strong");
        }
    };

    const updatePasswordMatch = function () {
        if (!registerPassword || !registerConfirmPassword || !registerPasswordMatch) {
            return;
        }

        registerConfirmPassword.setCustomValidity("");
        registerPasswordMatch.className = "password-match";

        if (registerConfirmPassword.value === "") {
            registerPasswordMatch.textContent = "";
        } else if (registerPassword.value !== registerConfirmPassword.value) {
            registerConfirmPassword.setCustomValidity("Passwords do not match.");
            registerPasswordMatch.textContent = "Passwords do not match.";
            registerPasswordMatch.classList.add("is-invalid");
        } else {
            registerPasswordMatch.textContent = "Passwords match.";
            registerPasswordMatch.classList.add("is-valid");
        }
    };

    const showRegisterStep = function (step) {
        if (!registerAccountStep || !registerSecurityStep || !registerStepActions) {
            return;
        }

        const isSecurityStep = step === "security";
        registerAccountStep.hidden = isSecurityStep;
        registerSecurityStep.hidden = !isSecurityStep;
        registerAccountStep.classList.toggle("is-active", !isSecurityStep);
        registerSecurityStep.classList.toggle("is-active", isSecurityStep);
        registerStepActions.classList.toggle("is-security", isSecurityStep);
        registerProgressSteps.forEach(function (progressStep) {
            progressStep.classList.toggle(
                "is-active",
                progressStep.dataset.progressStep === (isSecurityStep ? "security" : "account")
            );
        });

        if (isSecurityStep) {
            updateRegisterReview();
        }

        const registerPanel = registerForm?.closest(".auth-panel-register");
        const authShell = document.getElementById("authShell");

        if (registerPanel && authShell) {
            requestAnimationFrame(function () {
                authShell.style.height = `${registerPanel.scrollHeight}px`;
            });
        }

        const focusTarget = isSecurityStep
            ? registerSecurityStep.querySelector("input, select")
            : registerAccountStep.querySelector("input, select");

        focusTarget?.focus();
    };

    registerNextButton?.addEventListener("click", function () {
        const accountFields = registerAccountStep?.querySelectorAll("input, select") || [];
        const accountIsValid = Array.from(accountFields).every(function (field) {
            return field.checkValidity();
        });

        if (!accountIsValid) {
            registerAccountStep?.querySelector(":invalid")?.reportValidity();
            return;
        }

        showRegisterStep("security");
    });

    registerBackButton?.addEventListener("click", function () {
        showRegisterStep("account");
    });

    registerForm?.querySelectorAll("input, select").forEach(function (field) {
        field.addEventListener("input", updateRegisterReview);
        field.addEventListener("change", updateRegisterReview);
    });

    registerPassword?.addEventListener("input", function () {
        updatePasswordFeedback();
        updatePasswordMatch();
    });

    registerConfirmPassword?.addEventListener("input", updatePasswordMatch);

    registerForm?.querySelectorAll("[data-register-password-toggle]").forEach(function (toggle) {
        toggle.addEventListener("click", function () {
            const input = document.getElementById(toggle.dataset.registerPasswordToggle);
            const icon = toggle.querySelector("i");

            if (!input || !icon) {
                return;
            }

            const showingPassword = input.type === "password";
            input.type = showingPassword ? "text" : "password";
            input.focus();

            const cursorPosition = input.value.length;
            input.setSelectionRange(cursorPosition, cursorPosition);

            icon.classList.toggle("bi-eye", !showingPassword);
            icon.classList.toggle("bi-eye-slash", showingPassword);
            toggle.setAttribute("aria-label", showingPassword ? "Hide password" : "Show password");
        });
    });

    registerForm?.addEventListener("submit", function (event) {
        const activeStep = registerSecurityStep && !registerSecurityStep.hidden
            ? registerSecurityStep
            : registerAccountStep;
        const invalidField = Array.from(activeStep?.querySelectorAll("input, select, textarea") || [])
            .find(function (field) {
                return !field.checkValidity();
            });

        if (invalidField) {
            event.preventDefault();
            invalidField.reportValidity();
            return;
        }

        if (activeStep === registerAccountStep) {
            event.preventDefault();
            showRegisterStep("security");
            return;
        }

        if (!registerSubmitButton) {
            return;
        }

        registerSubmitButton.disabled = true;
        registerSubmitButton.classList.add("is-loading");
        registerSubmitButton.querySelector("span:not(.login-button-icon)").textContent = "Submitting...";
    });

    const authShell = document.getElementById("authShell");
    const authToggleButtons = document.querySelectorAll("[data-auth-toggle]");

    if (authShell && authToggleButtons.length) {
        const authPanels = authShell.querySelector(".auth-panels");
        const authPanelLogin = authShell.querySelector(".auth-panel-login");
        const authPanelRegister = authShell.querySelector(".auth-panel-register");

        const syncAuthShellHeight = function () {
            if (!authPanels || !authPanelLogin || !authPanelRegister) {
                return;
            }

            const activePanel = authShell.classList.contains("is-register-mode")
                ? authPanelRegister
                : authPanelLogin;

            authShell.style.height = `${activePanel.scrollHeight}px`;
        };

        const applyAuthMode = function (mode) {
            if (!authShell) {
                return;
            }

            if (mode === "register") {
                authShell.classList.add("is-register-mode");
            } else {
                authShell.classList.remove("is-register-mode");
            }

            requestAnimationFrame(syncAuthShellHeight);
        };

        authToggleButtons.forEach(function (button) {
            button.addEventListener("click", function (event) {
                event.preventDefault();
                applyAuthMode(button.dataset.authToggle || "login");
            });
        });

        syncAuthShellHeight();
    }

    /* =====================================================
       PASSWORD SHOW / HIDE
    ====================================================== */

    const password =
        document.getElementById("password");

    const passwordToggle =
        document.getElementById("passwordToggle");

    const passwordIcon =
        document.getElementById("passwordIcon");

    if (
        password &&
        passwordToggle &&
        passwordIcon
    ) {

        passwordToggle.addEventListener(
            "click",
            function () {

                if (
                    password.type === "password"
                ) {

                    password.type = "text";

                    passwordIcon.classList.remove(
                        "bi-eye"
                    );

                    passwordIcon.classList.add(
                        "bi-eye-slash"
                    );

                    passwordToggle.setAttribute(
                        "aria-label",
                        "Hide password"
                    );

                } else {

                    password.type = "password";

                    passwordIcon.classList.remove(
                        "bi-eye-slash"
                    );

                    passwordIcon.classList.add(
                        "bi-eye"
                    );

                    passwordToggle.setAttribute(
                        "aria-label",
                        "Show password"
                    );

                }

            }
        );

    }

    /* =====================================================
       LOGIN FORM
    ====================================================== */

    const loginForm =
        document.getElementById("loginForm");

    const loginButton =
        document.getElementById("loginButton");

    const loginButtonText =
        document.getElementById(
            "loginButtonText"
        );

    if (
        loginForm &&
        loginButton &&
        loginButtonText
    ) {

        loginForm.addEventListener(
            "submit",
            function () {

                /*
                 * Disable button to prevent
                 * multiple submissions.
                 */

                loginButton.disabled = true;

                /*
                 * Change button text.
                 */

                loginButtonText.textContent =
                    "Signing In...";

                /*
                 * Change login icon.
                 */

                const icon =
                    loginButton.querySelector(
                        ".login-button-icon i"
                    );

                if (icon) {

                    icon.className =
                        "bi bi-arrow-repeat";

                    icon.style.animation =
                        "loginSpin 1s linear infinite";

                }

            }
        );

    }

    /* =====================================================
       LOGIN SPINNER
    ====================================================== */

    const spinnerStyle =
        document.createElement("style");

    spinnerStyle.textContent = `

        @keyframes loginSpin {

            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }

        }

    `;

    document.head.appendChild(
        spinnerStyle
    );

});
