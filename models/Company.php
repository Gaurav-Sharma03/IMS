<?php
class Company {
    private $conn;
    private $table = "companies";
    public $errors = [];

    public function __construct($db) {
        $this->conn = $db;
    }

    /* =====================================================
       1. AUTO-GENERATE UNIQUE 8-DIGIT ID
       -----------------------------------------------------
       FIXED: Selects 'company_id' instead of 'id' to prevent
       "Column not found" errors.
    ===================================================== */
    public function generateCompanyID() {
        do {
            $id = random_int(10000000, 99999999);
            
            // FIXED LINE BELOW: Changed 'SELECT id' to 'SELECT company_id'
            $stmt = $this->conn->prepare("SELECT company_id FROM {$this->table} WHERE company_id = :id LIMIT 1");
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            
        } while($stmt->rowCount() > 0);

        return $id;
    }

    /* =====================================================
       2. VALIDATE INPUTS
    ===================================================== */
    public function validate($data) {
        $this->errors = [];

        $required = ['name', 'address', 'city', 'state', 'country', 'created_by'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
            }
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "Invalid email address format.";
        }

        return empty($this->errors);
    }

    /* =====================================================
       3. CREATE COMPANY
    ===================================================== */
    public function create($data) {
        if (!$this->validate($data)) {
            return false;
        }

        try {
            $company_id = $this->generateCompanyID();

            $sql = "INSERT INTO {$this->table}
                    (company_id, name, contact, email, gst_vat, 
                     address, street, city, state, country, postal_code, 
                     logo, created_by, created_at)
                    VALUES
                    (:company_id, :name, :contact, :email, :gst_vat, 
                     :address, :street, :city, :state, :country, :postal_code, 
                     :logo, :created_by, NOW())";

            $stmt = $this->conn->prepare($sql);

            $stmt->bindValue(':company_id', $company_id);
            $stmt->bindValue(':name',       strip_tags($data['name']));
            $stmt->bindValue(':contact',    strip_tags($data['contact'] ?? ''));
            $stmt->bindValue(':email',      filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL));
            $stmt->bindValue(':gst_vat',    strip_tags($data['gst_vat'] ?? ''));
            
            $stmt->bindValue(':address',     strip_tags($data['address']));
            $stmt->bindValue(':street',      strip_tags($data['street'] ?? ''));
            $stmt->bindValue(':city',        strip_tags($data['city']));
            $stmt->bindValue(':state',       strip_tags($data['state']));
            $stmt->bindValue(':country',     strip_tags($data['country']));
            $stmt->bindValue(':postal_code', strip_tags($data['postal_code'] ?? ''));
            
            $stmt->bindValue(':logo',       $data['logo'] ?? null);
            $stmt->bindValue(':created_by', (int)$data['created_by']);

            if ($stmt->execute()) {
                return $company_id;
            }
            
            $this->errors[] = "Database failed to save the company.";
            return false;

        } catch (PDOException $e) {
            $this->errors[] = "System Error: " . $e->getMessage();
            return false;
        }
    }
}
?>