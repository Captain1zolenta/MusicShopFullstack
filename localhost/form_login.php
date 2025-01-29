<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
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

    <main>
    <div class="container">
        <h2>Вход в систему</h2>
        <form action="login.php" method="POST">
            <label for="login">Логин(E-mail):</label>
            <input name="login" id="login" type="email" size="20" maxlength="30" required><br><br>
            <label for="password">Пароль:</label>
            <input name="password" id="password" type="password" size="20" maxlength="15" required><br><br>
            <input type="submit" value="Войти">
        </form>
    </div>
    </main>

    <footer>
        <p>&copy; 2024 Музыкальный рай</p>
    </footer>
</body>
</html>

<?php
$conn->close();
?>