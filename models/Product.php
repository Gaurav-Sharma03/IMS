<?php
class Product {

    private $conn;
    private $table = "products";

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /* =====================================================
       1. ADD PRODUCT
    ===================================================== */
    public function addProduct($data) {
        $product_id = $this->generateUniqueId();

        if ($this->productExists($data['company_id'], $data['name'], $data['sku'])) {
            return false;
        }

        $sql = "INSERT INTO {$this->table} 
                (product_id, company_id, name, sku, category, product_type, unit, price, currency_id, tax_id, description, stock_quantity, status, created_at)
                VALUES 
                (:id, :cid, :name, :sku, :cat, :type, :unit, :price, :curr, :tax, :desc, :stock, :status, NOW())";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':id'       => $product_id,
            ':cid'      => $data['company_id'],
            ':name'     => $data['name'],
            ':sku'      => $data['sku'],
            ':cat'      => $data['category'],
            ':type'     => $data['product_type'],
            ':unit'     => $data['unit'],
            ':price'    => $data['price'],
            ':curr'     => $data['currency_id'],
            ':tax'      => $data['tax_id'],
            ':desc'     => $data['description'],
            ':stock'    => $data['stock_quantity'],
            ':status'   => $data['status']
        ]);
    }

    /* =====================================================
       2. UPDATE PRODUCT (Full Update)
    ===================================================== */
    public function updateProduct($data, $company_id) {
        $sql = "UPDATE {$this->table} SET 
                name = :name, 
                sku = :sku, 
                category = :category, 
                product_type = :type,
                unit = :unit,
                price = :price, 
                currency_id = :curr, 
                tax_id = :tax,
                stock_quantity = :stock, 
                description = :desc, 
                status = :status,
                updated_at = NOW()
                WHERE product_id = :id AND company_id = :cid";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':name'     => $data['name'],
            ':sku'      => $data['sku'],
            ':category' => $data['category'],
            ':type'     => $data['product_type'],
            ':unit'     => $data['unit'],
            ':price'    => $data['price'],
            ':curr'     => $data['currency_id'],
            ':tax'      => $data['tax_id'],
            ':stock'    => $data['stock_quantity'],
            ':desc'     => $data['description'],
            ':status'   => $data['status'],
            ':id'       => $data['product_id'],
            ':cid'      => $company_id
        ]);
    }

    /* =====================================================
       3. DELETE PRODUCT
    ===================================================== */
    public function deleteProduct($product_id, $company_id) {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE product_id = :id AND company_id = :cid LIMIT 1");
        $stmt->execute([':id' => $product_id, ':cid' => $company_id]);
        return $stmt->rowCount() > 0;
    }

    /* =====================================================
       4. GET ALL PRODUCTS
    ===================================================== */
    public function getAllProducts($company_id, $limit, $offset, $search = '') {
        $sql = "SELECT p.*, c.code AS currency_code 
                FROM {$this->table} p
                LEFT JOIN currencies c ON p.currency_id = c.currency_id
                WHERE p.company_id = :cid 
                AND (p.name LIKE :search OR p.sku LIKE :search)
                ORDER BY p.created_at DESC 
                LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':cid', $company_id, PDO::PARAM_INT);
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =====================================================
       5. COUNT PRODUCTS
    ===================================================== */
    public function countProducts($company_id, $search = '') {
        $sql = "SELECT COUNT(*) FROM {$this->table} 
                WHERE company_id = :cid 
                AND (name LIKE :search OR sku LIKE :search)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':cid' => $company_id, ':search' => "%$search%"]);
        return (int)$stmt->fetchColumn();
    }

    /* =====================================================
       HELPERS
    ===================================================== */
    private function generateUniqueId($length = 8) {
        return strtoupper(bin2hex(random_bytes($length / 2)));
    }

    private function productExists($company_id, $name, $sku) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE company_id = :cid AND (name = :name OR sku = :sku)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':cid' => $company_id, ':name' => $name, ':sku' => $sku]);
        return $stmt->fetchColumn() > 0;
    }
}
?>