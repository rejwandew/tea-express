<?php
if (count(get_included_files()) == ((version_compare(PHP_VERSION, '5.0.0', '>=')) ? 1 : 0)) { die('Restricted Access'); }

// InfinityFree Live MySQL Credentials
define('DB_HOST', 'sql201.infinityfree.com'); 
define('DB_USER', 'if0_41571232');             
define('DB_PASS', 'Oto48yY6y4sckB');     
define('DB_NAME', 'if0_41571232_epiz_xxx_teashop');        
define('DB_PORT', 3306); // Default MySQL port for live server

// Supabase Credentials (Used for both Local and Live)
define('SUPABASE_URL', 'https://dypsilcspuyetfijkuux.supabase.co');
define('SUPABASE_KEY', 'sb_publishable_bhkJPdJ0roBnGwYdR1p3Xw_G33HFcfG');

// ── Gemini API Key (get yours at https://aistudio.google.com/apikey) ──────────
define('GEMINI_API_KEY', 'AQ.Ab8RN6I6wvMUBnl8mW-QFpx-8mcEKT7yJXohtdBt7pSVb6AuEg');


// Updated connection to include DB_PORT as the 5th parameter
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>