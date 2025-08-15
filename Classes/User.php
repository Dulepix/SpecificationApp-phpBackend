<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\ExpiredException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

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
        if ($this->conn instanceof mysqli) {
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
                "CreatedAt" => explode(' ', $row["CreatedAt"])[0],
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

    public function getSpecification($specId){
        $stmt = $this->conn->prepare("SELECT Name, Visibility, Price FROM specifications WHERE Id = ? AND UserId = ?");
        $stmt->bind_param("ii", $specId, $this->userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        
        if ($row = $result->fetch_assoc()) {
            $stmt = $this->conn->prepare("
                SELECT 
                    specifications_product_size.Product_sizeId AS ProductSizeId, 
                    specifications_product_size.Quantity AS Quantity, 
                    products.Name AS Proizvod,
                    CONCAT_WS(' ',
                        (SELECT sizes.Size FROM sizes WHERE sizes.Id = product_size.SizeIdFirst),
                        (SELECT sizes.Size FROM sizes WHERE sizes.Id = product_size.SizeIdSecond),
                        (SELECT sizes.Size FROM sizes WHERE sizes.Id = product_size.SizeIdThird)
                    ) AS Sizes
                FROM specifications_product_size 
                INNER JOIN product_size ON product_size.Id = specifications_product_size.Product_sizeId
                INNER JOIN products ON products.Id = product_size.ProductId
                WHERE specifications_product_size.SpecificationsId = ?
                ORDER BY specifications_product_size.Position ASC
            ");

            $stmt->bind_param("i", $specId);
            $stmt->execute();

            $result2 = $stmt->get_result();
            $data = [];

            while ($row2 = $result2->fetch_assoc()) {
                $data[] = [
                    "ProductSizeId" => $row2["ProductSizeId"],
                    "Product" => $row2["Proizvod"],
                    "Sizes" => $row2["Sizes"],
                    "Quantity" => $row2["Quantity"],
                    "Checked" => false
                ];
            }

            $stmt->close();

            echo json_encode([
                "status" => "success",
                "specName" => $row['Name'],
                "visibility" => $row['Visibility'],
                "price" => $row['Price'],
                "data" => $data
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Specification not found"
            ]);
        }
    }

    public function updateSpecification($specName, $visibility, $price, $specificationId): bool{
        $stmt = $this->conn->prepare("UPDATE specifications SET Name = ?, Visibility = ?, Price = ? WHERE Id = ? AND UserId = ?");
        $stmt->bind_param("sidii", $specName, $visibility, $price, $specificationId, $this->userId);
        
        if($stmt->execute())
            return true;
        else
            return false;
        
        $stmt->close();
    }

    public function updateProducts($data, $specificationId): bool{
        $stmt = $this->conn->prepare("DELETE FROM specifications_product_size WHERE SpecificationsId = ?");
        $stmt->bind_param("i", $specificationId);
        $stmt->execute();
        $stmt->close();

        foreach($data as $item){
            $stmt2 = $this->conn->prepare("INSERT INTO specifications_product_size (SpecificationsId, Product_sizeId, Quantity, Position) VALUES (?, ?, ?, ?)");
            $stmt2->bind_param("iisi", $specificationId, $item['ProductSizeId'], $item['Quantity'], $item['Order']);
            if(!$stmt2->execute()){
                return false; // If any insert fails, return false
            }
            $stmt2->close();
        }

        return true;

    }

    public function handleUpdateSpecification($data){
        $updateSpecification = $this->updateSpecification($data['specName'], $data['visibility'], $data['price'], $data['editspecformId']);
        $updateProducts = true;
        if(isset($data['products'])){
            $updateProducts = $this->updateProducts($data['products'], $data['editspecformId']);
        }

        if($updateSpecification && $updateProducts){
            echo json_encode(["status" => "success", "message" => "Specification updated successfully"]);
        }else{
            echo json_encode(["status" => "error", "message" => "Failed to update specification, try again later."]);
        }
    }

    public function deleteSpecification($specificationId){
        $stmt = $this->conn->prepare("DELETE FROM specifications_product_size WHERE SpecificationsId = ?");
        $stmt->bind_param("i", $specificationId);
        
        if($stmt->execute()){
            $stmt = $this->conn->prepare("DELETE FROM specifications WHERE Id = ? AND UserId = ?");
            $stmt->bind_param("ii", $specificationId, $this->userId);
            if($stmt->execute()){
                echo json_encode(["status" => "success", "message" => "Specification deleted successfully"]);
            }else{
                echo json_encode(["status" => "error", "message" => "Failed to delete specification"]);
            }
        }else{
            echo json_encode(["status" => "error", "message" => "Failed to delete specification"]);
        }

        $stmt->close();
    }

    public function getProfileDetails(){
        $stmt = $this->conn->prepare("SELECT Name, Username, Company, Telnumber FROM users WHERE Id = ?");
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if($row = $result->fetch_assoc()){
            echo json_encode([
                "status" => "success",
                "fullname" => $row['Name'],
                "username" => $row['Username'],
                "company" => $row['Company'],
                "telnumber" => $row['Telnumber']
            ]);
        }else{
            echo json_encode(["status" => "error", "message" => "User not found"]);
        }
    }

    public function updateProfileDetails($name, $username, $company, $telnumber){
        $stmt = $this->conn->prepare("UPDATE users SET Name = ?, Username = ?, Company = ?, Telnumber = ? WHERE Id = ?");
        $stmt->bind_param("ssssi", $name, $username, $company, $telnumber, $this->userId);
        
        if($stmt->execute()){
            echo json_encode(["status" => "success", "message" => "Profile updated successfully"]);
        }else{
            echo json_encode(["status" => "error", "message" => "Failed to update profile"]);
        }

        $stmt->close();
    }

    public function makeFile($specificationId): \PhpOffice\PhpSpreadsheet\Spreadsheet{
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(__DIR__ . '/../Template.xlsx');
        $sheet = $spreadsheet->getActiveSheet();

        $stmt = $this->conn->prepare("
            SELECT specifications.Name as specName, specifications.Price, specifications.CreatedAt, users.Name, users.Company, users.Telnumber FROM specifications INNER JOIN users ON users.Id = specifications.UserId WHERE specifications.Id = ?
        ");
        $stmt->bind_param("i", $specificationId);
        if(!$stmt->execute()){
            throw new Exception("Something went wrong");
        }
        $result = $stmt->get_result();
        $result = $result->fetch_assoc();

        $sheet->setCellValue('D5', $result['specName']);

        $price = $result['Price'];
        $date = explode(' ', $result['CreatedAt'])[0];
        $name = $result['Name'];
        $company = $result['Company'];
        $telnumber = $result['Telnumber'];

        $stmt = $this->conn->prepare("
            SELECT 
                specifications_product_size.Product_sizeId AS ProductSizeId, 
                specifications_product_size.Quantity AS Quantity, 
                specifications_product_size.Position AS Position,
                products.Name AS Proizvod,
                CONCAT_WS(' ',
                    (SELECT sizes.Size FROM sizes WHERE sizes.Id = product_size.SizeIdFirst),
                    (SELECT sizes.Size FROM sizes WHERE sizes.Id = product_size.SizeIdSecond),
                    (SELECT sizes.Size FROM sizes WHERE sizes.Id = product_size.SizeIdThird)
                ) AS Sizes
            FROM specifications_product_size 
            INNER JOIN product_size ON product_size.Id = specifications_product_size.Product_sizeId
            INNER JOIN products ON products.Id = product_size.ProductId
            WHERE specifications_product_size.SpecificationsId = ?
            ORDER BY specifications_product_size.Position ASC
        ");
        $stmt->bind_param("i", $specificationId);
        if(!$stmt->execute()){
            throw new Exception("Something went wrong");
        }
        $result = $stmt->get_result();
        $rowIndex = 12;
        while ($row = $result->fetch_assoc()) {
            $sheet->setCellValue('B' . $rowIndex, $row['Position']);
            $sheet->getStyle('B' . $rowIndex)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            $sheet->setCellValue('C' . $rowIndex, $row['Proizvod'] . ' (' . $row['Sizes'] . ')');
            $sheet->getStyle('C' . $rowIndex)->getAlignment()->setWrapText(true);
            $sheet->setCellValue('E' . $rowIndex, $row['Quantity']);
            $sheet->getStyle('E' . $rowIndex)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            $rowIndex++;
        } 

        $sheet->setCellValue('B' . $rowIndex + 3, "IZVOĐENJE RADOVA " . $price . " €");
        $sheet->getStyle('B' . $rowIndex + 3)->getFont()->setBold(true);
        if($company != ''){
            $sheet->setCellValue('B' . $rowIndex + 4, "IZVOĐAČ: \"" . $company . "\"");
        }
        $sheet->setCellValue('B' . $rowIndex + 5, strtoupper($name));
        if($telnumber != ''){
            $sheet->setCellValue('B' . $rowIndex + 6, "mob. ". $telnumber);
        }
        $sheet->setCellValue('E' . $rowIndex + 3, $date);


        return $spreadsheet;
    }

    public function downloadSpecification($specificationId) {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="specifikacija_' . $specificationId . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($this->makeFile($specificationId));
        $writer->save('php://output');
        exit;
    }
}
