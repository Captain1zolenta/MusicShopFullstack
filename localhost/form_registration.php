<!DOCTYPE html>
<>
<head>
    <title>Регистрация</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <?php
    session_start();
    
    ?>
    <!-- Вывод сообщения о том, что логин занят -->
    <p class="prompt"><?php echo ($_SESSION['message'] . '<br>'); ?></p>
    <script>
        function check() {
            // Функция сравнивает содержание введенных паролей
            let a = document.getElementById("password").value;
            let b = document.getElementById("password1").value;
            if (a != b) {
                $('#password1').focus();
                let string = "Пароли не совпадают";
                ddd.innerHTML = string;
            } else {
                let string = " ";
                ddd.innerHTML = string;
            }
        }

        function pass() {
            // Функция удаляет из первого поля с паролем служебные символы и устанавливает ограничение на минимальное количество символов (равное 7)
            let passw = document.getElementById("password").value;
            passw = passw.replace(/[<>/.//,/$]/g, '');
            document.getElementById("password").value = passw;
            let col = passw.length;
            if (col < 7) {
                document.getElementById("password").value = '';
                let string = "Количество символов в пароле должно быть больше 6";
                dd.innerHTML = string;
            } else {
                let string = " ";
                dd.innerHTML = string;
            }
        }

        function nam(a) {
            // Функция удаляет из полей Фамилия и Имя служебные символы и цифры и делает первую букву введенного слова прописной (большой)
            let na = document.getElementById(a).value;
            na = na.replace(/[<>\.\/,\$0-9\s]/g, '');
            na = na.toLowerCase();
            na = FirstLetter(na);
            document.getElementById(a).value = na;
        }

        function FirstLetter(str) {
            // Функция делает первую букву (в текстовой переменной str) прописной (большой)
            return str[0].toUpperCase() + str.substring(1);
        }

        $(function() {
            // Код jQuery, устанавливающий маску для ввода телефона элементу input
            $('#Telephone').mask('+7(999) 999-9999');
        });
    </script>
</head>
<
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
        <!-- Форма регистрации -->
        <form action="registration.php" method="POST">
        <div>
            <!-- Поле для ввода логина (E-mail) -->
                <label for="email">Логин (E-mail):</label><br>
                <input type="email" id="email" name="login" size="20" maxlength="30" required>
            </div>
            <!-- Поле для ввода пароля -->
            <div>
                <label for="password">Пароль:</label><br>
                <input type="password" id="password" name="password" size="20" maxlength="15" value="<?php echo $_SESSION['password']; ?>" required onchange="pass()">
                <span id="dd" class="prompt"></span>
            </div>
            <!-- Поле для подтверждения пароля -->
            <div>
                <label for="confirmPassword">Подтвердите пароль:</label><br>
                <input type="password" id="confirmPassword" name="confirmPassword" size="20" maxlength="15" value="<?php echo $_SESSION['password']; ?>" required onchange="check()">
                <span id="ddd" class="prompt"></span>
            </div>
            <!-- Поле для ввода фамилии -->
            <div>
                <label for="surname">Фамилия:</label><br>
                <input type="text" id="surname" name="surname" size="20" maxlength="15" value="<?php echo $_SESSION['surname']; ?>" required onchange="nam('surname')">
            </div>
            <!-- Поле для ввода имени -->
            <div>
                <label for="name">Имя:</label><br>
                <input type="text" id="name" name="name" size="20" maxlength="15" value="<?php echo $_SESSION['name']; ?>" required onchange="nam('name')">
            </div>
            <!-- Поле для ввода даты рождения -->
            <div>
                <label for="birthday">Дата рождения:</label><br>
                <input type="date" id="birthday" name="birthday" value="<?php echo $_SESSION['birthday']; ?>" required>
            </div>
            <!-- Поле для ввода телефона -->
            <div>
                <label for="telephone">Телефон:</label><br>
                <input type="text" id="telephone" name="telephone" size="20" maxlength="16" value="<?php echo $_SESSION['telephone']; ?>">
            </div>
            <!-- Поле для страны -->
            <div>
                <label>Страна:<br></label>
                <input name="country" id="country" type="text" size="20" maxlength="50" value="<?php echo $_SESSION['country']; ?>" required onchange="nam('country')">
            </div>
            <!-- Поле для города -->
            <div>
                <label>Город:<br></label>
                <input name="city" id="city" type="text" size="20" maxlength="50" value="<?php echo $_SESSION['city']; ?>" required onchange="nam('city')">
            </div>
            <!-- Поле для улицы -->
            <div>
                <label>Улица:<br></label>
                <input name="street" id="street" type="text" size="20" maxlength="100" value="<?php echo $_SESSION['street']; ?>" required>
            </div>
            <!-- Поле для номера дома -->
            <div>
                <label>Номер дома:<br></label>
                <input name="house" id="house" type="text" size="10" maxlength="10" value="<?php echo $_SESSION['house']; ?>" required>
            </div>
            <!-- Поле для корпуса -->
            <div>
                <label>Корпус:<br></label>
                <input name="building" id="building" type="text" size="10" maxlength="10" value="<?php echo $_SESSION['building']; ?>">
            </div>
            <!-- Поле для квартиры -->
            <div>
                <label>Квартира:<br></label>
                <input name="apartment" id="apartment" type="text" size="10" maxlength="10" value="<?php echo $_SESSION['apartment']; ?>">
            </div>
            <!-- Кнопка отправки формы -->
            <button type="submit" name="submit">Зарегистрироваться</button>
        </form>
    </div>
    </main>
</body>
</html>

<?php
$conn->close();
?>