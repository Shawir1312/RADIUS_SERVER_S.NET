<?php
require_once __DIR__ . '/includes/config.php';
doLogout();
header('Location: login.php');
exit;
