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

// Обработка добавления товара
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Получаем данные из формы
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $category = $_POST['category'];
    
    // Подготовленный запрос для добавления инструмента
    $sql = "INSERT INTO instruments (Name, Description, Price, QuantityStock, CategoryID, CreatedAt) 
            VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdii", $name, $description, $price, $quantity, $category);
    
    if ($stmt->execute()) {
        echo "<script>alert('Товар успешно добавлен');</script>";
    } else {
        echo "<script>alert('Ошибка при добавлении товара: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}

// Получение списка категорий
$categories_sql = "SELECT CategoryID, Name FROM categories";
$categories_result = $conn->query($categories_sql);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ввод нового товара</title>
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
        <h2>Добавление нового товара</h2>
        <form method="post" action="add_product.php" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Название товара:</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div class="form-group">
                <label for="description">Описание:</label>
                <textarea id="description" name="description" required></textarea>
            </div>

            <div class="form-group">
                <label for="price">Цена:</label>
                <input type="number" id="price" name="price" step="0.01" min="0" required>
            </div>

            <div class="form-group">
                <label for="quantity">Количество на складе:</label>
                <input type="number" id="quantity" name="quantity" min="0" required>
            </div>

            <div class="form-group">
                <label for="category">Категория:</label>
                <select id="category" name="category" required>
                    <?php
                    if ($categories_result->num_rows > 0) {
                        while($category = $categories_result->fetch_assoc()) {
                            echo "<option value='" . $category['CategoryID'] . "'>" . 
                                 htmlspecialchars($category['Name']) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <button type="submit" class="button">Добавить товар</button>
        </form>
        
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