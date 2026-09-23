<?php
$host = 'localhost';
$dbname = 'doctors_db'; // اسم قاعدة البيانات التي أنشأتها
$username = 'root';     // اسم المستخدم الافتراضي في XAMPP
$password = '';         // كلمة السر الافتراضية تكون فارغة

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}
?>