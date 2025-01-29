<?php
$password = "12345678"; // Замените на пароль, который вы хотите захешировать
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

echo "Хешированный пароль: " . $hashedPassword;
?>