<?php
session_start();
include 'connect.php';

// Проверка, авторизован ли пользователь
if (!isset($_SESSION['login'])) {
    header('Location: form_login.php');
    exit();
}

// Получение ID заказа из POST-запроса
if (isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);

    // Обновление статуса заказа на "отменен"
    $query = "UPDATE Orders SET Status = 'отменен' WHERE OrderID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();

    // Перенаправление обратно на пользовательскую панель
    header('Location: user_panel.php');
    exit();
} else {
    echo "ID заказа не указан.";
}
?>

<?php
$conn->close();
?>