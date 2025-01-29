<?php
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

include 'connect.php';

try {
    // Получаем товары с правильной логикой формирования цен
    $sql = "SELECT 
                i.ProductID, 
                i.Name, 
                i.Description, 
                i.QuantityStock,
                (
                    SELECT 
                        COALESCE(
                            -- Сначала ищем закупочную цену и умножаем на 1.2
                            (
                                SELECT ROUND(oi2.Price * 1.2, 2)
                                FROM order_items oi2 
                                JOIN orders o2 ON oi2.OrderID = o2.OrderID 
                                WHERE oi2.ProductID = i.ProductID 
                                AND o2.OrderType = 'закупка'
                                ORDER BY o2.OrderDate DESC 
                                LIMIT 1
                            ),
                            -- Если закупочной нет, берем последнюю цену продажи
                            (
                                SELECT oi3.Price
                                FROM order_items oi3 
                                JOIN orders o3 ON oi3.OrderID = o3.OrderID 
                                WHERE oi3.ProductID = i.ProductID 
                                AND o3.OrderType = 'продажа'
                                ORDER BY o3.OrderDate DESC 
                                LIMIT 1
                            ),
                            -- Если нет ни одной цены, ставим 0
                            0
                        )
                ) as Price
            FROM instruments i";
    
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception($conn->error);
    }

    $products = [];
    while($row = $result->fetch_assoc()) {
        // Добавляем товар только если есть цена
        if ($row['Price'] > 0) {
            $row['Price'] = round($row['Price'], 2);
            $products[] = $row;
        }
    }

    echo json_encode($products, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>