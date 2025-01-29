<?php
session_start();

// Проверка авторизации
if (!isset($_SESSION['login'])) {
    header('Location: form_login.php');
    exit();
}

// Проверка роли
if ($_SESSION['role'] !== 'seller') {
    echo "Доступ запрещен";
    exit();
}


// Подключение к БД
include 'connect.php';

try {
    // Получение списка всех товаров для выбора
    // Используем правильные названия полей из таблицы instruments
    $sql = "SELECT ProductID, Name, Description, QuantityStock FROM instruments ORDER BY Name";
    $result = $conn->query($sql);

    // Обработка удаления товара
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['product_id'])) {
        $product_id = $_POST['product_id'];
        
        // Проверяем, есть ли товар в заказах
        $check_orders = "SELECT COUNT(*) as count FROM order_items WHERE ProductID = ?";
        $stmt = $conn->prepare($check_orders);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $check_result = $stmt->get_result();
        $order_count = $check_result->fetch_assoc()['count'];
        
        if ($order_count > 0) {
            // Если товар есть в заказах, выводим предупреждение
            echo "<script>alert('Невозможно удалить товар, так как он присутствует в заказах');</script>";
        } else {
            // Если товар не используется в заказах, удаляем его
            $delete_sql = "DELETE FROM instruments WHERE ProductID = ?";
            $stmt = $conn->prepare($delete_sql);
            $stmt->bind_param("i", $product_id);
            
            if ($stmt->execute()) {
                // Успешное удаление
                echo "<script>alert('Товар успешно удален'); window.location.href = 'delete_product.php';</script>";
            } else {
                // Ошибка при удалении
                echo "<script>alert('Ошибка при удалении товара: " . $stmt->error . "');</script>";
            }
        }
    }
} catch (Exception $e) {
    // Обработка ошибок
    echo "Произошла ошибка: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Удаление товара</title>
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
        <h2>Удаление товара</h2>
        
        <?php if ($result && $result->num_rows > 0): ?>
            <!-- Таблица со списком товаров -->
            <table>
                <tr>
                    <th>Название</th>
                    <th>Описание</th>
                    <th>Количество на складе</th>
                    <th>Действие</th>
                </tr>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['Name']); ?></td>
                        <td><?php echo htmlspecialchars($row['Description']); ?></td>
                        <td><?php echo htmlspecialchars($row['QuantityStock']); ?></td>
                        <td>
                            <!-- Форма для удаления товара -->
                            <form method="post" action="delete_product.php" 
                                  onsubmit="return confirm('Вы уверены, что хотите удалить этот товар?');">
                                <input type="hidden" name="product_id" value="<?php echo $row['ProductID']; ?>">
                                <button type="submit" class="cancel-button">Удалить</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>Нет доступных товаров для удаления.</p>
        <?php endif; ?>
        
        <!-- Кнопка возврата -->
        <div class="button-group">
            <a href="seller_panel.php" class="button">Вернуться в панель продавца</a>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 Музыкальный рай</p>
    </footer>
</body>
</html>

<?php
$conn->close();
?>
