<?php
// includes/header.php - Updated header untuk semua halaman
require_once 'config/database.php';

$schoolInfo = getSchoolInfo();
$currentDate = getCurrentDate();
$currentUser = $_SESSION['username'] ?? 'Guest';
$userRole = $_SESSION['role'] ?? 'guest';
$userName = $_SESSION['name'] ?? $currentUser;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Smart Attendance' ?> – <?= $schoolName ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
