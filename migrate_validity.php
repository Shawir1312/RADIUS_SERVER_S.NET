<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
try {
    \ = db_query(\"SHOW COLUMNS FROM profiles LIKE 'validity_value'\");
    if (\ instanceof mysqli_result && \->num_rows === 0) {
        db_execute(\"ALTER TABLE profiles ADD COLUMN validity_value int(11) DEFAULT 30 AFTER display_name, ADD COLUMN validity_unit enum('minutes','hours','days') DEFAULT 'days' AFTER validity_value\");
        db_execute(\"UPDATE profiles SET validity_value = duration_value, validity_unit = duration_unit\");
        echo 'Migration OK';
    } else {
        echo 'Already exists';
    }
} catch(Exception \) { echo \->getMessage(); }
