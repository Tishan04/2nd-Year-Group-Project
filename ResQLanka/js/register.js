document.addEventListener("DOMContentLoaded", function () {
    const toggleButtons =
        document.querySelectorAll(".toggle-password");

    toggleButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            const targetId = button.dataset.target;
            const passwordInput =
                document.getElementById(targetId);

            if (!passwordInput) {
                return;
            }

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                button.classList.remove("fa-eye");
                button.classList.add("fa-eye-slash");
            } else {
                passwordInput.type = "password";
                button.classList.remove("fa-eye-slash");
                button.classList.add("fa-eye");
            }
        });
    });

    const registerForm =
        document.getElementById("registerForm");

    const password =
        document.getElementById("password");

    const confirmPassword =
        document.getElementById("confirm_password");

    if (
        registerForm &&
        password &&
        confirmPassword
    ) {
        registerForm.addEventListener(
            "submit",
            function (event) {
                confirmPassword.setCustomValidity("");

                if (
                    password.value !==
                    confirmPassword.value
                ) {
                    confirmPassword.setCustomValidity(
                        "Passwords do not match."
                    );

                    confirmPassword.reportValidity();
                    event.preventDefault();
                }
            }
        );

        confirmPassword.addEventListener(
            "input",
            function () {
                confirmPassword.setCustomValidity("");
            }
        );
    }
});
