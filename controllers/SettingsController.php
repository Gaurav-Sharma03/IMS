<?php
require_once __DIR__ . "/../config/database.php";

class SettingsController {
    
    private $db;
    private $user_id;
    private $company_id;
    private $role;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->db = (new Database())->connect();
        
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "views/auth/login.php");
            exit;
        }

        $this->user_id = $_SESSION['user_id'];
        $this->company_id = $_SESSION['company_id'] ?? 0;
        $this->role = $_SESSION['role'] ?? 'client';
    }

    /* ================= GET SETTINGS ================= */
    public function getSettings() {
        // 1. Get User Profile
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$this->user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Get Organization Info
        $org = [];
        if ($this->role === 'admin' || $this->role === 'superadmin') {
            $stmt = $this->db->prepare("SELECT * FROM companies WHERE company_id = ?");
            $stmt->execute([$this->company_id]);
            $org = $stmt->fetch(PDO::FETCH_ASSOC);
        } elseif ($this->role === 'client') {
            $stmt = $this->db->prepare("SELECT * FROM clients WHERE email = ? LIMIT 1");
            $stmt->execute([$user['email']]); 
            $org = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return ['user' => $user, 'org' => $org];
    }

    /* ================= UPDATE ACTIONS ================= */
    public function updateSettings() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type = $_POST['update_type'] ?? '';

            try {
                if ($type === 'profile') {
                    $this->updateProfile();
                } elseif ($type === 'organization') {
                    $this->updateOrganization();
                } elseif ($type === 'security') {
                    $this->updateSecurity();
                }
                
                header("Location: " . BASE_URL . "views/dashboard/settings.php?success=" . $type);
                exit;

            } catch (Exception $e) {
                header("Location: " . BASE_URL . "views/dashboard/settings.php?error=" . urlencode($e->getMessage()));
                exit;
            }
        }
    }

    // A. Update User Profile
    private function updateProfile() {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $avatar = $_FILES['avatar'] ?? null;

        $avatarSql = "";
        $params = [$name, $email];

        if ($avatar && $avatar['tmp_name']) {
            $fileName = time() . '_' . $avatar['name'];
            $target = __DIR__ . '/../assets/uploads/avatars/' . $fileName;
            if(move_uploaded_file($avatar['tmp_name'], $target)) {
                $avatarSql = ", avatar = ?";
                $params[] = $fileName;
                $_SESSION['avatar'] = $fileName;
            }
        }

        $params[] = $this->user_id;
        $sql = "UPDATE users SET name = ?, email = ? $avatarSql WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $_SESSION['user_name'] = $name;
    }

    // B. Update Company/Client Info (UPDATED WITH NEW FIELDS)
    private function updateOrganization() {
        // Common Fields
        $name = $_POST['org_name'];
        $email = $_POST['org_email'];
        $phone = $_POST['org_phone'];
        $gst_vat = $_POST['gst_vat'];
        
        // Address Fields
        $street = $_POST['street'];
        $city = $_POST['city'];
        $state = $_POST['state'];
        $country = $_POST['country'];
        $postal_code = $_POST['postal_code'];

        // Handle Logo
        $logoSql = "";
        $fileParams = [];
        if (!empty($_FILES['org_logo']['tmp_name'])) {
            $fileName = 'logo_' . time() . '.png';
            $target = __DIR__ . '/../assets/uploads/logos/' . $fileName;
            if(move_uploaded_file($_FILES['org_logo']['tmp_name'], $target)) {
                $logoSql = ", logo = ?";
                $fileParams[] = $fileName;
            }
        }

        if ($this->role === 'admin' || $this->role === 'superadmin') {
            // Update 'companies' table
            $sql = "UPDATE companies SET 
                    name=?, email=?, contact=?, gst_vat=?, 
                    street=?, city=?, state=?, country=?, postal_code=? 
                    $logoSql 
                    WHERE company_id=?";
            
            $params = array_merge(
                [$name, $email, $phone, $gst_vat, $street, $city, $state, $country, $postal_code], 
                $fileParams, 
                [$this->company_id]
            );

        } else {
            // Update 'clients' table
            $contact_person = $_POST['contact_person']; // Specific to clients
            
            $sql = "UPDATE clients SET 
                    name=?, email=?, phone=?, gst_vat=?, contact_person=?, 
                    street=?, city=?, state=?, country=?, postal_code=? 
                    WHERE email = (SELECT email FROM users WHERE id = ?)";
            
            $params = [$name, $email, $phone, $gst_vat, $contact_person, $street, $city, $state, $country, $postal_code, $this->user_id];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    // C. Update Password
    private function updateSecurity() {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        if ($new !== $confirm) throw new Exception("New passwords do not match.");

        $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$this->user_id]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($current, $hash)) throw new Exception("Current password is incorrect.");

        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $upd = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $upd->execute([$newHash, $this->user_id]);
    }
}
?>