<?php
include 'init.php';

if ((isset($_POST['password']) AND md5($_POST['password']) == $passwordAdmin) OR (isset($_SESSION['auth']) AND $_SESSION['auth'] == TRUE)) {
   $_SESSION['auth'] = TRUE;

   $_SESSION['info'] = [
      'msg' => "Выполнен вход.",
      'status' => 'success'
   ];

   header('Location: /');
} else {
   $title = "admin login page";
   $content = "<br><form method=\"POST\">
      Введите пароль: <input type=\"password\" name=\"password\"><br><br>
      <input type=\"submit\">
   </form><br>";
}

include 'elems/layout.php';