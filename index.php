<?php
$servername = "localhost";
$username = "root";
$password = "";
$error_message = "";
$error_message_1 = "";
$error_message_2 = "";

//Encrypt-Decrypt functions
$encryptKey = md5('45rijkljhgutgyrwesrdtfgdweert');

//Encrypt
function encrypt($string, $secret_key)
{
    global $encryptKey;
    $encrypt_method = "AES-256-CBC";
    // hash
    $key = hash('sha256', $secret_key);

    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
    $iv = substr(hash('sha256', $encryptKey), 0, 16);
    $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
    $output = base64_encode($output);
    return $output;
}

//Decrypt
function decrypt($string, $secret_key)
{
    global $encryptKey;
    $encrypt_method = "AES-256-CBC";
    // hash
    $key = hash('sha256', $secret_key);

    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
    $iv = substr(hash('sha256', $encryptKey), 0, 16);
    $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    return $output;
}

try {
    $connection = new PDO("mysql:host=$servername;dbname=task_db", $username, $password);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (isset($_POST['submit1'])) {
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $error_message_1 = true;
            $error_message = "* неверный формат email";
        } else {
            $secret_key = $_POST['password'];
            $email = encrypt($_POST['email'], $secret_key);
            $phone = encrypt($_POST['phone'], $secret_key);

            $sql = "INSERT INTO task (email, phone) VALUES (:email, :phone)";

            $sth = $connection->prepare($sql);
            $exec = $sth->execute(array(":email" => $email, ":phone" => $phone));
        }
    }

    if (isset($_POST['submit2'])) {
        if (!filter_var($_POST['email_return'], FILTER_VALIDATE_EMAIL)) {
            $error_message_2 = true;
            $error_message = "* неверный формат email";
        } else {
            $secret_key_return = $_POST['password_return'];
            $email_return = encrypt($_POST['email_return'], $secret_key_return);

            $sql = "SELECT * FROM task WHERE email = :email_return";

            $sth = $connection->prepare($sql);
            $sth->execute(array(":email_return" => $email_return));
            $result = $sth->fetch(PDO::FETCH_ASSOC);

            $message_body = decrypt($result['phone'], $secret_key_return);

            if($result == false){
                $error_message_2 = true;
                $error_message = "* неверный пароль или email!";
            } else {
                //Отправка сообщения
                $to = 'example@gmail.com';
                $subject = 'Ваш номер телефона';
                $message = $message_body;
                $headers .= 'From: nmgcom.ru';

                mail($to, $subject, $message, $headers);
            }
        }
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
<!doctype html>
<html>
<head>
    <!--  Styles  -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col-xs-3"></div>
        <div class="col-xs-3">
            <fieldset class="fsStyle">
                <legend class="legendStyle">Добавить номер телефона</legend>
                <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <p class="task-header">Шаг 1. Добавьте номер телефона</p>
                    <div class="form-group">
                        <label for="exampleInputEmail1">Ваш номер телефона: </label>
                        <input type="tel" class="form-control" id="exampleInputEmail1" placeholder="Телефон"
                               name="phone">
                    </div>
                    <div class="form-group <?=$error_message_1 != "" ? "has-error" : ""; ?>">
                        <label for="exampleInputEmail1">Ваш email :</label>
                        <input type="text" class="form-control" id="exampleInputEmail1" placeholder="Email"
                               name="email">
                        <span class="help-block"> <?=$error_message_1 != "" ? $error_message : ""; ?></span>
                    </div>
                    <div class="form-group">
                        <label for="exampleInputPassword1">Password</label>
                        <input type="password" name="password" class="form-control" id="exampleInputPassword1"
                               placeholder="Password">
                    </div>

                    <button type="submit" name="submit1" class="btn btn-default">Отправить</button>
                </form>
            </fieldset>
        </div>
        <div class="col-xs-3">
            <fieldset class="fsStyle">
                <legend class="legendStyle">Получить номер телефона</legend>
                <form method="post">
                    <p class="task-header">Шаг 2. Получите номер вашего телефона</p>
                    <div class="form-group <?=$error_message_2 != "" ? "has-error" : ""; ?>">
                        <label for="exampleInputEmail1">Ваш email :</label>
                        <input type="email" class="form-control" id="exampleInputEmail1" placeholder="Email"
                               name="email_return">
                        <span class="help-block"> <?=$error_message_2 != "" ? $error_message : ""; ?></span>
                    </div>
                    <div class="form-group" <?=$error_message_2 != "" ? "has-error" : ""; ?>>
                        <label for="exampleInputPassword1">Password</label>
                        <input type="password" name="password_return" class="form-control" id="exampleInputPassword1"
                               placeholder="Password">
                    </div>
                    <p>Номер телефона будет отправлен на email</p>

                    <button type="submit" name="submit2" class="btn btn-default">Отправить</button>
                </form>
            </fieldset>
        </div>
        <div class="col-xs-3"></div>
    </div>
</div>
</body>
</html>