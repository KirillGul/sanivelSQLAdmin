<?php
session_start(); //для авторизации
set_time_limit(0); //время выполнения скрипта в сек. (0-бесконечно)
error_reporting(E_ALL);
ini_set('display_errors', 'on');

$passwordAdmin = '202cb962ac59075b964b07152d234b70'; //MD5

$prefhostHTTP = 'http://'; //протокол
$hostHTTP = $_SERVER['HTTP_HOST']; //доменное имя

//Устанавливаем доступы к базе данных:
$host = 'localhost'; //имя хоста, на локальном компьютере это localhost
$user = 'root'; //имя пользователя, по умолчанию это root
$password = 'root'; //пароль, по умолчанию пустой
$db_name = 'emagazinadmin'; //имя базы данных
//Соединяемся с базой данных используя наши доступы (возвращет объект):
//$link = mysqli_connect($host, $user, $password, $db_name) or die(mysqli_error($link));
$link = mysqli_connect($host, $user, $password);
if ($link) {
    mysqli_query($link, "CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET='utf8' COLLATE='utf8_general_ci'") or die(mysqli_error($link));
    mysqli_select_db($link, $db_name);
} else {
    die("Connection failed: " . mysqli_connect_error());
}