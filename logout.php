<?php
session_start();
session_destroy();
session_start();

$_SESSION['info'] = [
   'msg' => "Выполнен выход.",
   'status' => 'success'
];

header('Location: login.php');