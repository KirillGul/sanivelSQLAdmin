<?php
include 'init.php';

function showPageTable ($link) {
    $query = "SELECT `id`, `name`, `uri` FROM category";
    $result = mysqli_query($link, $query) or die( mysqli_error($link) );
    //Преобразуем то, что отдала нам база в нормальный массив PHP $data:
    for ($data = []; $row = mysqli_fetch_assoc($result); $data[] = $row);

    $countCategory = count($data);

    $table = "
        <table>
        <tr>
        <th>ID</th>
        <th>Name</th>
        <th>URL</th>
        <th>
            Count<br>
            <a href=\"?refreshCount=1\">Пересчитать</a>
        </th>
        <th>ImportXML</th>
        <th>Сгенерировать похожие<br>товары</th>
        <th>Сгенерировать другие<br>интернет-магазины</th>
        <th>Очистить</th>        
        <th>Удалить</th>
        </tr>";
    foreach ($data as $value) {
        $idCat = $value['id'];
        $uriCat = $value['uri'];
        $nameCat = $value['name'];

        if (!isset($_COOKIE[$uriCat]) OR $_COOKIE[$uriCat] == '0') { 
            //узнаем сколько товаров в данной категории
            $query = "SELECT COUNT(*) as count FROM product WHERE category_id='$idCat'";
            $result = mysqli_query($link, $query) or die( mysqli_error($link) );
            //Преобразуем то, что отдала нам база в нормальный массив PHP $data:
            $countProduct = mysqli_fetch_assoc($result)['count'];
            setcookie($uriCat, $countProduct);
            //var_dump($_COOKIE[$uriCat]);
        } else {                
            $countProduct = $_COOKIE[$uriCat];
            //var_dump($_COOKIE[$uriCat]);
        }

        $table .= "<tr>
            <td>$idCat</td>
            <td>$nameCat</td>
            <td>$uriCat</td>
            <td>$countProduct</td>
            <td><a href=\"import.php?id=$idCat&uriCat=$uriCat\">Import</a></td>";

        if ($countProduct > 7) {
            $table .= "<td><a href=\"?generetionUniq=$idCat&countProd=$countProduct\">Похожие товары<br>(той же категории)</a>
                        <br><a href=\"?generetionUniqDel=$idCat\">Очистить</a></td>";
        } else {
            $table .= "<td>В категории мало товаров для генерации</td>";       
        }

        $table .= "<td><a href=\"?generetionOtherIMUniq=$idCat&countCategory=$countCategory\">Другие интернет-мгазины</a>
                        <br><a href=\"?generetionOtherIMUniqDel=$idCat\">Очистить</a></td>";
        $table .= "<td><a href=\"?delProductID=$idCat\">Очистить от товаров</a></td>
            <td><a href=\"?delCategoryID=$idCat\">Удалить категорию</a></td>
            </tr>";
    }
    return $table .= "</table>";
}

function deleteProduct ($link, $idCat, $nameCat='product', $param='category_id') { //удаление страниц в таблице и таблиц
    $query = "SELECT * FROM $nameCat WHERE $param='$idCat'";
    $result = mysqli_query($link, $query) or die(mysqli_error($link));
    for ($data = []; $row = mysqli_fetch_assoc($result); $data[] = $row);

    if ($data) {
        foreach ($data as $prod) {
            $query = "DELETE FROM $nameCat WHERE $param='$idCat'";
            $result = mysqli_query($link, $query) or die(mysqli_error($link));

            if ($nameCat=='product') {
                $query = "DELETE FROM other_online_stores WHERE $param='$idCat'";
            $result = mysqli_query($link, $query) or die(mysqli_error($link));
            }

            if($result) return true;
            else return false;
        }

    } else return true;
}

function checkTable ($link, $db_name, $nameTable) {
    $query = "SHOW TABLES FROM $db_name LIKE '$nameTable'";
    $result = mysqli_query($link, $query) or die(mysqli_error($link));
    return mysqli_fetch_assoc($result);
}

 function checkURI ($link, $uri, $table='category') { //запрос существования страницы в таблице
    $query = "SELECT COUNT(*) as count FROM $table WHERE uri='$uri'";
    $result = mysqli_query($link, $query) or die( mysqli_error($link) );
    return mysqli_fetch_assoc($result)['count']; //Преобразуем то, что отдала нам база из объекта в нормальный массив с одним значением и значение count
}

