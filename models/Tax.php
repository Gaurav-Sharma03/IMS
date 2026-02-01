<?php
require_once __DIR__ . "/../config/database.php";

class Tax {

    private $conn;

    public function __construct() {
        $this->conn = (new Database())->connect();
    }

    private function generateUniqueId($length = 8) {
        return 'TAX_' . strtoupper(bin2hex(random_bytes($length / 2)));
    }

    /* ================= ADD TAX ================= */
    public function addTax($data) {
        // Check for duplicates
        $check = $this->conn->prepare("SELECT 1 FROM taxes WHERE name = ? AND company_id = ?");
        $check->execute([$data['name'], $data['company_id']]);

        if ($check->rowCount() > 0) {
            return false;
        }

        $tax_id = $this->generateUniqueId();

        $stmt = $this->conn->prepare("
            INSERT INTO taxes
            (tax_id, company_id, name, country, rate, description, currency_id, created_at)
            VALUES
            (?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        return $stmt->execute([
            $tax_id,
            $data['company_id'],
            $data['name'],
            $data['country'],
            $data['rate'],
            $data['description'],
            $data['currency_id']
        ]) ? $tax_id : false;
    }

    /* ================= GET TAXES ================= */
    public function getAllTaxes($company_id, $limit, $offset, $search = '') {
        $sql = "SELECT t.*, c.code AS currency_code 
                FROM taxes t
                LEFT JOIN currencies c ON t.currency_id = c.currency_id
                WHERE t.company_id = :company_id";

        if (!empty($search)) {
            $sql .= " AND t.name LIKE :search ";
        }

        $sql .= " ORDER BY t.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':company_id', $company_id);
        if (!empty($search)) $stmt->bindValue(':search', "%$search%");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ================= COUNT TAXES ================= */
    public function countTaxes($company_id, $search = '') {
        $sql = "SELECT COUNT(*) FROM taxes WHERE company_id = :company_id";
        if (!empty($search)) {
            $sql .= " AND name LIKE :search";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':company_id', $company_id);
        if (!empty($search)) $stmt->bindValue(':search', "%$search%");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /* ================= UPDATE TAX ================= */
    public function updateTax($data) {
        $stmt = $this->conn->prepare("
            UPDATE taxes SET
                name = ?, country = ?, rate = ?, description = ?, currency_id = ?
            WHERE tax_id = ? AND company_id = ?
        ");

        return $stmt->execute([
            $data['name'], $data['country'], $data['rate'], $data['description'], 
            $data['currency_id'], $data['tax_id'], $data['company_id']
        ]);
    }

    /* ================= DELETE TAX ================= */
    public function deleteTax($tax_id, $company_id) {
        $stmt = $this->conn->prepare("DELETE FROM taxes WHERE tax_id = ? AND company_id = ?");
        return $stmt->execute([$tax_id, $company_id]);
    }
}
?>