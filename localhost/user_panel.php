<?php
session_start();
// Проверка, авторизован ли пользователь
if (!isset($_SESSION['login'])) {
    header('Location: form_login.php');
    exit();
}

// Проверка роли пользователя
if ($_SESSION['role'] !== 'user') {
    echo "Доступ запрещен";
    exit();
}

// Подключение к базе данных
include 'connect.php';

// Получение данных о заказах пользователя
$user_id = $_SESSION['user_id'];
$query = "
    SELECT o.OrderID, o.TotalAmount, o.Status, o.OrderDate, o.DeliveryDate,
            oi.ProductID, oi.Quantity, oi.Price,
            i.Name as ProductName
    FROM orders o
    JOIN order_items oi ON o.OrderID = oi.OrderID
    JOIN instruments i ON oi.ProductID = i.ProductID
    WHERE o.UserID = ? AND o.OrderType = 'продажа'
    ORDER BY o.OrderDate DESC
";

// Используем подготовленный запрос для безопасности
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Получение личных данных пользователя и адреса
$user_query = "
    SELECT u.*, a.Country, a.City, a.Street, a.House, a.Building, a.Apartment 
    FROM users u
    LEFT JOIN address a ON u.UserID = a.UserID
    WHERE u.UserID = ?
";

$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Пользовательская панель</title>
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
                if (isset($_SESSION['user_id'])) {
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

    <!-- Основной контент -->
    <div class="container">
        <h2>Личный кабинет</h2>
        
        <!-- Блок личных данных -->
        <div class="user-info">
            <h3>Личные данные:</h3>
            <p>Имя: <?php echo htmlspecialchars($user_data['Name']); ?></p>
            <p>Фамилия: <?php echo htmlspecialchars($user_data['Surname']); ?></p>
            <p>Email: <?php echo htmlspecialchars($user_data['Login']); ?></p>
            <p>Телефон: <?php echo htmlspecialchars($user_data['Telephone']); ?></p>
            
            <!-- Адрес пользователя -->
            <h3>Адрес доставки:</h3>
            <p>Страна: <?php echo htmlspecialchars($user_data['Country']); ?></p>
            <p>Город: <?php echo htmlspecialchars($user_data['City']); ?></p>
            <p>Улица: <?php echo htmlspecialchars($user_data['Street']); ?></p>
            <p>Дом: <?php echo htmlspecialchars($user_data['House']); ?></p>
            <?php if (!empty($user_data['Building'])): ?>
                <p>Корпус: <?php echo htmlspecialchars($user_data['Building']); ?></p>
            <?php endif; ?>
            <p>Квартира: <?php echo htmlspecialchars($user_data['Apartment']); ?></p>
        </div>
    </div>
    <div class="container">
        <!-- История заказов -->
        <div class="orders-history">
            <h3>История заказов</h3>
            <?php if (empty($orders)): ?>
                <p>У вас пока нет заказов.</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>№ заказа</th>
                        <th>Дата</th>
                        <th>Товар</th>
                        <th>Количество</th>
                        <th>Стоимость</th>
                        <th>Статус</th>
                    </tr>
                    <?php 
                    $current_order_id = null;
                    foreach ($orders as $order):
                        // Если новый заказ, выводим его детали
                        if ($current_order_id !== $order['OrderID']):
                            $current_order_id = $order['OrderID'];
                    ?>
                        <tr class="order-header">
                            <td><?php echo htmlspecialchars($order['OrderID']); ?></td>
                            <td><?php echo date('d.m.Y', strtotime($order['OrderDate'])); ?></td>
                            <td colspan="2"></td>
                            <td><?php echo htmlspecialchars($order['TotalAmount']); ?> ₽</td>
                            <td><?php echo htmlspecialchars($order['Status']); ?></td>
                        </tr>
                    <?php endif; ?>
                        <!-- Детали заказа -->
                        <tr class="order-details">
                            <td colspan="2"></td>
                            <td><?php echo htmlspecialchars($order['ProductName']); ?></td>
                            <td><?php echo htmlspecialchars($order['Quantity']); ?></td>
                            <td><?php echo htmlspecialchars($order['Price'] * $order['Quantity']); ?> ₽</td>
                            <td>
                                <?php if ($order['Status'] === 'ожидает подтверждения'): ?>
                                    <form action="cancel_order.php" method="POST">
                                        <input type="hidden" name="order_id" value="<?php echo $order['OrderID']; ?>">
                                        <button type="submit" class="cancel-button">Отменить</button>
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