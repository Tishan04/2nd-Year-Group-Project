<?php

require_once("../config/database.php");

class User{

    private $conn;

    public function __construct($db){

        $this->conn = $db;

    }

    public function login($username){

        $sql = "SELECT * FROM users WHERE username = ?";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([$username]);

        return $stmt->fetch(PDO::FETCH_ASSOC);

    }

}

?>
