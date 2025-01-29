<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: form_login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Создание нового заказа
$query = "INSERT INTO orders (user_id, order_date) VALUES ($user_id, NOW())";
$conn->query($query);
$order_id = $conn->insert_id;

// Обновление записей в order_items для текущего заказа
$query = "UPDATE order_items SET order_id = $order_id WHERE user_id = $user_id AND order_id IS NULL";
$conn->query($query);

header('Location: user_panel.php');
exit();
?>