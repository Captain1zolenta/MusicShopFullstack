<?php
session_start();
// Проверка, вошел ли пользователь в систему и является ли он продавцом
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'seller') {
    header("Location: index.php");
    exit();
}

include 'connect.php'; // Подключаем файл соединения с базой данных

// Обработка добавления нового товара
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $image = $_POST['image'];

    $stmt = $conn->prepare("INSERT INTO Products (Name, Description, Price, Image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssds", $name, $description, $price, $image);

    if ($stmt->execute()) {
        $message = "Новый товар добавлен успешно";
    } else {
        $message = "Ошибка: " . $stmt->error;
    }
    $stmt->close();
}

// Обработка коррекции данных товара
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_product'])) {
    $id = $_POST['edit_id'];
    $name = $_POST['edit_name'];
    $description = $_POST['edit_description'];
    $price = $_POST['edit_price'];
    $image = $_POST['edit_image'];

    $stmt = $conn->prepare("UPDATE Products SET Name=?, Description=?, Price=?, Image=? WHERE ProductID=?");
    $stmt->bind_param("ssdsi", $name, $description, $price, $image, $id);

    if ($stmt->execute()) {
        $message = "Товар обновлен успешно";
    } else {
        $message = "Ошибка: " . $stmt->error;
    }
    $stmt->close();
}

// Обработка удаления товара
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_product'])) {
    $id = $_POST['delete_id'];

    $stmt = $conn->prepare("DELETE FROM Products WHERE ProductID=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $message = "Товар удален успешно";
    } else {
        $message = "Ошибка: " . $stmt->error;
    }
    $stmt->close();
}

$conn->close();
header("Location: seller_panel.php?message=" . urlencode($message));
exit();
?>