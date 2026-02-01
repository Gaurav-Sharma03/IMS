<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . '/../models/AuditLogger.php'; 

class SuperAdminController
{

    private $db;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE)
            session_start();
        $this->db = (new Database())->connect();
    }

    /* =========================================================
       1. DASHBOARD OVERVIEW (Stats & Charts)
    ========================================================= */
    public function getSystemOverview()
{
    // 1. Subscription-Specific Revenue
    // Using the schema provided: amount (decimal 10,2), payment_type (enum), and status (enum)
    $subscriptionRevenue = $this->db->query("
        SELECT SUM(amount) 
        FROM payments 
        WHERE payment_type = 'subscription' 
        AND status = 'success'
    ")->fetchColumn() ?: 0;

    // 2. Aggregate System Stats
    $stats = [
        'total_companies'      => $this->db->query("SELECT COUNT(*) FROM companies")->fetchColumn(),
        'total_users'          => $this->db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'total_invoices'       => $this->db->query("SELECT COUNT(*) FROM invoices")->fetchColumn(),
        'system_volume'        => $this->db->query("SELECT SUM(grand_total) FROM invoices WHERE status = 'paid'")->fetchColumn() ?: 0,
        'subscription_revenue' => $subscriptionRevenue // Now accurately fetched from the payments table
    ];

    // 3. Recent Companies (Tenants)
    $recent_companies = $this->db->query("
        SELECT c.*, 
        (SELECT COUNT(*) FROM users u WHERE u.company_id = c.company_id) as user_count 
        FROM companies c 
        ORDER BY c.created_at DESC LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 4. Recent System Activity (Users with Company Context)
    $recent_users = $this->db->query("
        SELECT u.name, u.email, u.role, u.created_at, c.name as company_name 
        FROM users u 
        LEFT JOIN companies c ON u.company_id = c.company_id 
        ORDER BY u.created_at DESC LIMIT 6
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 5. Growth Chart Data (Registration Velocity)
    $growthData = $this->db->query("
        SELECT DATE_FORMAT(created_at, '%b %Y') as month, COUNT(*) as count 
        FROM companies 
        GROUP BY month 
        ORDER BY MIN(created_at) DESC LIMIT 6
    ")->fetchAll(PDO::FETCH_ASSOC);

    return compact('stats', 'recent_companies', 'recent_users', 'growthData');
}

    /* =========================================================
       2. COMPANY MANAGEMENT (CRUD & Actions)
    ========================================================= */

    // Get All Companies with Detailed Stats
    public function getAllCompanies($search = '', $status = '')
    {
        $sql = "
            SELECT c.*, 
            (SELECT COUNT(*) FROM users u WHERE u.company_id = c.company_id) as user_count,
            (SELECT COUNT(*) FROM invoices i WHERE i.company_id = c.company_id) as invoice_count,
            (SELECT SUM(grand_total) FROM invoices i WHERE i.company_id = c.company_id AND i.status = 'paid') as total_revenue
            FROM companies c 
            WHERE 1=1
        ";

        $params = [];
        if (!empty($search)) {
            $sql .= " AND (c.name LIKE ? OR c.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if (!empty($status)) {
            $sql .= " AND c.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY c.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Create New Company (Tenant)
    public function createCompany($data)
    {
        $sql = "INSERT INTO companies (name, email, contact, gst_vat, address, city, state, country, postal_code, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone'], // Input name is 'phone' in form, maps to 'contact' in DB
            $data['gst_vat'] ?? null,
            $data['address'] ?? null,
            $data['city'] ?? null,
            $data['state'] ?? null,
            $data['country'] ?? null,
            $data['postal_code'] ?? null
        ]);
        AuditLogger::log('CREATE_COMPANY', "Created new tenant: " . $data['name']);
    }

    // Update Company Status
    public function updateCompanyStatus($id, $status)
    {
        $stmt = $this->db->prepare("UPDATE companies SET status = ? WHERE company_id = ?");
        return $stmt->execute([$status, $id]);
    }



    // ... inside SuperAdminController class ...

    // 1. Fetch All Settings as Key-Value Pair
    public function getGlobalSettings()
    {
        $stmt = $this->db->query("SELECT setting_key, setting_value FROM system_settings");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    // 2. Update Settings
    public function updateGlobalSettings($data, $files)
    {
        foreach ($data as $key => $value) {
            // Skip non-setting fields
            if ($key === 'save_settings')
                continue;

            $stmt = $this->db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute([$key, $value]);
        }

        // Simple File Upload Logic (For Logo)
        if (!empty($files['app_logo']['name'])) {
            $target = __DIR__ . "/../public/uploads/logo.png"; // Save as specific name
            move_uploaded_file($files['app_logo']['tmp_name'], $target);
        }

        return true;
    }



    // ... inside SuperAdminController class ...

    // 1. Fetch Audit Logs with Filters
    public function getAuditLogs($filter_role = '', $search = '')
    {
        $sql = "SELECT * FROM audit_logs WHERE 1=1";
        $params = [];

        // Role Filter
        if (!empty($filter_role)) {
            $sql .= " AND user_role = ?";
            $params[] = $filter_role;
        }

        // Search Logic
        if (!empty($search)) {
            $sql .= " AND (user_name LIKE ? OR action LIKE ? OR details LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY created_at DESC LIMIT 100"; // Show last 100 events

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. Clear Old Logs (Maintenance)
    public function clearLogs()
    {
        // Keeps logs from the last 30 days, deletes older ones
        $this->db->query("DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        return true;
    }

    // 1. Fetch All Users (Global)
    public function getAllUsers($search = '', $role = '', $company_id = '')
    {
        $sql = "
            SELECT u.*, c.name as company_name 
            FROM users u
            LEFT JOIN companies c ON u.company_id = c.company_id
            WHERE 1=1
        ";

        $params = [];

        if (!empty($search)) {
            $sql .= " AND (u.name LIKE ? OR u.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($role)) {
            $sql .= " AND u.role = ?";
            $params[] = $role;
        }

        if (!empty($company_id)) {
            $sql .= " AND u.company_id = ?";
            $params[] = $company_id;
        }

        $sql .= " ORDER BY u.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. Suspend/Activate User
    public function toggleUserStatus($user_id, $status)
    {
        // Status can be 'active' or 'suspended' (ensure column exists in users table)
        // If 'status' column doesn't exist, you might need to add it:
        // ALTER TABLE users ADD COLUMN status ENUM('active', 'suspended') DEFAULT 'active';

        $stmt = $this->db->prepare("UPDATE users SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $user_id]);
    }

    // 3. Delete User (Hard Delete)
    public function deleteUser($user_id)
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$user_id]);
    }
    /* ==========================
           USER MANAGEMENT (CRUD)
        ========================== */

    // 1. Create New User
    public function createUser($data)
    {
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $company_id = !empty($data['company_id']) ? $data['company_id'] : null; // Null for Superadmins

        $sql = "INSERT INTO users (name, email, password, role, company_id, status, created_at) VALUES (?, ?, ?, ?, ?, 'active', NOW())";
        return $this->db->prepare($sql)->execute([
            $data['name'],
            $data['email'],
            $password,
            $data['role'],
            $company_id
        ]);
    }

    // 2. Update Existing User
    public function updateUser($data)
    {
        $fields = "name=?, email=?, role=?, company_id=?";
        $params = [$data['name'], $data['email'], $data['role'], $data['company_id'] ?: null];

        // Only update password if provided
        if (!empty($data['password'])) {
            $fields .= ", password=?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $params[] = $data['user_id'];

        $sql = "UPDATE users SET $fields WHERE id=?";
        return $this->db->prepare($sql)->execute($params);
    }


} // <--- THIS MUST BE THE LAST CLOSING BRACE
?>