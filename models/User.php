<?php
class User {
    private $conn;
    private $table = "users";

    public function __construct($db) {
        $this->conn = $db;
    }

    /* =====================================================
       FIND USER BY EMAIL
       Used for: Login authentication
    ===================================================== */
    public function findByEmail($email) {
        $sql = "SELECT id, name, email, password, role, status, company_id, client_id, staff_id 
                FROM {$this->table} 
                WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* =====================================================
       FIND USER BY RESET TOKEN
       Updated: Now allows 'pending' users (for Staff/Client invites)
    ===================================================== */
    public function findByResetToken($token) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE reset_token = :token 
                AND token_expiry > NOW() 
                AND status IN ('active', 'pending') -- Allow pending users to activate account
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":token", $token);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* =====================================================
       REGISTER USER (Flexible for All Roles)
       Used for: Public Sign Up (User) AND Admin Invites (Staff/Client)
    ===================================================== */
    public function register($data) {
        $sql = "INSERT INTO {$this->table} 
                (name, email, password, role, status, phone, address, company_id, staff_id, client_id, created_at, updated_at) 
                VALUES 
                (:name, :email, :password, :role, :status, :phone, :address, :company_id, :staff_id, :client_id, NOW(), NOW())";

        $stmt = $this->conn->prepare($sql);
        
        // 1. Basic Info
        $stmt->bindValue(":name", $data['name']);
        $stmt->bindValue(":email", $data['email']);
        $stmt->bindValue(":password", $data['password']);
        $stmt->bindValue(":phone", $data['phone'] ?? null);
        $stmt->bindValue(":address", $data['address'] ?? null);

        // 2. Role & Status Logic
        // Public Register: role='user', status='active'
        // Admin Invites: role='staff'/'client', status='pending'
        $stmt->bindValue(":role", $data['role'] ?? 'user'); 
        $stmt->bindValue(":status", $data['status'] ?? 'active'); 

        // 3. Foreign Keys (Important for Multi-tenancy)
        $stmt->bindValue(":company_id", $data['company_id'] ?? null);
        $stmt->bindValue(":staff_id", $data['staff_id'] ?? null);
        $stmt->bindValue(":client_id", $data['client_id'] ?? null);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /* =====================================================
       UPGRADE USER TO ADMIN
       Used when: A 'user' creates a Company
    ===================================================== */
    public function upgradeToAdmin($userId, $companyId) {
        $sql = "UPDATE {$this->table} 
                SET role = 'admin', 
                    company_id = :company_id, 
                    updated_at = NOW() 
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':company_id', $companyId);
        $stmt->bindParam(':id', $userId);
        
        return $stmt->execute();
    }

    /* =====================================================
       HELPER: CREATE COMPANY
       Used when: A 'user' fills out the "Create Company" form
    ===================================================== */
    public function createCompany($companyName, $userId) {
        try {
            $this->conn->beginTransaction();

            // 1. Create Company
            $sqlComp = "INSERT INTO companies (name, created_by, created_at) VALUES (:name, :user, NOW())";
            $stmt = $this->conn->prepare($sqlComp);
            $stmt->execute([':name' => $companyName, ':user' => $userId]);
            $companyId = $this->conn->lastInsertId();

            // 2. Upgrade User to Admin immediately
            $this->upgradeToAdmin($userId, $companyId);

            $this->conn->commit();
            return $companyId;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    /* =====================================================
       GENERATE RESET TOKEN
    ===================================================== */
    public function saveResetToken($email, $token) {
        $sql = "UPDATE {$this->table} 
                SET reset_token = :token, 
                    token_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR),
                    updated_at = NOW()
                WHERE email = :email";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":email", $email);
        return $stmt->execute();
    }

    /* =====================================================
       UPDATE PASSWORD / ACTIVATE ACCOUNT
       Used for: Reset Password AND First Time Login (Set Password)
    ===================================================== */
    public function updatePassword($user_id, $hashedPassword) {
        $sql = "UPDATE {$this->table} 
                SET password = :password, 
                    reset_token = NULL, 
                    token_expiry = NULL,
                    status = 'active', -- Always activate account on password set
                    updated_at = NOW()
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":password", $hashedPassword);
        $stmt->bindParam(":id", $user_id);
        return $stmt->execute();
    }

    /* =====================================================
       UPDATE USER DETAILS
       Used for: Editing Staff/Client info
    ===================================================== */
    public function updateUser($id, $data) {
        // Optional: Check if email is taken by another user
        // $sql = "SELECT id FROM {$this->table} WHERE email = :email AND id != :id"; ...

        $sql = "UPDATE {$this->table} SET 
                name = :name, 
                email = :email, 
                phone = :phone, 
                status = :status,
                updated_at = NOW() 
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        
        return $stmt->execute([
            ':name'   => $data['name'],
            ':email'  => $data['email'],
            ':phone'  => $data['phone'],
            ':status' => $data['status'],
            ':id'     => $id
        ]);
    }

    /* =====================================================
       DELETE USER
       Security: Ensure we only delete users from the same company
    ===================================================== */
    public function deleteUser($id, $company_id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id AND company_id = :company_id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $id, ':company_id' => $company_id]);
    }



    /* =====================================================
       HELPER: CHECK EMAIL EXISTS
    ===================================================== */
    public function emailExists($email) {
        $sql = "SELECT id FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
    
}
?>