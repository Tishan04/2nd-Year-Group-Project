<?php

require_once("../config/database.php");
require_once("../config/session.php");
require_once("../models/User.php");

if($_SERVER["REQUEST_METHOD"]=="POST"){

    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    $userModel = new User($conn);

    $user = $userModel->login($username);

    if($user){

        if(password_verify($password,$user["password"])){

            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["role"] = $user["role"];

            if($user["role"]=="SUPER_ADMIN"){

                header("Location: ../views/dashboard/super_dashboard.php");

            }

            elseif($user["role"]=="DISTRICT_ADMIN"){

                header("Location: ../views/dashboard/district_dashboard.php");

            }

            else{

                header("Location: ../views/dashboard/user_dashboard.php");

            }

            exit();

        }

    }

    header("Location: ../views/auth/login.php?error=1");

}
