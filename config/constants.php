<?php
// =========================================================
//  1. GENERAL SETTINGS
// =========================================================
define("APP_NAME", "Invoice Management System");
define("CURRENCY", "$");
date_default_timezone_set("Asia/Kolkata"); // Change to your timezone

// =========================================================
//  2. WEB URL PATHS (For HTML: href, src, links)
// =========================================================
// Change this URL when you host the website
define("BASE_URL", "http://localhost/IMS/");

define("ASSETS_URL",  BASE_URL . "assets/");
define("CSS_URL",     ASSETS_URL . "css/");
define("JS_URL",      ASSETS_URL . "js/");
define("IMAGES_URL",  ASSETS_URL . "images/");
define("UPLOADS_URL", ASSETS_URL . "uploads/");

// =========================================================
//  3. SERVER FILE PATHS (For PHP: require, include)
// =========================================================
// __DIR__ returns the path to the 'config' folder. 
// dirname(__DIR__) goes up one level to the project root.
define("ROOT_PATH", dirname(__DIR__) . "/");

define("CONFIG_PATH",      ROOT_PATH . "config/");
define("CONTROLLERS_PATH", ROOT_PATH . "controllers/");
define("MODELS_PATH",      ROOT_PATH . "models/");
define("VIEWS_PATH",       ROOT_PATH . "views/");
define("LAYOUTS_PATH",     VIEWS_PATH . "layouts/");

// =========================================================
//  4. DEBUG MODE
// =========================================================
// Set to TRUE for development, FALSE for live server
define("DEBUG_MODE", true);

if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
?>