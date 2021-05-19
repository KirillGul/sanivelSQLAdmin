<?php
set_time_limit(0); //время выполнения скрипта в сек. (0-бесконечно)

include 'init.php';

/*УСТАНОВКА ЗНАЧЕНИЙ МАССИВА $_COOKIE*/
   if (isset($_POST['check_image_one'])) $checkImage = $_POST['check_image_one'].'';
   else $checkImage = 'No';

   if (isset($_POST['check_param_product'])) $checkParam = $_POST['check_param_product'].'';
   else $checkParam = 'No';

   if (isset($_POST['check_avaliable'])) $checkAvaliable = $_POST['check_avaliable'].'';
   else $checkAvaliable = 'No';

   setcookie("check_image_one", $checkImage);
   setcookie("check_param_product", $checkParam);
   setcookie("check_avaliable", $checkAvaliable);

function printTableImport ($checkImage, $checkParam, $checkAvaliable) {
   if($checkImage == 'Yes') $rezultcheckImage = 'checked';
   else $rezultcheckImage = '';

   if($checkParam == 'Yes') $rezultcheckParam = 'checked';
   else $rezultcheckParam = '';

   if($checkAvaliable == 'Yes') $rezultcheckAvaliable = 'checked';
   else $rezultcheckAvaliable = '';

   return $rezult = "
   <table>
   <form method=\"POST\" enctype=\"multipart/form-data\">
      <tr>
         <td><input type=\"file\" name=\"userfile\"></td>
         <td><input type=\"submit\" value=\"Загрузить файл для обработки\"></td>
      </tr>         
   </form>
   </table>
   <br>
   <table>
   <form method=\"POST\">
      <tr>
         <td><span>Загружать все картинки? - </td>
         <td><input type=\"checkbox\" value=\"Yes\" name=\"check_image_one\" $rezultcheckImage></span></td>
         <td><input type=\"submit\" value=\"Подтвердить\"></td>
      </tr>
      <tr>
         <td><span>Не загружать параметры товара? - </td>
         <td><input type=\"checkbox\" value=\"Yes\" name=\"check_param_product\" $rezultcheckParam></span></td>
         <td><input type=\"submit\" value=\"Подтвердить\"></td>
      </tr>
      <tr>
         <td><span>Загружать товары которые в наличии(true) (использовать только для первичной загрузки) - </td>
         <td><input type=\"checkbox\" value=\"Yes\" name=\"check_avaliable\" $rezultcheckAvaliable></span></td>
         <td><input type=\"submit\" value=\"Подтвердить\"></td>
      </tr>
   </form>
   </table>";
}

function mysql_utf8_sanitizer(string $str)
{
    return preg_replace('/[\x{10000}-\x{10FFFF}]/u', "\xEF\xBF\xBD", $str);
}


