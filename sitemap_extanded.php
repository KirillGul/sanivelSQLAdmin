<?php
set_time_limit(0); //время выполнения скрипта в сек. (0-бесконечно)

include 'init.php';

/*УСТАНОВКА ЗНАЧЕНИЙ МАССИВА $_COOKIE*/
if (isset($_POST['check_category'])) $rezultCat = $_POST['check_category'].'';
else $rezultCat = 'No';

if (isset($_POST['check_product'])) $rezultProd = $_POST['check_product'].'';
else $rezultProd = 'No';

setcookie("check_category", $rezultCat);
setcookie("check_product", $rezultProd);

function printTableSitemap ($rezultCat, $rezultProd) {
   if($rezultProd == 'Yes') $rezultcheckProd = 'checked';
   else $rezultcheckProd = '';

   if($rezultCat == 'Yes' || $rezultProd == 'Yes') {
      $rezultCat = 'Yes';
      $rezultcheckCat = 'checked'; //rezultProd только для данного случая
   } else $rezultcheckCat = '';

   return $rezult = "
   <table>
   <form method=\"POST\">
      <tr>
         <th colspan=\"3\">Генерация карты сайта<br>(если ни чего не выбрать, в sitemap.xml будет только '/')</th>
      </tr>
      <tr>
         <td><span>Категории </td>
         <td>($rezultCat) <input type=\"checkbox\" value=\"Yes\" name=\"check_category\" $rezultcheckCat></span></td>
         <td><input type=\"submit\" value=\"Подтвердить - Не рабочее\"></td>
      </tr>
      <tr>
         <td><span>Товары </td>
         <td>($rezultProd) <input type=\"checkbox\" value=\"Yes\" name=\"check_product\" $rezultcheckProd></span></td>
         <td><input type=\"submit\" value=\"Подтвердить - Не рабочее\"></td>
      </tr>
      <tr>
         <td colspan=\"3\" style=\"text-align:center;\"><input type=\"submit\" name=\"gen_sitemap\" value=\"Генерировать\"></td>
      </tr>
   </form>
   </table>";
}

