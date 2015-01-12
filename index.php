<?php
    error_reporting(E_ALL);
    session_start();
    include 'class/MyDB.class.php';
    include 'class/login.php';
    $db_host = 'localhost';
    $db_database = 'login';
    $db_login = 'login_user';
    $db_password = 'y2QzuTy3YwmG35y5';
    $db = new MyDB($db_host, $db_login, $db_password, $db_database);
    if ($db->Connect() == false) {
        echo('brak połączenia z bazą danych');
    }
    $db->Query('SET NAMES UTF8') or die($db->GetLastError());
    $login = new login($db);
?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title></title>
    </head>
    <body>
        <?php
            if($login->isLoggedIn() == false) {
                echo $login->loginBox();
            }
            if($_GET['logout']) {
                $login->logout();
            }
            if($_GET['register']) {
                echo $login->registerBox();
            }
            echo $login->_debug();
        ?>
    </body>
</html>