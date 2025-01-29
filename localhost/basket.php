<?php
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

session_start();
include 'connect.php';

try {
    // Проверка авторизации пользователя
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Необходима авторизация');
    }

    $user_id = $_SESSION['user_id'];
    $action = $_POST['action'] ?? '';

    // Обработка различных действий с корзиной
    switch($action) {
        case 'add':
            // Добавление товара в корзину
            handleAddToBasket($conn, $user_id);
            break;
            
        case 'update':
            // Обновление количества товара
            handleUpdateQuantity($conn, $user_id);
            break;
            
        case 'remove':
            // Удаление товара из корзины
            handleRemoveFromBasket($conn, $user_id);
            break;
            
        default:
            throw new Exception('Неизвестное действие');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

$conn->close();

// Функция обработки добавления товара
function handleAddToBasket($conn, $user_id) {
    $product_id = $_POST['product_id'] ?? null;
    $quantity = $_POST['quantity'] ?? 1;
    
    if (!$product_id) {
        throw new Exception('Неверные параметры');
    }

    $conn->begin_transaction();
    
    try {
        // Проверяем наличие товара и получаем его цену
        $product_query = "SELECT 
            QuantityStock,
            (
                SELECT 
                    COALESCE(
                        (
                            SELECT ROUND(oi2.Price * 1.2, 2)
                            FROM order_items oi2 
                            JOIN orders o2 ON oi2.OrderID = o2.OrderID 
                            WHERE oi2.ProductID = i.ProductID 
                            AND o2.OrderType = 'закупка'
                            ORDER BY o2.OrderDate DESC 
                            LIMIT 1
                        ),
                        (
                            SELECT oi3.Price
                            FROM order_items oi3 
                            JOIN orders o3 ON oi3.OrderID = o3.OrderID 
                            WHERE oi3.ProductID = i.ProductID 
                            AND o3.OrderType = 'продажа'
                            ORDER BY o3.OrderDate DESC 
                            LIMIT 1
                        ),
                        0
                    )
            ) as Price
            FROM instruments i 
            WHERE i.ProductID = ?";
            
        $product_stmt = $conn->prepare($product_query);
        $product_stmt->bind_param("i", $product_id);
        $product_stmt->execute();
        $product_result = $product_stmt->get_result();
        $product_data = $product_result->fetch_assoc();

        if (!$product_data) {
            throw new Exception('Товар не найден');
        }

        if ($product_data['Price'] <= 0) {
            throw new Exception('Цена товара не установлена');
        }

        if ($quantity > $product_data['QuantityStock']) {
            throw new Exception('Недостаточно товара на складе');
        }

        // Проверяем наличие активного заказа
        $order_query = "SELECT OrderID FROM orders 
                       WHERE UserId = ? AND Status = 'ожидает подтверждения'";
        $order_stmt = $conn->prepare($order_query);
        $order_stmt->bind_param("i", $user_id);
        $order_stmt->execute();
        $order_result = $order_stmt->get_result();
        
        // Если заказа нет, создаем новый
        if ($order_result->num_rows === 0) {
            $create_order = "INSERT INTO orders (UserId, OrderDate, Status, OrderType) 
                           VALUES (?, NOW(), 'ожидает подтверждения', 'продажа')";
            $create_stmt = $conn->prepare($create_order);
            $create_stmt->bind_param("i", $user_id);
            $create_stmt->execute();
            $order_id = $conn->insert_id;
        } else {
            $order_data = $order_result->fetch_assoc();
            $order_id = $order_data['OrderID'];
        }

        // Проверяем, есть ли уже товар в корзине
        $check_query = "SELECT Quantity FROM order_items 
                       WHERE OrderID = ? AND ProductID = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ii", $order_id, $product_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            // Обновляем количество существующего товара
            $current_quantity = $check_result->fetch_assoc()['Quantity'];
            $new_quantity = $current_quantity + $quantity;
            
            if ($new_quantity > $product_data['QuantityStock']) {
                throw new Exception('Недостаточно товара на складе');
            }

            $update_query = "UPDATE order_items 
                           SET Quantity = ? 
                           WHERE OrderID = ? AND ProductID = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("iii", $new_quantity, $order_id, $product_id);
            $update_stmt->execute();
        } else {
            // Добавляем новый товар
            $insert_query = "INSERT INTO order_items (OrderID, ProductID, Quantity, Price) 
                           VALUES (?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("iiid", $order_id, $product_id, $quantity, $product_data['Price']);
            $insert_stmt->execute();
        }

        $conn->commit();
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

// Функция обновления количества
function handleUpdateQuantity($conn, $user_id) {
    $product_id = $_POST['product_id'] ?? null;
    $quantity = $_POST['quantity'] ?? null;
    
    if (!$product_id || !$quantity) {
        throw new Exception('Неверные параметры');
    }

    $conn->begin_transaction();
    
    try {
        // Проверяем наличие товара на складе
        $check_stock = "SELECT QuantityStock FROM instruments WHERE ProductID = ?";
        $stock_stmt = $conn->prepare($check_stock);
        $stock_stmt->bind_param("i", $product_id);
        $stock_stmt->execute();
        $stock_result = $stock_stmt->get_result();
        $stock_data = $stock_result->fetch_assoc();

        if ($quantity > $stock_data['QuantityStock']) {
            throw new Exception('Недостаточно товара на складе');
        }

        // Обновляем количество в корзине
        $update_query = "UPDATE order_items oi
                        JOIN orders o ON oi.OrderID = o.OrderID
                        SET oi.Quantity = ?
                        WHERE o.UserId = ? 
                        AND o.Status = 'ожидает подтверждения'
                        AND oi.ProductID = ?";
        
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("iii", $quantity, $user_id, $product_id);
        $update_stmt->execute();

        $conn->commit();
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

// Функция удаления товара из корзины
function handleRemoveFromBasket($conn, $user_id) {
    $product_id = $_POST['product_id'] ?? null;
    
    if (!$product_id) {
        throw new Exception('Неверные параметры');
    }

    $conn->begin_transaction();
    
    try {
        $delete_query = "DELETE oi FROM order_items oi
                        JOIN orders o ON oi.OrderID = o.OrderID
                        WHERE o.UserId = ? 
                        AND o.Status = 'ожидает подтверждения'
                        AND oi.ProductID = ?";
        
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("ii", $user_id, $product_id);
        $delete_stmt->execute();

        $conn->commit();
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}
?>