<?php
/* =================================================================================
 * AUDIT LOGGER MODEL (Matched to your Table Structure)
 * ================================================================================= */

require_once __DIR__ . '/../config/database.php';

class AuditLogger {

    public static function log($action, $details) {
        
        try {
            $db = (new Database())->connect();

            // 1. Capture Data from Session & Server
            $user_id    = $_SESSION['user_id'] ?? 0; // Default to 0 if not logged in
            $user_name  = $_SESSION['name'] ?? 'System/Guest';
            $user_role  = $_SESSION['role'] ?? 'guest';
            
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

            // 2. Prepare SQL (Matches your specific table columns)
            // Note: We use 'details' column instead of 'description' to match your image
            $sql = "INSERT INTO audit_logs 
                    (user_id, user_name, user_role, action, details, ip_address, user_agent, created_at) 
                    VALUES 
                    (:uid, :uname, :urole, :action, :details, :ip, :agent, NOW())";
            
            $stmt = $db->prepare($sql);

            // 3. Execute
            $stmt->execute([
                ':uid'     => $user_id,
                ':uname'   => $user_name,
                ':urole'   => $user_role,
                ':action'  => $action,
                ':details' => $details,
                ':ip'      => $ip_address,
                ':agent'   => $user_agent
            ]);

        } catch (PDOException $e) {
            // Optional: Write to a text file if DB logging fails
            // error_log("Audit Error: " . $e->getMessage());
        }
    }
}
?>