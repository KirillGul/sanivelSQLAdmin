<?php
include 'init.php';

if (isset($_SESSION['auth']) AND $_SESSION['auth'] == TRUE) {
    
/*ОБРАБОТКА GET ЗАПРОСОВ*/ 
    /*Если нажато выход из авторизации*/
    if (!empty($_GET['logout']) && $_GET['logout'] == 1) {
        header('Location: logout.php'); die();
    }


/*ПОДГОТОВКА ПЕРЕМЕННЫХ*/
    $title = 'admin install page';

    $info = '';
    if (isset($_SESSION['info'])) {
        $info = $_SESSION['info'];
    }

    $content = "";


/*ФУНКЦИИ*/
    function checkTable ($link, $db_name, $nameTable) {
        $query = "SHOW TABLES FROM $db_name LIKE '$nameTable'";
        $result = mysqli_query($link, $query) or die(mysqli_error($link));
        return mysqli_fetch_assoc($result);
    }

    function deleteTable ($link, $nameCat) {
        $query = "DROP TABLE `$nameCat`";
        $result = mysqli_query($link, $query) or die(mysqli_error($link));
        //$result = mysqli_fetch_assoc($result);

        if($result) {
            return [
                'msg' => "Таблица успешно удалена.",
                'status' => 'success'
            ];
        } else {
            return [
                'msg' => "Ошибка удаления таблицы",
                'status' => 'error'
            ];
        }
    }


/*ЛОГИКА НА $_GET запросы*/
    //создание таблиц если $_GET['add']
    if (!empty($_GET['add'])) {

        if ($_GET['add'] == 'category') {
            $query = "CREATE TABLE IF NOT EXISTS category (
                `id` INT NOT NULL AUTO_INCREMENT,
                `uri` VARCHAR(256) NOT NULL,
                `name` VARCHAR(256) NOT NULL,
                PRIMARY KEY (`id`), INDEX `uri` (`uri`)) ENGINE = InnoDB CHARSET=utf8 COLLATE=utf8_general_ci";
           
           $result = mysqli_query($link, $query) or die(mysqli_error($link));
             
        } elseif ($_GET['add'] == 'product') {
            //max
            /*$query = "CREATE TABLE IF NOT EXISTS product (
                `id` INT NOT NULL AUTO_INCREMENT,
                `uri` VARCHAR(256) NOT NULL,
                `available` VARCHAR(256) NOT NULL,
                `category_id` VARCHAR(256) NOT NULL,
                `category_sub_id` VARCHAR(256) NULL DEFAULT NULL,
                `category_sub_name` VARCHAR(256) NULL DEFAULT NULL,
                `description` TEXT NULL DEFAULT NULL,
                `modified_time` VARCHAR(256) NULL DEFAULT NULL,
                `name` VARCHAR(256) NOT NULL,
                `oldprice` VARCHAR(256) NULL DEFAULT NULL,
                `price` VARCHAR(256) NOT NULL,
                `param` TEXT NULL DEFAULT NULL,
                `picture` TEXT NULL DEFAULT NULL,
                `type` VARCHAR(256) NULL DEFAULT NULL,
                `produrl` TEXT NOT NULL,          
                `vendor` VARCHAR(256) NULL DEFAULT NULL,
                `vendorcode` VARCHAR(256) NULL DEFAULT NULL,
                `groupid` VARCHAR(256) NOT NULL,
                `topseller` VARCHAR(256) NULL DEFAULT NULL,
                `similar_products` VARCHAR(256) NULL DEFAULT NULL,
                PRIMARY KEY `id` (`id`)) ENGINE = InnoDB CHARSET=utf8 COLLATE=utf8_general_ci";*/
            //min
            $query = "CREATE TABLE IF NOT EXISTS product (
                `id` INT NOT NULL AUTO_INCREMENT,
                `uri` VARCHAR(256) NOT NULL,
                `available` VARCHAR(256) NOT NULL,
                `category_id` VARCHAR(256) NOT NULL,
                `description` TEXT NULL DEFAULT NULL,
                `modified_time` VARCHAR(256) NULL DEFAULT NULL,
                `name` VARCHAR(256) NOT NULL,
                `oldprice` VARCHAR(256) NULL DEFAULT NULL,
                `price` VARCHAR(256) NOT NULL,
                `param` TEXT NULL DEFAULT NULL,
                `picture` TEXT NULL DEFAULT NULL,
                `produrl` TEXT NOT NULL,          
                `vendor` VARCHAR(256) NULL DEFAULT NULL,
                `vendorcode` VARCHAR(256) NULL DEFAULT NULL,
                `similar_products` VARCHAR(256) NULL DEFAULT NULL,
                PRIMARY KEY `id` (`id`), INDEX `uri` (`uri`), INDEX `category_id` (`category_id`)) ENGINE = InnoDB CHARSET=utf8 COLLATE=utf8_general_ci";
            $result = mysqli_query($link, $query) or die(mysqli_error($link));
        } elseif ($_GET['add'] == 'page') {
            $query = "CREATE TABLE IF NOT EXISTS page (
                `id` INT NOT NULL AUTO_INCREMENT,
                `uri` VARCHAR(256) NOT NULL,
                `name` VARCHAR(256) NOT NULL,
                PRIMARY KEY (`id`)) ENGINE = InnoDB CHARSET=utf8 COLLATE=utf8_general_ci";

            $result = mysqli_query($link, $query) or die(mysqli_error($link));

            $query =    "INSERT INTO 
                            `page` (`name`, `uri`)
                        VALUES 
                            ('main', '/'),
                            ('404', '404')";
            $result = mysqli_query($link, $query) or die(mysqli_error($link));

        } elseif($_GET['add'] == 'other_online_stores') {
            $query = "CREATE TABLE IF NOT EXISTS other_online_stores (
                `product_id` INT NOT NULL AUTO_INCREMENT,
                `category_id` VARCHAR(256) NOT NULL,
                `other_online_stores` VARCHAR(250) NULL DEFAULT NULL,
                PRIMARY KEY `product_id` (`product_id`)) ENGINE = InnoDB CHARSET=utf8 COLLATE=utf8_general_ci";
            $result = mysqli_query($link, $query) or die(mysqli_error($link));

        } elseif($_GET['add'] == 'similar_products') {
            $query = "CREATE TABLE IF NOT EXISTS similar_products (
                `product_id` INT NOT NULL AUTO_INCREMENT,
                `category_id` VARCHAR(256) NOT NULL,
                `similar_products` VARCHAR(250) NULL DEFAULT NULL,
                PRIMARY KEY `product_id` (`product_id`)) ENGINE = InnoDB CHARSET=utf8 COLLATE=utf8_general_ci";
            $result = mysqli_query($link, $query) or die(mysqli_error($link));
        }
     
        if ($result) {
            $_SESSION['info'] = [
                'msg' => "Таблица успешно созданна.",
                'status' => 'success'
            ];
            header('Location: install.php'); die();
        } else {
            $info = $_SESSION['info'] = [
                'msg' => "Ошибка создания таблицы",
                'status' => 'error'
            ];
            include 'elems/layout.php';
        }
    }

    $arrTable = ['category'=>'category', 'product'=>'product', 'page'=>'page', 'other_online_stores'=>'other_online_stores', 'similar_products'=>'similar_products']; //массив таблиц

    //удаление таблиц если $_GET['delTable']
    if (!empty($_GET['delTable']) && isset($arrTable[$_GET['delTable']])) {
        $table = $arrTable[$_GET['delTable']];
        $_SESSION['info'] = $info = deleteTable($link, $table);
        header('Location: install.php'); die();
    }

/*ЛОГИКА*/
    $content = "
            <table>
                <tr>
                    <th>№</th>
                    <th>Таблица</th>
                    <th>Действие</th>
                    <th>Состояние</th>
                </tr>";

    
    

    foreach ($arrTable as $key=>$table) { //вывод таблицы
        $content .= "<tr><td>$key</td><td>'$table'</td>";
        if (empty(checkTable($link, $db_name, $table))) {
            $content .= "
                        <td><a href=\"?add=$table\">Создать таблицу '$table'</a></td>
                        <td>Нет в базе</td>
                    </tr>"; 
        } else {
            $content .= "
                    <td><a href=\"?delTable=$table\">Удалить таблицу '$table'</a></td>
                    <td>Созданно</td>
                </tr>";
        }
    }

    $content .= "</table>";

    include 'elems/layout.php';

} else {
   header('Location: login.php'); die();
}