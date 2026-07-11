<?php
require_once __DIR__ . '/../config.php';
requireLogin();

header('Location: ' . APP_URL . '/admin/customers.php');
exit;
