<?php
session_start();

// Проверка, авторизован ли пользователь
if (!isset($_SESSION['login'])) {
    header('Location: form_login.php');
    exit();
}

// Проверка роли пользователя
if ($_SESSION['role'] !== 'owner') {
    echo "Доступ запрещен";
    exit();
}

// Подключение к базе данных
include 'connect.php';

// Получение личных данных владельца
$owner_query = "SELECT * FROM users WHERE Login = ?";
$stmt = $conn->prepare($owner_query);
$stmt->bind_param("s", $_SESSION['login']);
$stmt->execute();
$owner_data = $stmt->get_result()->fetch_assoc();

// Обработка запроса на получение прибыли
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['get_profit'])) {
    $start_date = $_POST['start_date'] . ' 00:00:00';
    $end_date = $_POST['end_date'] . ' 23:59:59';

    // Расчет прибыли с учетом типа операции и приоритета закупочной цены
    $profit_query = "
        WITH LastPurchasePrice AS (
            SELECT 
                oi.ProductID,
                oi.Price as purchase_price,
                o.OrderDate
            FROM order_items oi
            JOIN orders o ON oi.OrderID = o.OrderID
            WHERE o.OrderType = 'закупка'
            AND o.OrderDate <= ?
            AND o.OrderDate >= ?
        ),
        SalesPrices AS (
            SELECT 
                oi.ProductID,
                oi.Price as sale_price,
                o.OrderDate
            FROM order_items oi
            JOIN orders o ON oi.OrderID = o.OrderID
            WHERE o.OrderType = 'продажа'
            AND o.OrderDate BETWEEN ? AND ?
        )
        SELECT 
            COALESCE(
                SUM(
                    CASE 
                        WHEN lpp.purchase_price IS NOT NULL THEN sp.sale_price - lpp.purchase_price
                        ELSE sp.sale_price * 0.2
                    END
                ), 0
            ) as total_profit
        FROM SalesPrices sp
        LEFT JOIN LastPurchasePrice lpp ON sp.ProductID = lpp.ProductID
        WHERE sp.OrderDate >= lpp.OrderDate OR lpp.OrderDate IS NULL";

    $stmt = $conn->prepare($profit_query);
    $stmt->bind_param("ssss", $end_date, $start_date, $start_date, $end_date);
    $stmt->execute();
    $profit_result = $stmt->get_result();
    $profit_data = $profit_result->fetch_assoc();
    $total_profit = $profit_data['total_profit'] ?? 0;
}

// Обработка запроса на получение рейтинга товаров
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['get_rating'])) {
    $start_date = $_POST['start_date'] . ' 00:00:00';
    $end_date = $_POST['end_date'] . ' 23:59:59';

    $rating_query = "
        WITH LastPurchasePrice AS (
            SELECT 
                oi.ProductID,
                oi.Price as purchase_price,
                o.OrderDate
            FROM order_items oi
            JOIN orders o ON oi.OrderID = o.OrderID
            WHERE o.OrderType = 'закупка'
            AND o.OrderDate <= ?
        )
        SELECT 
            p.ProductID, 
            p.Name,
            COUNT(DISTINCT CASE WHEN o.OrderType = 'продажа' THEN o.OrderID END) as sales_count,
            SUM(
                CASE 
                    WHEN o.OrderType = 'продажа' AND lpp.purchase_price IS NOT NULL 
                    THEN oi.Price - lpp.purchase_price
                    WHEN o.OrderType = 'продажа' 
                    THEN oi.Price * 0.2
                    ELSE 0
                END
            ) AS total_profit
        FROM order_items oi
        JOIN orders o ON oi.OrderID = o.OrderID
        JOIN instruments p ON oi.ProductID = p.ProductID
        LEFT JOIN LastPurchasePrice lpp ON oi.ProductID = lpp.ProductID 
            AND o.OrderDate >= lpp.OrderDate
        WHERE o.OrderDate BETWEEN ? AND ?
        GROUP BY p.ProductID, p.Name
        ORDER BY total_profit DESC
        LIMIT 3";

    $stmt = $conn->prepare($rating_query);
    $stmt->bind_param("sss", $end_date, $start_date, $end_date);
    $stmt->execute();
    $rating_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет Владельца</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="index.php">Главная</a></li>
                <li><a href="#">О нас</a></li>
                <li><a href="#">Контакты</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="<?php echo $_SESSION['role'] ?>_panel.php">Личный кабинет</a></li>
                    <li><a href="logout.php">Выход</a></li>
                <?php else: ?>
                    <li><a href="form_login.php">Вход</a></li>
                    <li><a href="form_registration.php">Регистрация</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <div class="container">
        <h1>Личный кабинет Владельца</h1>
        <div class="owner-info">
            <h2>Личные данные владельца</h2>
            <p>Имя: <?php echo htmlspecialchars($owner_data['Name']); ?></p>
            <p>Email: <?php echo htmlspecialchars($owner_data['Login']); ?></p>
        </div>

        <!-- Получение информации о прибыли -->
        <h2>Получение информации о прибыли в интервале</h2>
        <form action="" method="post">
            <label for="start_date">Дата начала:</label>
            <input type="date" id="start_date" name="start_date" required><br>
            <label for="end_date">Дата окончания:</label>
            <input type="date" id="end_date" name="end_date" required><br>
            <button type="submit" name="get_profit">Получить прибыль</button>
        </form>
        <?php if (isset($total_profit)): ?>
            <p>Общая прибыль за выбранный период: <?php echo number_format($total_profit, 2); ?> руб.</p>
        <?php endif; ?>

        <!-- Получение информации о рейтинге товаров -->
        <h2>Рейтинг товаров по прибыли</h2>
        <form action="" method="post">
            <label for="start_date">Дата начала:</label>
            <input type="date" id="start_date" name="start_date" required><br>
            <label for="end_date">Дата окончания:</label>
            <input type="date" id="end_date" name="end_date" required><br>
            <button type="submit" name="get_rating">Получить рейтинг</button>
        </form>
        <?php if (isset($rating_data)): ?>
            <h3>Топ 3 товара по прибыли:</h3>
            <ul>
                <?php foreach ($rating_data as $item): ?>
                    <li>
                        <?php echo htmlspecialchars($item['Name']); ?> - 
                        Прибыль: <?php echo number_format($item['total_profit'], 2); ?> руб.
                        (Продаж: <?php echo $item['sales_count']; ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form action="logout.php" method="post">
            <button type="submit">Выйти</button>
        </form>
    </div>

    <footer>
        <p>&copy; 2024 Музыкальный рай</p>
    </footer>
</body>
</html>

<?php
$conn->close();
?>