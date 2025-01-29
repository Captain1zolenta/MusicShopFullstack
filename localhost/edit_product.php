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

// Получение списка всех товаров
$sql = "SELECT i.ProductID, i.Name, i.Description, i.QuantityStock, c.Name as CategoryName, c.CategoryID 
        FROM instruments i 
        LEFT JOIN categories c ON i.CategoryID = c.CategoryID 
        ORDER BY i.Name";
$result = $conn->query($sql);

// Получение списка категорий для выпадающего списка
$categories_sql = "SELECT CategoryID, Name FROM categories";
$categories_result = $conn->query($categories_sql);
$categories = [];
if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Обработка формы редактирования
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['product_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $quantity = $_POST['quantity'];
    $category = $_POST['category'];

    $update_sql = "UPDATE instruments 
                   SET Name = ?, Description = ?, QuantityStock = ?, CategoryID = ? 
                   WHERE ProductID = ?";
    
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssiii", $name, $description, $quantity, $category, $id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Товар успешно обновлен'); window.location.href = 'edit_product.php';</script>";
    } else {
        echo "<script>alert('Ошибка при обновлении товара: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Коррекция товара</title>
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
    <h2>Редактирование товара</h2>
    
    <?php if ($result && $result->num_rows > 0): ?>
        <table class="edit-table">
            <thead>
                <tr>
                    <th>Название</th>
                    <th>Описание</th>
                    <th>Количество</th>
                    <th>Категория</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <form method="post" action="edit_product.php">
                            <input type="hidden" name="product_id" value="<?php echo $row['ProductID']; ?>">
                            <td>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($row['Name']); ?>" required>
                            </td>
                            <td>
                                <textarea name="description" required><?php echo htmlspecialchars($row['Description']); ?></textarea>
                            </td>
                            <td>
                                <input type="number" name="quantity" value="<?php echo htmlspecialchars($row['QuantityStock']); ?>" min="0" required>
                            </td>
                            <td>
                                <select name="category" required>
                                    <?php foreach($categories as $category): ?>
                                        <option value="<?php echo $category['CategoryID']; ?>" 
                                            <?php echo ($category['CategoryID'] == $row['CategoryID']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['Name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <button type="submit" class="save-button">Сохранить</button>
                            </td>
                        </form>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Нет доступных товаров для редактирования.</p>
    <?php endif; ?>
    
    <div class="edit-buttons">
        <a href="seller_panel.php" class="return-button">Вернуться в панель продавца</a>
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