if (isset($_SESSION['auth']) AND $_SESSION['auth'] == TRUE) {

   /*ПОДГОТОВКА ПЕРЕМЕННЫХ*/
      $title = 'admin sitemap Extended page';
   
      $info = '';
      if (isset($_SESSION['info'])) {
         $info = $_SESSION['info'];
      }
   
      $content = '';
      $xmlmap = '';

      /*ЛОГИКА*/
      if (isset($_POST['gen_sitemap'])) {
         
         if (file_exists('map/sitemap.xml'))
            rmdir ('map');
            mkdir('map');
         //unlink('map/sitemap.xml');

         $f = fopen('map/sitemap.xml', 'w');

         /*Главная карта - начало*/
         $xmlmap .= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
         $xmlmap .= "<sitemapindex xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd\">\n";
         $xmlmap .= "   <sitemap>\n";
         $xmlmap .= "     <loc>$prefhostHTTP$hostHTTP/sitemap1.xml</loc>\n";
         $date = date('Y-m-d', time()); 
         $xmlmap .= "     <lastmod>$date</lastmod>\n";
         $xmlmap .= "   </sitemap>\n";

         /*Карты из категорий и товаров по 25000 - начало*/
         $query = "SELECT id, uri FROM category";
         $result = mysqli_query($link, $query) or die(mysqli_error($link));
         for ($dataCategory = []; $row = mysqli_fetch_assoc($result); $dataCategory[] = $row);

         $countCat = count($dataCategory);
         if ($dataCategory AND $countCat > 0) {

            //перебор категорий и товаров
            $countSietMap = 1;
            $countProductInSiteMap = 2;
            $xmlmapCat = '';
            
            //для главной
            $fCat = fopen("map/sitemap$countSietMap.xml", 'w');
            $xmlmapCat .= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
            $xmlmapCat .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
            $xmlmapCat .= "  <url>\n";
            $xmlmapCat .= "     <loc>$prefhostHTTP$hostHTTP</loc>\n";
            $date = date('Y-m-d', time()); 
            $xmlmapCat .= "     <lastmod>$date</lastmod>\n";
            $xmlmapCat .= "     <changefreq>daily</changefreq>\n";
            $xmlmapCat .= "     <priority>1.0</priority>\n";
            $xmlmapCat .= "  </url>\n";

            //для category
            foreach ($dataCategory as $cat) {
               $catID = $cat['id'];
               $catURI = $cat['uri'];

               if ($countProductInSiteMap == 1) {
                  $fCat = fopen("map/sitemap$countSietMap.xml", 'w');
                  $xmlmapCat .= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
                  $xmlmapCat .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
               }

               $xmlmapCat .= "  <url>\n";
               $xmlmapCat .= "     <loc>$prefhostHTTP$hostHTTP/$catURI</loc>\n";
               $date = date('Y-m-d', time()); 
               $xmlmapCat .= "     <lastmod>$date</lastmod>\n";
               $xmlmapCat .= "     <changefreq>daily</changefreq>\n";
               $xmlmapCat .= "     <priority>0.8</priority>\n";
               $xmlmapCat .= "  </url>\n";

               //для product
               $query = "SELECT uri, modified_time FROM product WHERE category_id='$catID'";
               $result = mysqli_query($link, $query) or die(mysqli_error($link));
               for ($dataProduct = []; $row = mysqli_fetch_assoc($result); $dataProduct[] = $row);
               
               $countProd = count($dataProduct);
               if ($dataProduct AND $countProd > 0) {  

                  foreach ($dataProduct as $prod) {
                     $prodURI = $prod['uri'];
                     if (!empty($prod['modified_time'])) {
                        $dateProd = date('Y-m-d', $prod['modified_time']);
                     } else $dateProd = '';

                     $xmlmapCat .= "  <url>\n";
                     $xmlmapCat .= "     <loc>$prefhostHTTP$hostHTTP/$prodURI</loc>\n";
                     if (!empty($dateProd)) {
                        $xmlmapCat .= "     <lastmod>$dateProd</lastmod>\n";
                     }
                     $xmlmapCat .= "     <changefreq>daily</changefreq>\n";
                     $xmlmapCat .= "  </url>\n";

                     if ($countProductInSiteMap == 25000) {
                        $xmlmapCat .= "</urlset>";
                        fwrite($fCat, $xmlmapCat);
                        fclose($fCat);

                        $countSietMap++;
                        $xmlmapCat = '';
                        $fCat = fopen("map/sitemap$countSietMap.xml", 'w');
                        $xmlmapCat .= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
                        $xmlmapCat .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

                        $countProductInSiteMap = 1;

                        $xmlmap .= "   <sitemap>\n";
                        $xmlmap .= "     <loc>$prefhostHTTP$hostHTTP/sitemap$countSietMap.xml</loc>\n";
                        $date = date('Y-m-d', time()); 
                        $xmlmap .= "     <lastmod>$date</lastmod>\n";
                        $xmlmap .= "   </sitemap>\n";
                        continue;
                     }
                     $countProductInSiteMap++;
                  }
               } elseif ($countProductInSiteMap == 25000) {
                     $xmlmapCat .= "</urlset>";
                     fwrite($fCat, $xmlmapCat);
                     fclose($fCat);

                     $countSietMap++;
                     $xmlmapCat = '';
                     $fCat = fopen("map/sitemap$countSietMap.xml", 'w');
                     $xmlmapCat .= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
                     $xmlmapCat .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

                     $countProductInSiteMap = 1;

                     $xmlmap .= "   <sitemap>\n";
                     $xmlmap .= "     <loc>$prefhostHTTP$hostHTTP/sitemap$countSietMap.xml</loc>\n";
                     $date = date('Y-m-d', time()); 
                     $xmlmap .= "     <lastmod>$date</lastmod>\n";
                     $xmlmap .= "   </sitemap>\n";
                     continue;                  
               }
               $countProductInSiteMap++;
            }
         }

         /*Карты из категорий и товаров по 25000 - конец*/
         $xmlmapCat .= "</urlset>";
         fwrite($fCat, $xmlmapCat);
         fclose($fCat);
         
         /*Главная карта - конец*/
         $xmlmap .= "</sitemapindex>";         
         fwrite($f, $xmlmap);
         fclose($f);
      
      }

   $content .= printTableSitemap($rezultCat, $rezultProd);


   include 'elems/layout.php';

   /*echo '<pre>';
   print_r($cat);
   print "</pre>";*/
   
} else {
   header('Location: login.php'); die();
}