if (isset($_SESSION['auth']) AND $_SESSION['auth'] == TRUE) {

/*ПОДГОТОВКА ПЕРЕМЕННЫХ*/
   $title = 'admin import page';

   $info = '';
   if (isset($_SESSION['info'])) {
      $info = $_SESSION['info'];
   }

   $content = '';


   
/*ЛОГИКА*/
   if (isset($_GET['id']) AND isset($_FILES['userfile'])) {

      $content = printTableImport($checkImage, $checkParam, $checkAvaliable);

      /*передача через форму*/
      $uploaddir = 'files/';
      $uploadfile = $uploaddir . basename($_FILES['userfile']['name']);

      
      if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
         echo "Файл корректен и был успешно загружен.\n";
      } else {
         echo "Возможная атака с помощью файловой загрузки!\n";
      }

      /*запись файла в базу*/
	   if (file_exists($uploadfile)) {
         $xml = $uploadfile;
         $reader = new XMLReader(); //логика считывания: (элемент начало + атрубуты->значение->элемент конец) и всё через $reader->read();

         //ДЛЯ КАТЕГОРИЙ
         $reader->open($xml);

         //category
         $cat = [];
         while ($reader->read()) { //работает если категории находяться перед товарами
            if ($reader->name === "category" && $reader->nodeType == XMLReader::ELEMENT) {
               $temp = $reader->getAttribute('id');
               $reader->read();				
               $cat["$temp"] = $reader->value;
            } elseif (($reader->name == "categories" && $reader->nodeType == XMLReader::END_ELEMENT) || ($reader->name == "offer" && $reader->nodeType == XMLReader::ELEMENT)) break;
         }
         //$reader->close();
         //var_dump($cat);
         //Конец парсинга категорий

         //ДЛЯ ТОВАРОВ
         $countProd = 0;
         $countProdUniq = 0;
         $countProdAvaliable = 0;
         $prodArr = [];
         $prodUrlArr = []; //массив для проверки дублей товаров
         $categoryId = $_GET['id'];
         $categoryUri = $_GET['uriCat'];

         //Начало парсинга товаров
         //$reader->open($xml);

         while ($reader->read()) {
            $prodAvailable = $prodCategoryId = $prodCurrencyId = $prodDescription = $prodId = $prodModified_time = $prodName = $prodOldPrice = $prodParam = $prodPicture = $prodPrice = $prodType =$prodUrl = $prodVendor = $prodVendorCode = $prodGroupId = $prodTopSeller = $prod = ''; //обнуление значений
            
            if ($reader->name == "offer" && $reader->nodeType == XMLReader::ELEMENT) {
               $countProd++; //счетчик товаров
               
               $prodAvailable = $reader->getAttribute('available')."";
               
               if ($_COOKIE['check_avaliable'] == 'Yes') { //пропуск итерации если нет в наличии (при выставленной галочке)
                  if ($prodAvailable === 'false' or $prodAvailable === 'False'  or $prodAvailable === 'FASLE' or $prodAvailable === '0' or $prodAvailable === 0) continue;
               }
               
               $prodId = $reader->getAttribute('id')."";
               $prodGroupId = $reader->getAttribute('group_id').""; //для чего не понятно
               
               $countProdAvaliable++; //счетчик товаров
               $prodPicture = '';
               $prodParam = '';

               while ($reader->read()) {
                  if ($reader->nodeType == XMLReader::ELEMENT) { //проверка что это открывающий тег например <item>, а не </item>
                     switch ($reader->name) {
                        case "categoryId":
                           $reader->read();
                           $prodSubCategoryId = $reader->value;
                           continue 2;
                        case "currencyId":
                           $reader->read();
                           $prodCurrencyId = $reader->value;
                           continue 2;
                        case "description":
                           $reader->read();
                           $prodDescription = $reader->value;
                           $prodDescription = trim($prodDescription); //убираем переносы строк по краям
                           $prodDescription = str_replace(array("\r\n", "\r", "\n"), ' ', $prodDescription); //убираем переносы строк ввнутри
                           $prodDescription = str_replace(array("'"), "\'", $prodDescription);
                           $prodDescription = mb_ereg_replace('[\x00\x0A\x0D\x1A\x22\x27\x5C]', '\\\0', $prodDescription);
                           continue 2;
                        case "modified_time":
                           $reader->read();
                           $prodModified_time = $reader->value;
                           continue 2;
                        case "name":
                           $reader->read();
                           $prodName = $reader->value;
                           $prodName = str_replace(array("\r\n", "\r", "\n"), ' ', $prodName); //убираем переносы строк ввнутри
                           $prodName = str_replace(array("'"), "\'", $prodName);
                           continue 2;
                        case "oldprice":
                           $reader->read();
                           $prodOldPrice = $reader->value;
                           continue 2;
                        case "price":
                           $reader->read();
                           $prodPrice = $reader->value;
                           continue 2;
                        case "type":
                           $reader->read();
                           $prodType = $reader->value;
                           continue 2;
                        case "url":
                           $reader->read();
                           $prodUrl = $reader->value;
                           array_push($prodUrlArr, $prodUrl); //для проверки дублей
                           continue 2;
                        case "vendor":
                           $reader->read();
                           $prodVendor = $reader->value;
                           $prodVendor = str_replace(array("'"), "\'", $prodVendor); //убираем переносы строк ввнутри
                           continue 2;
                        case "vendorCode":
                           $reader->read();
                           $prodVendorCode = $reader->value;
                           continue 2;
                        case "topseller":
                           $reader->read();
                           $prodTopSeller = $reader->value;
                           continue 2;
                        case "param":
                           if ($_COOKIE['check_param_product'] == 'Yes') {
                              $prodParam = '';
                           } else {
                              $atrib = $reader->getAttribute('name');
                              
                              if ($atrib == 'origin_url') continue 2; //пропуск определенной параметра
                              $reader->read();
                              $prodParamTemp = $reader->value;
                              $prodParamTemp = str_replace(array("'"), "\'", $prodParamTemp); //убираем переносы строк ввнутри
                              
                              $prodParam .= $atrib.':'.$prodParamTemp."&-&-&";
                           }
                           continue 2;
                        case "picture":
                           if ($_COOKIE['check_image_one'] == 'Yes') {									
                              $reader->read();
                              $picture = $reader->value;	
                              $prodPicture .= $picture."&-&-&";										
                           } else {
                              $reader->read();
                              $prodPicture = $reader->value;
                           }
                           continue 2;
                     }
                  }
                  if ($reader->name == "offer" && $reader->nodeType == XMLReader::END_ELEMENT) break;					
               }
               
               if(isset($cat["$prodSubCategoryId"])) {
                  $prodSubCategoryName = $cat["$prodSubCategoryId"].""; //добавляем имя категории
                  $prodSubCategoryName = str_replace(array("'"), "\'", $prodSubCategoryName);
               } else {
                  $prodSubCategoryName = ""; //добавляем имя категории
               }               

               $prod = [
                  'available'=>$prodAvailable,
                  'subcategoryid'=>$prodSubCategoryId,
                  'subcategoryname'=>$prodSubCategoryName,
                  'CurrencyId'=>$prodCurrencyId,
                  'description'=>$prodDescription,
                  'Id'=>$prodId,
                  'modifiedtime'=>$prodModified_time,
                  'name'=>$prodName,
                  'oldprice'=>$prodOldPrice,
                  'param'=>$prodParam,
                  'picture'=>$prodPicture,
                  'price'=>$prodPrice,
                  'type'=>$prodType,
                  'produrl'=>$prodUrl,
                  'vendor'=>$prodVendor,
                  'vendorcode'=>$prodVendorCode,                  
                  'groupid'=>$prodGroupId,
                  'topseller'=>$prodTopSeller
               ];
               //echo "$prodId<br>";
               
               array_push($prodArr, $prod); //записываем данные в массив				
            }
         }

         $reader->close();
         
         //Подготовка массива с дублями
         $duplicates = array_diff_assoc($prodUrlArr, array_unique($prodUrlArr)); //массив дублей с ключями
         
         //for min optimaze START
         $query = "INSERT INTO product (`uri`,`available`,`category_id`,`category_sub_id`,`description`,`modified_time`,`name`,`oldprice`,`price`,`param`,`picture`,`produrl`,`vendor`,`vendorcode`) VALUES";

         foreach ($prodArr as $key => $value1) {
            ///УДАЛЯЕМ ДУБЛИ			
            if (!array_key_exists($key, $duplicates)) {
               
               $countProdUniq++;

               $available = $value1['available'].'';
               $subcategoryid = $value1['subcategoryid'].'';
               $subcategoryname = $value1['subcategoryname'].'';
               $subcategoryname = mysql_utf8_sanitizer($subcategoryname);
               $subcategoryname = mb_ereg_replace('[\x00\x0A\x0D\x1A\x22\x27\x5C]', '\\\0', $subcategoryname);
               mysqli_real_escape_string($link, $subcategoryname);
               $description = $value1['description'].'';
               $description = mysql_utf8_sanitizer($description);
               mysqli_real_escape_string($link, $description);
               //$description = str_replace('"', '\"', $name);
               $modifiedtime = $value1['modifiedtime'].'';
               $name = $value1['name'].'';
               $name = mysql_utf8_sanitizer($name);
               $name = mb_ereg_replace('[\x00\x0A\x0D\x1A\x22\x27\x5C]', '\\\0', $name);
               mysqli_real_escape_string($link, $name);
              // $name = str_replace('"', '\"', $name);
               /*echo "<pre>";
               var_dump($description);
               echo "</pre>";*/
               $oldprice = $value1['oldprice'].'';
               $price = $value1['price'].'';
               $param = $value1['param'].'';
               $param = mysql_utf8_sanitizer($param);
               $param = mb_ereg_replace('[\x00\x0A\x0D\x1A\x22\x27\x5C]', '\\\0', $param);
               mysqli_real_escape_string($link, $param);
               $picture = $value1['picture'].'';
               $type = $value1['type'].'';
               $produrl = $value1['produrl'].'';
               $vendor = $value1['vendor'].'';
               $vendor = mysql_utf8_sanitizer($vendor);
               $vendor = mb_ereg_replace('[\x00\x0A\x0D\x1A\x22\x27\x5C]', '\\\0', $vendor);
               mysqli_real_escape_string($link, $vendor);
               $vendorcode = $value1['vendorcode'].'';
               $groupid = $value1['groupid'].'';
               $topseller = $value1['topseller'].'';
               
               //max
               /*$query = "INSERT INTO product SET 
               uri='$categoryUri/$countProdUniq', 
               available='$available', 
               category_id='$categoryId',
               category_sub_id='$subcategoryid',
               category_sub_name='$subcategoryname',
               description='$description',
               modified_time='$modifiedtime',
               name='$name',
               oldprice='$oldprice',
               price='$price',
               param='$param',
               picture='$picture',
               type='$type',
               produrl='$produrl',
               vendor='$vendor',
               vendorcode='$vendorcode',
               groupid='$groupid',
               topseller='$topseller'
               ";*/

               //min
               /*$query = "INSERT INTO product SET 
               uri='$categoryUri/$countProdUniq', 
               available='$available', 
               category_id='$categoryId',
               description='$description',
               modified_time='$modifiedtime',
               name='$name',
               oldprice='$oldprice',
               price='$price',
               param='$param',
               picture='$picture',
               produrl='$produrl',
               vendor='$vendor',
               vendorcode='$vendorcode'";*/

               //min optimaze
               $query .= " ('$categoryUri/$countProdUniq','$available','$categoryId','$subcategoryid','$description','$modifiedtime','$name','$oldprice','$price','$param','$picture','$produrl','$vendor','$vendorcode'),";

               /*echo "<pre>";
               var_dump($query);
               echo "</pre>";*/
               //echo "<br>";
               //$result = mysqli_query($link, $query) or die(mysqli_error($link));
               /*$result = mysqli_query($link, $query);
               if (!$result) {
                  echo "<pre>";
                  var_dump($query);
                  echo "</pre>";
               }*/
            }            
         }

         $query = rtrim($query, ',');
         $result = mysqli_query($link, $query) or die( mysqli_error($link) );
         if (!$result) {
            echo "<pre>";
            var_dump($query);
            echo "</pre>";
         }

         $_SESSION['info'] = [
            'msg' => "Файл импорта загружен<br><span>Всего ".$countProd." в наличии(true) - ".$countProdAvaliable." (в т.ч. уник. - ".$countProdUniq.", дубли - ".($countProd-$countProdUniq).")</span>",
            'status' => 'success'
         ];

      } else {

         $_SESSION['info'] = [
            'msg' => "Файл не найден",
            'status' => 'error'
         ];
      }

      /*удаление файла*/
      unlink($uploadfile);

   } else {
      $_SESSION['info'] = [
         'msg' => "Выберите файл для импорта (будут загружены только уникальные значения)",
         'status' => 'warning'
      ];
      $content = printTableImport($checkImage, $checkParam, $checkAvaliable);
   }

   include 'elems/layout.php';

   /*echo '<pre>';
   print_r($cat);
   print "</pre>";*/

} else {
   header('Location: login.php'); die();
}