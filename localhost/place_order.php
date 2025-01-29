<?php
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

session_start();
include 'connect.php';

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Необходима авторизация');
    }

    $user_id = $_SESSION['user_id'];

    // Начинаем транзакцию
    $conn->begin_transaction();

    try {
        // Находим текущий заказ пользователя
        $query = "UPDATE orders 
                 SET Status = 'подтвержден' 
                 WHERE UserId = ? AND Status = 'ожидает подтверждения'";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('Заказ не найден');
        }
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>