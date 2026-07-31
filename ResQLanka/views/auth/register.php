<?php

require_once __DIR__ . "/../../config/session.php";

$registerErrors = $_SESSION["register_errors"] ?? [];
$old = $_SESSION["register_old"] ?? [];

unset($_SESSION["register_errors"]);
unset($_SESSION["register_old"]);

function oldValue(string $field, array $old): string
{
    return htmlspecialchars(
        $old[$field] ?? "",
        ENT_QUOTES,
        "UTF-8"
    );
}

$districts = [
    "Ampara",
    "Anuradhapura",
    "Badulla",
    "Batticaloa",
    "Colombo",
    "Galle",
    "Gampaha",
    "Hambantota",
    "Jaffna",
    "Kalutara",
    "Kandy",
    "Kegalle",
    "Kilinochchi",
    "Kurunegala",
    "Mannar",
    "Matale",
    "Matara",
    "Monaragala",
    "Mullaitivu",
    "Nuwara Eliya",
    "Polonnaruwa",
    "Puttalam",
    "Ratnapura",
    "Trincomalee",
    "Vavuniya"
];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Register | ResQ Lanka</title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="../../css/registerstyles.css"
    >
</head>

<body>

<div class="background"></div>

<div class="container">

    <header>

        <div class="logo-area">

            <img
                src="../../images/logo.png"
                alt="ResQ Lanka logo"
            >

            <div>
                <h1>ResQ Lanka</h1>

                <p>
                    Disaster &amp; Crisis Management System
                </p>
            </div>

        </div>

        <div class="header-buttons">

            <a
                href="../fuel/search.php"
                class="fuel"
            >
                <i class="fa-solid fa-gas-pump"></i>

                <div>
                    <span>CHECK FUEL</span>
                    <span>AVAILABILITY</span>
                </div>
            </a>

            <a
                href="../disaster/report_disaster.php"
                class="danger"
            >
                <i class="fa-solid fa-triangle-exclamation"></i>

                <div>
                    <span>INFORM ABOUT</span>
                    <span>DISASTER!</span>
                </div>
            </a>

        </div>

    </header>

    <main>

        <!-- REGISTRATION FORM -->
        <section class="register-card">

            <div class="welcome-icon">
                <i class="fa-solid fa-user-plus"></i>
            </div>

            <h2>CREATE YOUR ACCOUNT</h2>

            <p class="register-description">
                Join ResQ Lanka and take part in volunteer
                assignments across the country.
            </p>

            <div class="divider"></div>

            <form
                action="../../controllers/RegisterController.php"
                method="POST"
                autocomplete="off"
            >

                <?php if (!empty($registerErrors)): ?>

                    <div class="form-errors">

                        <?php foreach ($registerErrors as $error): ?>

                            <p>
                                <i class="fa-solid fa-circle-exclamation"></i>

                                <?= htmlspecialchars(
                                    $error,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </p>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

                <div class="form-grid">

                    <!-- First Name -->
                    <div class="form-group">

                        <label for="first_name">
                            First Name
                        </label>

                        <div class="input-box">

                            <i class="fa-regular fa-user"></i>

                            <input
                                type="text"
                                id="first_name"
                                name="first_name"
                                placeholder="Enter your first name"
                                value="<?= oldValue(
                                    "first_name",
                                    $old
                                ) ?>"
                                maxlength="50"
                                required
                            >

                        </div>

                    </div>

                    <!-- Last Name -->
                    <div class="form-group">

                        <label for="last_name">
                            Last Name
                        </label>

                        <div class="input-box">

                            <i class="fa-regular fa-user"></i>

                            <input
                                type="text"
                                id="last_name"
                                name="last_name"
                                placeholder="Enter your last name"
                                value="<?= oldValue(
                                    "last_name",
                                    $old
                                ) ?>"
                                maxlength="50"
                                required
                            >

                        </div>

                    </div>

                    <!-- Username -->
                    <div class="form-group">

                        <label for="username">
                            Username
                        </label>

                        <div class="input-box">

                            <i class="fa-solid fa-at"></i>

                            <input
                                type="text"
                                id="username"
                                name="username"
                                placeholder="Choose a username"
                                value="<?= oldValue(
                                    "username",
                                    $old
                                ) ?>"
                                minlength="4"
                                maxlength="30"
                                pattern="[A-Za-z0-9_]+"
                                title="Use only letters, numbers and underscores"
                                required
                            >

                        </div>

                    </div>

                    <!-- Email -->
                    <div class="form-group">

                        <label for="email">
                            Email Address
                        </label>

                        <div class="input-box">

                            <i class="fa-regular fa-envelope"></i>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="Enter your email address"
                                value="<?= oldValue(
                                    "email",
                                    $old
                                ) ?>"
                                maxlength="100"
                                required
                            >

                        </div>

                    </div>

                    <!-- Phone -->
                    <div class="form-group">

                        <label for="phone">
                            Contact Number
                        </label>

                        <div class="input-box">

                            <i class="fa-solid fa-phone"></i>

                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                placeholder="Example: 0771234567"
                                value="<?= oldValue(
                                    "phone",
                                    $old
                                ) ?>"
                                maxlength="20"
                                required
                            >

                        </div>

                    </div>

                    <!-- District -->
                    <div class="form-group">

                        <label for="district">
                            District
                        </label>

                        <div class="input-box select-box">

                            <i class="fa-solid fa-map-location-dot"></i>

                            <select
                                id="district"
                                name="district"
                                required
                            >

                                <option value="">
                                    Select your district
                                </option>

                                <?php foreach ($districts as $district): ?>

                                    <option
                                        value="<?= htmlspecialchars(
                                            $district,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                        <?= (
                                            ($old["district"] ?? "")
                                            === $district
                                        ) ? "selected" : "" ?>
                                    >
                                        <?= htmlspecialchars(
                                            $district,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                    </div>

                </div>

                <!-- Address -->
                <div class="form-group full-width">

                    <label for="address">
                        Residential Address
                    </label>

                    <div class="input-box">

                        <i class="fa-solid fa-location-dot"></i>

                        <input
                            type="text"
                            id="address"
                            name="address"
                            placeholder="Enter your residential address"
                            value="<?= oldValue(
                                "address",
                                $old
                            ) ?>"
                            required
                        >

                    </div>

                </div>

                <div class="form-grid">

                    <!-- Password -->
                    <div class="form-group">

                        <label for="password">
                            Password
                        </label>

                        <div class="input-box">

                            <i class="fa-solid fa-lock"></i>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Create a password"
                                minlength="8"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                data-target="password"
                                aria-label="Show or hide password"
                            >
                                <i class="fa-regular fa-eye"></i>
                            </button>

                        </div>

                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">

                        <label for="confirm_password">
                            Confirm Password
                        </label>

                        <div class="input-box">

                            <i class="fa-solid fa-lock"></i>

                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                placeholder="Confirm your password"
                                minlength="8"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                data-target="confirm_password"
                                aria-label="Show or hide password"
                            >
                                <i class="fa-regular fa-eye"></i>
                            </button>

                        </div>

                    </div>

                </div>

                <p class="password-note">
                    Your password must contain at least 8 characters.
                </p>

                <div class="terms-box">

                    <input
                        type="checkbox"
                        id="terms"
                        name="terms"
                        value="1"
                        required
                    >

                    <label for="terms">
                        I confirm that the information provided is
                        accurate and I agree to the terms and conditions.
                    </label>

                </div>

                <button
                    type="submit"
                    class="register-submit"
                >
                    <i class="fa-solid fa-user-plus"></i>
                    CREATE ACCOUNT
                </button>

            </form>

            <div class="login-box">

                <p>Already have an account?</p>

                <a
                    href="login.php"
                    class="login-btn"
                >
                    SIGN IN
                </a>

            </div>

        </section>

        <!-- RIGHT INFORMATION PANEL -->
        <section class="information-card">

            <h2>WHY JOIN RESQ LANKA?</h2>

            <div class="benefit">

                <div class="benefit-icon blue-bg">
                    <i class="fa-solid fa-hand-holding-heart"></i>
                </div>

                <div>
                    <h3>Support Communities</h3>

                    <p>
                        Take part in disaster response,
                        recovery and community assistance.
                    </p>
                </div>

            </div>

            <div class="benefit">

                <div class="benefit-icon red-bg">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>

                <div>
                    <h3>Respond to Emergencies</h3>

                    <p>
                        Receive information about active disaster
                        assignments and volunteer opportunities.
                    </p>
                </div>

            </div>

            <div class="benefit">

                <div class="benefit-icon green-bg">
                    <i class="fa-solid fa-ranking-star"></i>
                </div>

                <div>
                    <h3>Build Your Volunteer Ranking</h3>

                    <p>
                        Earn assignment points, progress through
                        volunteer tiers and gain experience.
                    </p>
                </div>

            </div>

            <div class="benefit">

                <div class="benefit-icon purple-bg">
                    <i class="fa-solid fa-certificate"></i>
                </div>

                <div>
                    <h3>Receive Certificates</h3>

                    <p>
                        Eligible volunteers can receive electronic
                        certificates after completing assignments.
                    </p>
                </div>

            </div>

            <div class="info-box">

                <div class="shield">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>

                <div>
                    <h3>Your information is protected.</h3>

                    <p>
                        Your account details will only be used
                        for ResQ Lanka services and assignments.
                    </p>
                </div>

            </div>

        </section>

    </main>

    <footer>

        <div>
            <i class="fa-solid fa-shield"></i>

            <span>
                Building safer communities through preparedness,
                response and resilience.
            </span>
        </div>

        <div>
            © 2026 ResQ Lanka. All rights reserved.
        </div>

    </footer>

</div>

<script>
    const passwordButtons =
        document.querySelectorAll(".password-toggle");

    passwordButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            const targetId = button.dataset.target;
            const input = document.getElementById(targetId);
            const icon = button.querySelector("i");

            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        });
    });
</script>

</body>
</html>
