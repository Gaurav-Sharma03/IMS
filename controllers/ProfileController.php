<?php
require_once __DIR__ . "/../config/database.php";

class ProfileController {
    
    private $db;
    private $user_id;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "views/auth/login.php");
            exit;
        }

        $this->db = (new Database())->connect();
        $this->user_id = $_SESSION['user_id'];
    }

    /* ================= GET PROFILE DATA ================= */
    public function getProfile() {
        // Fetch basic user data
        $stmt = $this->db->prepare("SELECT id, name, email, phone, role, avatar, created_at FROM users WHERE id = ?");
        $stmt->execute([$this->user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // If user is a CLIENT, fetch extra details (Address, Company Name, etc.)
        if ($user['role'] === 'client') {
            $stmtClient = $this->db->prepare("SELECT * FROM clients WHERE email = ?"); // Assuming email link or user_id link
            $stmtClient->execute([$user['email']]);
            $clientDetails = $stmtClient->fetch(PDO::FETCH_ASSOC);
            if ($clientDetails) {
                $user = array_merge($user, $clientDetails); // Combine arrays
            }
        }

        return $user;
    }

    /* ================= UPDATE PROFILE ================= */
    public function updateProfile() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'update_info') {
                $this->updateInfo();
            } elseif ($action === 'change_password') {
                $this->changePassword();
            }
        }
    }

    private function updateInfo() {
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        
        // Handle Avatar Upload
        $avatarPath = $_POST['current_avatar']; // Default to existing
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png'];
            $filename = $_FILES['avatar']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $newName = "avatar_" . $this->user_id . "_" . time() . "." . $ext;
                $target = __DIR__ . "/../assets/uploads/avatars/" . $newName;
                
                // Create dir if not exists
                if (!is_dir(dirname($target))) mkdir(dirname($target), 0777, true);

                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
                    $avatarPath = $newName;
                    // Optional: Delete old avatar here to save space
                }
            }
        }

        // Update Users Table
        $stmt = $this->db->prepare("UPDATE users SET name = ?, phone = ?, avatar = ? WHERE id = ?");
        $updated = $stmt->execute([$name, $phone, $avatarPath, $this->user_id]);

        // Update Session Name immediately
        if ($updated) {
            $_SESSION['user_name'] = $name;
            
            // If Client, update address in clients table too
            if ($_SESSION['role'] === 'client' && isset($_POST['address'])) {
                $stmtC = $this->db->prepare("UPDATE clients SET address = ?, city = ?, state = ?, postal_code = ? WHERE email = (SELECT email FROM users WHERE id = ?)");
                $stmtC->execute([
                    $_POST['address'], $_POST['city'], $_POST['state'], $_POST['postal_code'], $this->user_id
                ]);
            }

            header("Location: " . BASE_URL . "views/profile/edit.php?success=info_updated");
            exit;
        }
    }

    private function changePassword() {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        if ($new !== $confirm) {
            header("Location: " . BASE_URL . "views/profile/edit.php?error=mismatch");
            exit;
        }

        // Verify Old Password
        $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$this->user_id]);
        $user = $stmt->fetch();

        if (password_verify($current, $user['password'])) {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $upd = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $upd->execute([$newHash, $this->user_id]);
            
            header("Location: " . BASE_URL . "views/profile/edit.php?success=password_changed");
        } else {
            header("Location: " . BASE_URL . "views/profile/edit.php?error=wrong_password");
        }
        exit;
    }
}
?>