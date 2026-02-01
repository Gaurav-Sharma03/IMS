<?php
require_once __DIR__ ."/../config/database.php";

class Client {
    private $conn;

    public function __construct() {
        $this->conn = (new Database())->connect();
    }

    /* ================= ADD CLIENT ================= */
    public function addClient($data) {
        try {
            $this->conn->beginTransaction();

            // 1. Check Duplicates
            if (!empty($data['email'])) {
                $stmt = $this->conn->prepare("SELECT COUNT(*) FROM clients WHERE email = :email AND company_id = :cid");
                $stmt->execute([':email' => $data['email'], ':cid' => $data['company_id']]);
                if ($stmt->fetchColumn() > 0) {
                    $this->conn->rollBack();
                    return ['status' => false, 'message' => 'Email already exists for this company.'];
                }
            }

            // 2. Insert Client
            $sql = "INSERT INTO clients 
                (client_id, name, contact_person, phone, email, gst_vat, street, city, state, country, postal_code, address, currency_id, company_id, created_at)
                VALUES
                (:id, :name, :person, :phone, :email, :vat, :street, :city, :state, :country, :zip, :addr, :curr, :cid, NOW())";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':id' => $data['client_id'],
                ':name' => $data['name'],
                ':person' => $data['contact_person'],
                ':phone' => $data['phone'],
                ':email' => $data['email'],
                ':vat' => $data['gst_vat'],
                ':street' => $data['street'],
                ':city' => $data['city'],
                ':state' => $data['state'],
                ':country' => $data['country'],
                ':zip' => $data['postal_code'],
                ':addr' => $data['address'],
                ':curr' => $data['currency_id'],
                ':cid' => $data['company_id']
            ]);

            // 3. Create Login User (With Default Password passed from Controller)
            $plainPassword = $data['password']; // <--- Get dynamic password
            $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
            $userName = !empty($data['contact_person']) ? $data['contact_person'] : $data['name'];

            $userSql = "INSERT INTO users 
                (name, email, password, role, status, client_id, company_id, created_at) 
                VALUES 
                (:name, :email, :password, 'client', 'pending', :client_id, :company_id, NOW())";

            $userStmt = $this->conn->prepare($userSql);
            $userStmt->execute([
                ':name' => $userName,
                ':email' => $data['email'],
                ':password' => $hashedPassword,
                ':client_id' => $data['client_id'],
                ':company_id' => $data['company_id']
            ]);

            $this->conn->commit();
            return ['status' => true, 'message' => 'Client added successfully'];

        } catch (Exception $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            return ['status' => false, 'message' => 'System Error: ' . $e->getMessage()];
        }
    }

    // ... (Keep existing updateClient, deleteClient, getClients methods here) ...
    /* ================= UPDATE CLIENT ================= */
    public function updateClient($data) {
        $sql = "UPDATE clients SET 
                name = :name,
                contact_person = :person,
                phone = :phone,
                email = :email,
                gst_vat = :vat,
                street = :street,
                city = :city,
                state = :state,
                country = :country,
                postal_code = :zip,
                address = :addr,
                currency_id = :curr
                WHERE client_id = :id AND company_id = :cid";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':name' => $data['name'],
            ':person' => $data['contact_person'],
            ':phone' => $data['phone'],
            ':email' => $data['email'],
            ':vat' => $data['gst_vat'],
            ':street' => $data['street'],
            ':city' => $data['city'],
            ':state' => $data['state'],
            ':country' => $data['country'],
            ':zip' => $data['postal_code'],
            ':addr' => $data['address'],
            ':curr' => $data['currency_id'],
            ':id' => $data['client_id'],
            ':cid' => $data['company_id']
        ]);
    }

    /* ================= DELETE CLIENT ================= */
    public function deleteClient($client_id) {
        // Optional: Check if client has invoices before deleting
        $stmt = $this->conn->prepare("DELETE FROM clients WHERE client_id = :id");
        return $stmt->execute([':id' => $client_id]);
    }

    /* ================= LIST CLIENTS ================= */
    public function getClients($limit, $offset, $search, $from, $to, $company_id) {
        $sql = "SELECT c.*, cur.code AS currency_code 
                FROM clients c
                LEFT JOIN currencies cur ON c.currency_id = cur.currency_id
                WHERE c.company_id = :cid";

        if (!empty($search)) {
            $sql .= " AND (c.name LIKE :search OR c.email LIKE :search OR c.phone LIKE :search)";
        }
        
        $sql .= " ORDER BY c.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':cid', $company_id);
        if (!empty($search)) $stmt->bindValue(':search', "%$search%");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ================= COUNT CLIENTS ================= */
    public function countClients($search, $from, $to, $company_id) {
        $sql = "SELECT COUNT(*) FROM clients WHERE company_id = :cid";
        if (!empty($search)) {
            $sql .= " AND (name LIKE :search OR email LIKE :search)";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':cid', $company_id);
        if (!empty($search)) $stmt->bindValue(':search', "%$search%");
        $stmt->execute();
        return $stmt->fetchColumn();
    }
}
?>