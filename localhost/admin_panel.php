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
if ($_SESSION['role'] !== 'admin') {
    // Если роль пользователя не 'admin', выводим сообщение о запрете доступа
    echo "Доступ запрещен";
    exit();
}

// Код для административной панели
echo "Добро пожаловать, администратор!";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Административная панель</title>
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
        <h1>Административная панель</h1>
        <p>Здесь вы можете управлять пользователями и настройками системы.</p>
        <!-- Примеры функций, доступных только администраторам -->
        <a class="button" href="manage_users.php">Управление пользователями</a><br>
        <a class="button" href="system_settings.php">Настройки системы</a><br>
        <a class="button" href="logout.php">Выйти</a>
    </div>

    <footer>
        <p>&copy; 2024 Музыкальный рай</p>
    </footer>
</body>
</html>

<?php
$conn->close();
?>