function getTranslit ($str) {
    $arr = ['a'=>'a', 'б'=>'b', 'в'=>'v', 'г'=>'g', 
       'д'=>'d', 'е'=>'e', 'ё'=>'e', 'ж'=>'zh', 'з'=>'z', 
       'и'=>'i', 'й'=>'i', 'к'=>'k', 'л'=>'l', 'м'=>'m', 
       'н'=>'n', 'о'=>'o', 'п'=>'p', 'р'=>'r', 'с'=>'s', 
       'т'=>'t', 'у'=>'u', 'ф'=>'f', 'х'=>'kh', 'ц'=>'ts', 
       'ч'=>'ch', 'ш'=>'sh', 'щ'=>'shch', 'ъ'=>'ie', 'ы'=>'y', 
       'ь'=>'', 'э'=>'e', 'ю'=>'iu', 'я'=>'ia'];
 
    foreach (preg_split('/(?<!^)(?!$)/u', $str) as $value) {
        if (isset($arr[$value])) {
            $arrTranslit[] = $arr[$value];
        } else {
            $arrTranslit[] = $value;
        }
    }
    return $arrTranslit;
 }

 function prodStartGen ($countProd, $lim) {
    $prodRand = rand(0 ,$countProd);

    if ($countProd < $prodRand+$lim) {
        $prodRand = prodStartGen ($countProd, $lim);
    }
    return $prodRand;
 }
