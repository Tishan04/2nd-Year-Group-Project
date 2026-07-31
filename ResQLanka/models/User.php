<?php

require_once "../config/Database.php";

class User
{
    private $conn;
    private $table = "users";

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /*
    ---------------------------------
    LOGIN
    ---------------------------------
    */

    public function login($username)
    {
        $query = "SELECT * FROM users WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username",$username);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /*
    ---------------------------------
    CHECK USERNAME
    ---------------------------------
    */

    public function usernameExists($username)
    {
        $query = "SELECT user_id FROM users WHERE username=:username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username",$username);
        $stmt->execute();

        return $stmt->rowCount()>0;
    }

    /*
    ---------------------------------
    CHECK EMAIL
    ---------------------------------
    */

    public function emailExists($email)
    {
        $query = "SELECT user_id FROM users WHERE email=:email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email",$email);
        $stmt->execute();

        return $stmt->rowCount()>0;
    }

    /*
    ---------------------------------
    REGISTER USER
    ---------------------------------
    */

    public function register($data)
    {
        $query = "INSERT INTO users(
                    first_name,
                    last_name,
                    username,
                    email,
                    password,
                    phone,
                    address,
                    district,
                    role,
                    tier,
                    points
                )

                VALUES(
                    :first_name,
                    :last_name,
                    :username,
                    :email,
                    :password,
                    :phone,
                    :address,
                    :district,
                    'registered_user',
                    'Bronze',
                    0
                )";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute($data);
    }

}
?>
