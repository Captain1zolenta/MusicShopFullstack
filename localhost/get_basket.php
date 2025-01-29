<?php
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

session_start();
include 'connect.php';

try {
    // Проверка авторизации пользователя
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Необходима авторизация');
    }

    $user_id = $_SESSION['user_id'];

    // Получаем данные корзины с дополнительной информацией о товаре
    $query = "SELECT 
                i.ProductID as id,
                i.Name as name, 
                oi.Quantity as kol_vo,
                oi.Price as stoimost,
                i.QuantityStock as stock
            FROM orders o
            JOIN order_items oi ON o.OrderID = oi.OrderID
            JOIN instruments i ON oi.ProductID = i.ProductID 
            WHERE o.UserId = ? 
            AND o.Status = 'ожидает подтверждения'";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception($conn->error);
    }

    // Формируем массив товаров
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'kol_vo' => (int)$row['kol_vo'],
            'stoimost' => (float)$row['stoimost'],
            'stock' => (int)$row['stock']
        ];
    }

    echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>