///////////////////////////////////////////////////////////////////////////////////////
/*Начало логики*/
if (isset($_SESSION['auth']) AND $_SESSION['auth'] == TRUE) {
/*Выход из авторизации*/
    if (!empty($_GET['logout']) && $_GET['logout'] == 1) {
        header('Location: logout.php'); die();
    }

/*ПОДГОТОВКА ПЕРЕМЕННЫХ*/
    $title = 'admin main page';

    $info = '';
    if (isset($_SESSION['info'])) {
        $info = $_SESSION['info'];
    }

    $infoTime = '';
    if (isset($_SESSION['timeScript'])) {
        $infoTime = $_SESSION['timeScript'];
    }

    $content = "";


/*ОБРАБОТКА GET ЗАПРОСОВ*/
    /*Если нажато пересчитать товары*/
    if (!empty($_GET['refreshCount'])) {
        $query = "SELECT `id`, `name`, `uri` FROM category";
        $result = mysqli_query($link, $query) or die( mysqli_error($link) );
        //Преобразуем то, что отдала нам база в нормальный массив PHP $data:
        for ($data = []; $row = mysqli_fetch_assoc($result); $data[] = $row);

        foreach ($data as $value) {
            $idCat = $value['id'];
            $uriCat = $value['uri'];
            $nameCat = $value['name'];

            //узнаем сколько товаров в данной категории
            $query = "SELECT COUNT(*) as count FROM product WHERE category_id='$idCat'";
            $result = mysqli_query($link, $query) or die( mysqli_error($link) );
            //Преобразуем то, что отдала нам база в нормальный массив PHP $data:
            $countProduct = mysqli_fetch_assoc($result)['count'];
            setcookie($uriCat, $countProduct);
        }

        $_SESSION['info'] = [
            'msg' => "Успешно пересчитано",
            'status' => 'success'
        ];
        header('Location: /'); die();
    }

    /*Если нажато удаление товаров*/
    if (!empty($_GET['delProductID'])) {
        if (deleteProduct($link, $_GET['delProductID'])) {
            $_SESSION['info'] = [
                'msg' => "Успешно удаленно",
                'status' => 'success'
            ];
            header('Location: /'); die();
        } else {
            $_SESSION['info'] = [
                'msg' => "Ошибка удаления",
                'status' => 'error'
            ];
            header('Location: /'); die();
        }
    }

    /*Если нажато удаление категории*/
    if (!empty($_GET['delCategoryID'])) {
        $idCat = $_GET['delCategoryID'];
        
        //удаление категории
        $query = "DELETE FROM category WHERE id='$idCat'";
        $result = mysqli_query($link, $query) or die(mysqli_error($link));

        //удаление товаров и данных из других таблиц
        $result = deleteProduct($link, $_GET['delCategoryID']);

        if ($result) {
            $_SESSION['info'] = [
                'msg' => "Успешно удаленно",
                'status' => 'success'
            ];
            header('Location: /'); die();
        } else {
            $_SESSION['info'] = [
                'msg' => "Ошибка удаления",
                'status' => 'error'
            ];
            header('Location: /'); die();
        }
    }

    /*Если нажато генерация похожих товаров*/
    if (!empty($_GET['generetionUniq'])) {
        $genCatID = $_GET['generetionUniq'];
        $countProd = $_GET['countProd'];

        //выбрать id всех товаров нужной категории
        $query = "SELECT id, category_sub_id FROM product WHERE category_id='$genCatID'";
        $result = mysqli_query($link, $query) or die(mysqli_error($link));
        for ($dataProd = []; $row = mysqli_fetch_assoc($result); $dataProd[] = $row);

        $start = microtime(true); //начало времени выполнения скрипта

        $query = "INSERT INTO similar_products (`product_id`,`category_id`,`similar_products`) VALUES";

        $flag = 1;
        $timeQuery = 300;
        foreach ($dataProd as $idValue) {

            $tempArrProd = [];
            foreach($dataProd as $val) {
                if ($idValue['category_sub_id'] == $val['category_sub_id'] AND $idValue['id'] != $val['id'])
                    $tempArrProd[] = $val['id'];
            }

            $intArr = count($tempArrProd);
            
            if ($intArr == 0) {
                continue;
            } elseif ($intArr == 1) {
                $intArr = 7;
                $dataGenCatSlice = $tempArrProd;
            } elseif ($intArr > 7) {
                $intArr = 7;
                unset($dataGenCatSlice);
                $dataGenCatSlice = array_rand(array_flip($tempArrProd), $intArr);
            }
      
            //генерация от какого товара начинать LIMIT
            //$prodRand = prodStartGen ($countProd, 7);
            //$dataGenCatSlice = array_slice($dataProd, $prodRand, 7);

            if (!isset($dataGenCatSlice)) continue;

            //START вставка данных в таблицы продукты, в колонку similar_products
            $dataGenStr = '';
            foreach ($dataGenCatSlice as $value) {
                $dataGenStr .= $value.';';
            }
            $idValue = $idValue['id'];

            if (round(microtime(true) - $start, 4) >= $timeQuery AND (round(microtime(true) - $start, 4)) <= $timeQuery+50) { //если импорт больше 5 мин.
                $query = rtrim($query, ',');
                mysqli_query($link, $query) or die(mysqli_error($link));
                $query = "INSERT INTO similar_products (`product_id`,`category_id`,`similar_products`) VALUES";
                //$flag = 0;
                $timeQuery += $timeQuery; //добавляем по 5 мин.
            }

            $query .= " ('$idValue','$genCatID','$dataGenStr'),";
            //END вставка
        }

        $query = rtrim($query, ',');
        mysqli_query($link, $query) or die(mysqli_error($link));

        $_SESSION['timeScript'] = '<p>Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.</p>'; //конец времени выполнения скрипта
        
        $_SESSION['info'] = [
            'msg' => "Успешно добавленно",
            'status' => 'success'
        ];
        header('Location: /'); die();
    }

    /*Если нажато очистить от других товаров*/
    if (!empty($_GET['generetionUniqDel'])) {
        if (deleteProduct($link, $_GET['generetionUniqDel'], 'similar_products')) {
            $_SESSION['info'] = [
                'msg' => "Успешно очищенна таблица",
                'status' => 'success'
            ];
            header('Location: /'); die();
        } else {
            $_SESSION['info'] = [
                'msg' => "Ошибка очищения таблицы",
                'status' => 'error'
            ];
            header('Location: /'); die();
        }
    }

    /*Если нажато генерация других ИМ*/
    if (!empty($_GET['generetionOtherIMUniq'])) {
        $genCatID = $_GET['generetionOtherIMUniq']; //какую категорию исключить
        $countCategory = $_GET['countCategory']; //сколько категорий
        
        //выбрать id всех товаров нужной категории
        $query = "SELECT `id` FROM `product` WHERE `category_id`=$genCatID";
        $result = mysqli_query($link, $query) or die(mysqli_error($link));
        for ($dataProd = []; $row = mysqli_fetch_assoc($result); $dataProd[] = $row);

        //выбрать id всех категории кроме запрашиваемой
        $query = "SELECT `id` FROM `category` WHERE `id`!=$genCatID";
        $result = mysqli_query($link, $query) or die(mysqli_error($link));
        for ($dataGenCat = []; $row = mysqli_fetch_assoc($result); $dataGenCat[] = $row);

        $start = microtime(true); //начало времени выполнения скрипта

        $query = "INSERT INTO other_online_stores (`product_id`,`category_id`,`other_online_stores`) VALUES";

        foreach ($dataProd as $idValue) { //в каждый товар
            //генерация от какого товара начинать LIMIT
            $prodRand = prodStartGen ($countCategory, 12);
            $dataGenCatSlice = array_slice($dataGenCat, $prodRand, 12);

            //вставка данных в таблицы продукты, в колонку other_online_stores
            $dataGenCatStr = '';
            foreach ($dataGenCatSlice as $val) {
                $dataGenCatStr .= $val['id'].';';
            }
            $idValue = $idValue['id'];

            $query .= " ('$idValue','$genCatID','$dataGenCatStr'),";
        }

        $query = rtrim($query, ',');
        mysqli_query($link, $query) or die(mysqli_error($link));

        $_SESSION['timeScript'] = '<p>Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.</p>'; //конец времени выполнения скрипта

        $_SESSION['info'] = [
            'msg' => "Успешно добавленно",
            'status' => 'success'
        ];
        header('Location: /'); die();
    }

    /*Если нажато очистить от других ИМ*/
    if (!empty($_GET['generetionOtherIMUniqDel'])) {
        if (deleteProduct($link, $_GET['generetionOtherIMUniqDel'], 'other_online_stores')) {
            $_SESSION['info'] = [
                'msg' => "Успешно очищенна таблица",
                'status' => 'success'
            ];
            header('Location: /'); die();
        } else {
            $_SESSION['info'] = [
                'msg' => "Ошибка очищения таблицы",
                'status' => 'error'
            ];
            header('Location: /'); die();
        }
    }



/*ОБРАБОТКА POST ЗАПРОСОВ*/
    /*Добавление категорий если были введены названия в форму*/
    if (isset($_POST['text'])) {
        $textValue = $_POST['text'];

        $arrCat = explode ("\n", $textValue);

        foreach ($arrCat as $value) {
            $nameCat =  (str_replace('.xml', '', $value)); //убрать .xml
            $nameCat =  (str_replace("'", '', $nameCat)); //убрать '
            $nameCat =  trim($nameCat);

            $uriCat = mb_strtolower(str_replace(' ', '-', $nameCat)); //перевод в нижн. регистр и замена пробелов на тире 
            $uriCat = implode('', getTranslit($uriCat));

            if (checkURI($link, $uriCat , 'category') == false) {
                $query = "INSERT INTO category SET name='$nameCat', uri='$uriCat'";
                $result = mysqli_query($link, $query) or die(mysqli_error($link));
            }
        }

        header('Location: /'); die();

    } else {
        $textValue = "Введите названия категорий (каждую категорию с новой строки)";
    }



/*ВЫВОД НА ЭКРАН ТАБЛИЦ*/
    /*Проверка инсталяции таблиц в базе*/
    $rezStaticPage = checkTable ($link, $db_name, $nameTable = 'page');
    $rezCategory = checkTable($link, $db_name, $nameTable = 'category');
    $rezProduct = checkTable ($link, $db_name, $nameTable = 'product');
    $rezProduct = checkTable ($link, $db_name, $nameTable = 'other_online_stores');

    if (!isset($rezStaticPage) || !isset($rezCategory) || !isset($rezProduct)) {
        /*Если не созданы таблицы*/
        header('Location: install.php'); die();        
    } else {
        /*Если все создано вывод на экран*/
        $content .= showPageTable($link);
    }

    /*Добавление категорий*/
    $content .= "<br><br><p>ДОБАВЛЕНИЕ КАТЕГОРИЙ</p>";
    $content .= "<table><tr>";
    $content .= "<td>";
    $content .= "<form method=\"POST\">
            <textarea name=\"text\" style=\"width:500px; height:300px;\">$textValue</textarea><br><br>
            <input type=\"submit\">
        </form>";
    $content .= "</td>";
    $content .= "</tr></table>";


        
/*КОНЕЦ*/    
    include 'elems/layout.php';
} else {
    header('Location: login.php'); die();
}

/*echo "<pre>";
print_r($tempArrProd);
echo "</pre>";*/