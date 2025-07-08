<?php

class User extends Connection{
    private $userId;
    private $conn;

    public function __construct($userId)
    {
        $this->userId = $userId;
        $this->conn = $this->connect();
    }

    public function __destruct()
    {
        if ($this->conn && $this->conn->ping()) {
            $this->conn->close();
        }
    }


     public function getUsername(): array
    {
        $stmt = $this->conn->prepare('SELECT Username FROM users WHERE id = ?');

        if (!$stmt)
            return ['status'  => 'error','message' => 'Greška pri pripremi upita: ' . $this->conn->error];

        $stmt->bind_param('i', $this->userId);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: 'Greška pri izvršavanju upita';
            $stmt->close();
            return ['status' => 'error', 'message' => $error];
        }

        $result = $stmt->get_result();
        $stmt->close();

        if ($row = $result->fetch_assoc()) {
            return ['status' => 'success', 'username' => $row['Username']];
        }

        return ['status'  => 'error','message' => 'Korisnik nije pronađen'];
    }


    public function getSpecifications(){
        $stmt = $this->conn->prepare("SELECT Id, Name, Visibility, CreatedAt, LastEdited FROM specifications WHERE UserId = ?");
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $stmt->close();

        $specifications = [];
        $username = $this->getUsername()['username'] ?? '';

        while($row = $result->fetch_assoc()){
            $specifications[] = [
                "Id" => $row["Id"],
                "Name" => $row["Name"],
                "Visibility" => $row["Visibility"],
                "CreatedAt" => $row["CreatedAt"],
                "LastEdited" => $row["LastEdited"]
            ];
        }

        if($username != ''){
            echo json_encode(["status" => "success", "data" => $specifications, "username" =>  $username, "userId" => $this->userId]);
        }else{
            echo json_encode(["status" => "error", "message" => "Korisnik nije pronađen"]);
        }
    }

    public function insertSpecification($name, $visibility, $price, $data){
        $stmt = $this->conn->prepare("INSERT INTO specifications (Name, Visibility, Price, UserId) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssdi", $name, $visibility, $price, $this->userId);
        
        if($stmt->execute()){
            $specificationId = $stmt->insert_id;
            foreach($data as $item){
                $stmt2 = $this->conn->prepare("INSERT INTO specifications_product_size (SpecificationsId, Product_sizeId, Quantity, Position) VALUES (?, ?, ?, ?)");
                $stmt2->bind_param("iisi", $specificationId, $item['ProductSizeId'], $item['Quantity'], $item['Order']);
                $stmt2->execute();
                $stmt2->close();
            }
            echo json_encode(["status" => "success", "message" => "Specification created successfully"]);
        }else{
            echo json_encode(["status" => "error", "message" => "Failed to create specification"]);
        }

        $stmt->close();
    }
}
