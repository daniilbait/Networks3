<?php
$host = 'localhost';
$dbname = 'u82289';
$username = 'u82289';
$password = '7844907';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

$errors = [];
$data = [];

// Получаем данные из POST
$full_name = trim($_POST['full_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$birth_date = $_POST['birth_date'] ?? '';
$gender = $_POST['gender'] ?? '';
$languages = $_POST['languages'] ?? [];
$bio = trim($_POST['bio'] ?? '');
$contract = isset($_POST['contract']) ? 1 : 0;
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}
if (empty($full_name)) {
    $errors[] = "ФИО обязательно для заполнения";
} elseif (!preg_match("/^[а-яА-ЯёЁa-zA-Z\s-]+$/u", $full_name)) {
    $errors[] = "ФИО должно содержать только буквы, пробелы и дефисы";
} elseif (strlen($full_name) > 150) {
    $errors[] = "ФИО не должно превышать 150 символов";
}

if (empty($phone)) {
    $errors[] = "Телефон обязателен для заполнения";
} elseif (!preg_match("/^\+?\d{10,15}$/", preg_replace("/[\s\-\(\)]/", "", $phone))) {
    $errors[] = "Телефон должен содержать от 10 до 15 цифр, может начинаться с + и содержать пробелы, скобки, дефисы";
}


if (empty($email)) {
    $errors[] = "Email обязателен для заполнения";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Некорректный формат email";
}

if (empty($birth_date)) {
    $errors[] = "Дата рождения обязательна";
} elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $birth_date)) {
    $errors[] = "Некорректный формат даты";
}

if (!in_array($gender, ['male', 'female'])) {
    $errors[] = "Некорректное значение пола";
}

$allowed_languages = ['pascal', 'c', 'c++', 'javascript', 'php', 'python', 'java', 'haskel', 'clojure', 'prolog', 'scala'];
if (empty($languages)) {
    $errors[] = "Выберите хотя бы один язык программирования";
} else {
    foreach ($languages as $lang) {
        if (!in_array($lang, $allowed_languages)) {
            $errors[] = "Некорректный язык программирования: $lang";
        }
    }
}

if (empty($bio)) {
    $errors[] = "Биография обязательна для заполнения";
} elseif (!preg_match("/^[а-яА-ЯёЁa-zA-Z0-9\s\.,!?()-]+$/u", $bio)) {
    $errors[] = "Биография содержит недопустимые символы. Разрешены буквы, цифры, пробелы и знаки препинания . , ! ? ( ) -";
}

if (!$contract) {
    $errors[] = "Необходимо подтвердить ознакомление с контрактом";
}
if (!empty($errors)) {
    echo "<h2>Ошибки при заполнении формы:</h2><ul>";
    foreach ($errors as $error) {
        echo "<li style='color:red;'>$error</li>";
    }
    echo "</ul><a href='index.html'>Вернуться к форме</a>";
    exit;
}
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO user_form (full_name, phone, email, birth_date, gender, biography, contract_accepted) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$full_name, $phone, $email, $birth_date, $gender, $bio, $contract]);
    $user_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("INSERT INTO user_languages (user_id, language) VALUES (?, ?)");
    foreach ($languages as $lang) {
        $stmt->execute([$user_id, $lang]);
    }

    $pdo->commit();
    echo "<h2 style='color:green;'>Данные успешно сохранены!</h2>";
    echo "<a href='index.html'>Вернуться к форме</a>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<h2 style='color:red;'>Ошибка при сохранении: " . $e->getMessage() . "</h2>";
}
