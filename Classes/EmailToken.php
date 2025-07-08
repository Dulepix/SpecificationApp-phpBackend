<?php

class EmailToken extends Connection{
    private $token;

    public function __construct($token)
    {
        $this->token = trim($token);
    }

    public function Verify(){
        $token_hash = hash("sha256", $this->token);

        $conn = $this->connect();

        if ($conn->connect_error) {
            echo "Database connection failed.";
            exit();
        }

        $stmt = $conn->prepare("SELECT * FROM users WHERE Account_activation_hash = ?");
        if($stmt){
            $stmt->bind_param("s", $token_hash);
            if($stmt->execute()){
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();

                if($user === null){
                    echo "Token not found";
                    exit();
                }

                $stmt = $conn->prepare("UPDATE users SET Account_activation_hash = NULL WHERE Id = ?");
                $stmt->bind_param("s", $user["Id"]);
                $stmt->execute();

                echo "Verified successfully";
            }else{
                echo "Query failed to execute";
            }
        }else{
            echo "Failed to prepare SQL statement";
        }
    }
}