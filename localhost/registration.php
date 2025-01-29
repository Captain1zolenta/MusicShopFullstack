<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'connect.php';

// Получение данных из формы регистрации
$login = trim(strip_tags($_POST['login']));
$password = trim(strip_tags($_POST['password']));
$surname = trim(strip_tags($_POST['surname']));
$name = trim(strip_tags($_POST['name']));
$birthday = trim(strip_tags($_POST['birthday']));
$telephone = trim(strip_tags($_POST['telephone']));

// Получение данных адреса
$country = trim(strip_tags($_POST['country']));
$city = trim(strip_tags($_POST['city']));
$street = trim(strip_tags($_POST['street']));
$house = trim(strip_tags($_POST['house']));
$building = trim(strip_tags($_POST['building']));
$apartment = trim(strip_tags($_POST['apartment']));

// Хеширование пароля с использованием password_hash
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// Проверка, существует ли уже такой логин
$query = "SELECT Login FROM `users` WHERE `Login` = '$login'";
$check_login = $conn->query($query);
$_SESSION['message'] = '';
if ($check_login->num_rows > 0) {
    // Если логин занят, сохраняем данные в сессии и перенаправляем на форму регистрации
    $_SESSION['message'] = 'Введите другой логин. Введенный логин уже занят.';
    $_SESSION['login'] = $login;
    $_SESSION['password'] = $password;
    $_SESSION['surname'] = $surname;
    $_SESSION['name'] = $name;
    $_SESSION['birthday'] = $birthday;
    $_SESSION['telephone'] = $telephone;
    // Сохранение данных адреса в сессии
    $_SESSION['country'] = $country;
    $_SESSION['city'] = $city;
    $_SESSION['street'] = $street;
    $_SESSION['house'] = $house;
    $_SESSION['building'] = $building;
    $_SESSION['apartment'] = $apartment;
    header('Location: form_registration.php');
    exit();
}

// Начинаем транзакцию
$conn->begin_transaction();

try {
    // Вставка нового пользователя в базу данных
    $query = "INSERT INTO Users (Login, Password, Surname, Name, Birthday, Telephone) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssss", $login, $hashed_password, $surname, $name, $birthday, $telephone);
    $stmt->execute();
    
    // Получаем ID только что созданного пользователя
    $user_id = $conn->insert_id;
    
    // Вставка адреса пользователя
    $query = "INSERT INTO Address (UserID, Country, City, Street, House, Building, Apartment) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("issssss", $user_id, $country, $city, $street, $house, $building, $apartment);
    $stmt->execute();
    
    // Если все запросы выполнены успешно, фиксируем транзакцию
    $conn->commit();
    echo "Регистрация прошла успешно";
} catch (Exception $e) {
    // В случае ошибки откатываем все изменения
    $conn->rollback();
    echo "ОШИБКА: " . $e->getMessage();
}
// Закрытие соединения
$conn->close();
header('Location: login.php');
?>