<?php

session_start();

require_once "../models/User.php";

$user = new User();

if($_SERVER["REQUEST_METHOD"]=="POST")
{

    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    $loggedUser = $user->login($username);

    if($loggedUser)
    {

        if(password_verify($password,$loggedUser["password"]))
        {

            $_SESSION["user_id"] = $loggedUser["user_id"];
            $_SESSION["username"] = $loggedUser["username"];
            $_SESSION["role"] = $loggedUser["role"];
            $_SESSION["name"] =
            $loggedUser["first_name"]." ".
            $loggedUser["last_name"];
            $_SESSION["district"] =
            $loggedUser["district"];
            $_SESSION["tier"] =
            $loggedUser["tier"];
            $_SESSION["points"] =
            $loggedUser["points"];

            if($loggedUser["role"]=="super_admin")
            {
                header("Location: ../views/superadmin/dashboard.php");
            }

            elseif($loggedUser["role"]=="district_admin")
            {
                header("Location: ../views/admin/dashboard.php");
            }

            else
            {
                header("Location: ../views/user/dashboard.php");
            }

            exit();
        }
    }

    header("Location: ../views/auth/login.php?error=1");
}
