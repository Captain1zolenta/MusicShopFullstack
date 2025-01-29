<?php
session_start();
include 'connect.php';

// Получение данных из формы входа
$login = trim(strip_tags($_POST['login']));
$password = trim(strip_tags($_POST['password']));

// Проверка логина
$query = "SELECT * FROM `users` WHERE `Login` = '$login'";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    // Если логин найден, проверяем пароль
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['Password'])) {
        // Если пароль верен, сохраняем логин и роль в сессии
        $_SESSION['login'] = $login;
        $_SESSION['role'] = $user['Role'];
        $_SESSION['user_id'] = $user['UserID'];

        // Перенаправление на соответствующую панель в зависимости от роли
        switch ($user['Role']) {
            case 'admin':
                header('Location: admin_panel.php');
                break;
            case 'user':
                header('Location: index.php');
                break;
            case 'seller':
                header('Location: seller_panel.php');
                break;
            case 'owner':
                header('Location: owner_panel.php');
                break;
            default:
                echo "Неизвестная роль";
                exit();
        }
        exit();
    } else {
        // Если пароль неверен, выводим сообщение об ошибке
        echo "Неверный логин или пароль";
    }
} else {
    // Если логин не найден, выводим сообщение об ошибке
    echo "Неверный логин или пароль";
}

// Закрытие соединения
$conn->close();
?>