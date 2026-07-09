<?php
require_once __DIR__ . '/../config.php';
unset($_SESSION['customer_id'], $_SESSION['pending_customer_id']);
header('Location: ' . APP_URL . '/customer/login.php');
exit;
