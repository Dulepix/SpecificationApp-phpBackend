<?php

class Products extends Connection {
    private $conn;

    public function __construct() {
        $this->conn = $this->connect();
    }

    public function searchProducts($product, $offset) {
        $sql = "
            SELECT 
                product_size.Id AS ProductSizeId,
                categories.Name AS Kategorija, 
                products.Name  AS Proizvod, 
                CONCAT_WS(' ',
                    (SELECT sizes.Size FROM sizes WHERE sizes.Id = product_size.SizeIdFirst),
                    (SELECT sizes.Size FROM sizes WHERE sizes.Id = product_size.SizeIdSecond),
                    (SELECT sizes.Size FROM sizes WHERE sizes.Id = product_size.SizeIdThird)
                ) AS Sizes
            FROM products
            INNER JOIN product_size ON product_size.ProductId = products.Id
            INNER JOIN categories   ON categories.Id   = products.CategoryId
            WHERE CONCAT_WS(' ',
                    products.Name,
                    (SELECT sizes.Size FROM sizes WHERE sizes.Id = product_size.SizeIdFirst),
                    (SELECT sizes.Size FROM sizes WHERE sizes.Id = product_size.SizeIdSecond),
                    (SELECT sizes.Size FROM sizes WHERE sizes.Id = product_size.SizeIdThird)
                ) LIKE '%" . $this->conn->real_escape_string(mb_strtolower($product, 'UTF-8')) . "%'
            ORDER BY categories.Name ASC, products.Name ASC, product_size.Id ASC
            LIMIT 20 OFFSET " . $offset;

        if ($this->conn->connect_error) {
            die(json_encode(["status" => "error", "message" => "Database connection failed: " . $this->conn->connect_error]));
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = [
                "ProductSizeId" => $row["ProductSizeId"],
                "Kategorija" => $row["Kategorija"],
                "Proizvod" => $row["Proizvod"],
                "Sizes" => $row["Sizes"]
            ];
        }

        if ($products != [] || $offset != 0) {
            echo json_encode(["status" => "success", "data" => $products]);
        } else {
            echo json_encode(["status" => "error", "message" => "No products found"]);
        }

        $stmt->close();
        $this->conn->close();
    }
}