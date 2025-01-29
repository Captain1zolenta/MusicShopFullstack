<?php
// Начало сессии для управления данными пользователя
session_start();

// Проверка, авторизован ли пользователь
if (!isset($_SESSION['login'])) {
    // Если пользователь не авторизован, перенаправляем его на страницу входа
    header('Location: form_login.php');
    exit();
}

// Проверка роли пользователя
if ($_SESSION['role'] !== 'seller') {
    // Если роль пользователя не 'seller', выводим сообщение о запрете доступа
    echo "Доступ запрещен";
    exit();
}

// Подключение к базе данных
include 'connect.php';

// Получение личных данных продавца
$seller_id = $_SESSION['user_id'];
$sql = "SELECT u.*, a.Country, a.City, a.Street, a.House, a.Building, a.Apartment 
        FROM users u 
        LEFT JOIN address a ON u.UserID = a.UserID 
        WHERE u.UserID = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $seller_data = $result->fetch_assoc();
} else {
    $seller_data = [
        'Name' => "Неизвестный продавец",
        'Login' => "Неизвестный email"
    ];
}

// Получение заказов для продавца
$orders_sql = "SELECT o.OrderID, o.OrderDate, o.TotalAmount, o.Status,
                      oi.Quantity, oi.Price,
                      i.Name as ProductName,
                      u.Name as CustomerName, u.Login as CustomerEmail
               FROM orders o
               JOIN order_items oi ON o.OrderID = oi.OrderID
               JOIN instruments i ON oi.ProductID = i.ProductID
               JOIN users u ON o.UserID = u.UserID
               WHERE o.OrderType = 'продажа'
               ORDER BY o.OrderDate DESC";

$orders_result = $conn->query($orders_sql);
$orders = [];
if ($orders_result) {
    while ($row = $orders_result->fetch_assoc()) {
        $orders[] = $row;
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Панель продавца</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <!-- Шапка сайта -->
    <header>
        <h1>Музыкальный рай</h1>
        <nav>
            <ul>
                <li><a href="index.php">Главная</a></li>
                <li><a href="#">О нас</a></li>
                <li><a href="#">Контакты</a></li>
                <?php
                // Проверяем авторизацию пользователя и выводим соответствующие пункты меню
                if (isset($_SESSION['user_id'])) {
                    // Определяем ссылку на панель в зависимости от роли
                    $panelLink = match($_SESSION['role']) {
                        'user' => 'user_panel.php',
                        'seller' => 'seller_panel.php',
                        'admin' => 'admin_panel.php',
                        'owner' => 'owner_panel.php',
                        default => '#'
                    };
                    echo "<li><a href=\"{$panelLink}\">Личный кабинет</a></li>";
                    echo '<li><a href="logout.php">Выход</a></li>';
                } else {
                    echo '<li><a href="form_login.php">Вход</a></li>';
                    echo '<li><a href="form_registration.php">Регистрация</a></li>';
                }
                ?>
            </ul>
        </nav>
    </header>
    
    <div class="container">
        <h2>Панель продавца</h2>
        <div class="seller-info">
            <h3>Информация о продавце:</h3>
            <p>Имя: <?php echo htmlspecialchars($seller_data['Name']); ?></p>
            <p>Email: <?php echo htmlspecialchars($seller_data['Login']); ?></p>
            <p>Текущая дата: <?php echo date('d.m.Y'); ?></p>
        </div>

        <!-- Функции продавца -->
        <div class="seller-actions">
            <h3>Управление товарами:</h3>
            <a class="button" href="manage_orders.php">Управление заказами</a>
            <a class="button" href="add_product.php">Добавить новый товар</a>
            <a class="button" href="edit_product.php">Редактировать товар</a>
            <a class="button" href="delete_product.php">Удалить товар</a>
        </div>

        <!-- Список заказов -->
        <div class="orders-list">
            <h3>Текущие заказы:</h3>
            <?php if (empty($orders)): ?>
                <p>Нет активных заказов</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>№ заказа</th>
                        <th>Дата</th>
                        <th>Покупатель</th>
                        <th>Товар</th>
                        <th>Количество</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['OrderID']); ?></td>
                            <td><?php echo date('d.m.Y', strtotime($order['OrderDate'])); ?></td>
                            <td><?php echo htmlspecialchars($order['CustomerName']); ?></td>
                            <td><?php echo htmlspecialchars($order['ProductName']); ?></td>
                            <td><?php echo htmlspecialchars($order['Quantity']); ?></td>
                            <td><?php echo htmlspecialchars($order['TotalAmount']); ?> ₽</td>
                            <td><?php echo htmlspecialchars($order['Status']); ?></td>
                            <td>
                                <?php if ($order['Status'] === 'ожидает подтверждения'): ?>
                                    <form action="update_order_status.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['OrderID']; ?>">
                                        <input type="hidden" name="status" value="подтвержден">
                                        <button type="submit" class="button">Подтвердить</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 Музыкальный рай</p>
    </footer>
</body>
</html>
