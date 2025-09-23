<?php
// filepath: /admin-dashboard/admin-dashboard/public/index.php

require_once '../src/config/database.php';
require_once '../src/controllers/UserController.php';
require_once '../src/controllers/SubjectController.php';

// Initialize database connection
$conn = getConnection();

// Initialize controllers
$userController = new UserController(); // <-- Remove $conn
$subjectController = new SubjectController($conn);

// Simple routing
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

switch ($page) {
    case 'users':
        $userController->listUsers();
        break;
    case 'subjects':
        $subjectController->listSubjects();
        break;
    case 'login':
        require 'login.php';
        break;
    default:
        require '../src/views/dashboard.php';
        break;
}
?>