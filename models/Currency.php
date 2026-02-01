<?php
require_once __DIR__ . "/../config/database.php";

class Currency {

    private $conn;

    public function __construct() {
        $this->conn = (new Database())->connect();
    }

    private function generateUniqueId($length = 8) {
        return 'CUR_' . strtoupper(bin2hex(random_bytes($length / 2)));
    }

    /* ================= ADD CURRENCY ================= */
    public function addCurrency($data) {
        // Check for duplicates
        $check = $this->conn->prepare("SELECT 1 FROM currencies WHERE code = ? AND company_id = ?");
        $check->execute([$data['code'], $data['company_id']]);

        if ($check->rowCount() > 0) {
            return ['status' => false, 'message' => 'Currency code already exists.'];
        }

        $currency_id = $this->generateUniqueId();

        $stmt = $this->conn->prepare("
            INSERT INTO currencies
            (currency_id, company_id, code, name, symbol, exchange_rate, created_at)
            VALUES
            (?, ?, ?, ?, ?, ?, NOW())
        ");

        if ($stmt->execute([
            $currency_id,
            $data['company_id'],
            $data['code'],
            $data['name'],
            $data['symbol'],
            $data['exchange_rate']
        ])) {
            return ['status' => true];
        }
        return ['status' => false, 'message' => 'Database error'];
    }

    /* ================= GET CURRENCIES ================= */
    public function getAllCurrencies($company_id, $limit, $offset, $search = '') {
        $sql = "SELECT * FROM currencies WHERE company_id = :company_id";

        if (!empty($search)) {
            $sql .= " AND (name LIKE :search OR code LIKE :search) ";
        }

        $sql .= " ORDER BY code ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':company_id', $company_id);
        if (!empty($search)) $stmt->bindValue(':search', "%$search%");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ================= COUNT CURRENCIES ================= */
    public function countCurrencies($company_id, $search = '') {
        $sql = "SELECT COUNT(*) FROM currencies WHERE company_id = :company_id";
        if (!empty($search)) {
            $sql .= " AND (name LIKE :search OR code LIKE :search)";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':company_id', $company_id);
        if (!empty($search)) $stmt->bindValue(':search', "%$search%");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /* ================= UPDATE CURRENCY ================= */
    public function updateCurrency($data) {
        $stmt = $this->conn->prepare("
            UPDATE currencies SET
                code = ?, name = ?, symbol = ?, exchange_rate = ?
            WHERE currency_id = ? AND company_id = ?
        ");

        return $stmt->execute([
            $data['code'], $data['name'], $data['symbol'], $data['exchange_rate'],
            $data['currency_id'], $data['company_id']
        ]);
    }

    /* ================= DELETE CURRENCY ================= */
    public function deleteCurrency($currency_id, $company_id) {
        // Optional: Check if used in Products/Clients/Taxes before deleting
        $stmt = $this->conn->prepare("DELETE FROM currencies WHERE currency_id = ? AND company_id = ?");
        return $stmt->execute([$currency_id, $company_id]);
    }
}
?>