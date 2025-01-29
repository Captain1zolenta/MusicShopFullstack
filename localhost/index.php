<?php
session_start();
// Начало сессии для отслеживания состояния пользователя
include 'connect.php';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная страница</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <script src="scripts.js"></script>
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

    <!-- Основной блок-->
    <main>
        <div class="container">
            <h2>Добро пожаловать, <?php echo isset($_SESSION['login']) ? htmlspecialchars($_SESSION['login']) : 'Гость'; ?>!</h2>
        </div>

        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'user'): ?>
            <!-- Блок корзины -->
            <section class="basket-section">
                <div class="container">
                    <h2>Ваша корзина</h2>
                    <div id="basket-list"></div>
                </div>
            </section>

            <!-- Блок товаров -->
            <section class="products-section">
                <div class="container">
                    <h2>Наши музыкальные инструменты</h2>
                    <div id="product-list" class="products-container"></div>
                </div>
</section>
        <?php endif; ?>
    </main>
    
    <footer>
        <p>&copy; 2024 Музыкальный рай</p>
    </footer>
</body>
</html>

<?php
$conn->close();
?>