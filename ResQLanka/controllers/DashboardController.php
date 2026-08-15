<?php

require_once __DIR__ . "/../config/session.php";

class DashboardController
{
    public function userDashboard()
    {
        if (!isset($_SESSION["user_id"])) {

            header(
                "Location: ../views/auth/login.php"
            );

            exit();
        }

        if ($_SESSION["role"] !== "registered_user") {

            header(
                "Location: ../views/auth/login.php"
            );

            exit();
        }

        require_once __DIR__ .
            "/../views/dashboard/user_dashboard.php";
    }
}
