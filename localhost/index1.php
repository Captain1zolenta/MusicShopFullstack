
<?php
session_start();
// Начало сессии для отслеживания состояния пользователя
include 'connect.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
    <link rel="stylesheet" href="styles.css">
</head>
    
<body>
    <header>
        <h1>Музыкальный рай</h1>
        <nav>
            <?php if (isset($_SESSION['login'])): ?>
                <p>Привет, <?php echo htmlspecialchars($_SESSION['login']); ?>!</p>  <!-- htmlspecialchars для безопасности -->
                <?php
                $role = $_SESSION['role']; // Получаем роль пользователя из сессии
                $dashboardLink = ''; // Значение по умолчанию

                // Определяем ссылку на личный кабинет в зависимости от роли
                switch ($role) {
                    case 'user':
                        $dashboardLink = 'user_panel.php';
                        break;
                    case 'admin':
                        $dashboardLink = 'admin_panel.php';
                        break;
                    case 'seller':
                        $dashboardLink = 'seller_panel.php';
                        break;
                    case 'owner':
                        $dashboardLink = 'owner_panel.php';
                        break;
                    default:
                        $dashboardLink = '#'; // Если роль неизвестна
                }
                ?>
                <a href="<?php echo htmlspecialchars($dashboardLink); ?>">Личный кабинет</a>
                <?php if ($role === 'user'): ?>
                    <a href="basket.php">Корзина</a>
                <?php endif; ?>
                <a href="logout.php">Выход</a>
            <?php else: ?>
                <a href="form_registration.php">Регистрация</a>
                <a href="form_login.php">Вход</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <section id="product-list">
            <!-- Товары будут загружены здесь с помощью JavaScript -->
        </section>
    </main>

    <footer>
        <p>&copy; 2024 Музыкальный рай</p>
    </footer>

    <script src="scripts.js"></script>
</body>
</html>
