<?php
set_time_limit(0); //время выполнения скрипта в сек. (0-бесконечно)

include '../elems//init.php';

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
         <td><input type=\"submit\" value=\"Подтвердить\"></td>
      </tr>
      <tr>
         <td><span>Товары </td>
         <td>($rezultProd) <input type=\"checkbox\" value=\"Yes\" name=\"check_product\" $rezultcheckProd></span></td>
         <td><input type=\"submit\" value=\"Подтвердить\"></td>
      </tr>
      <tr>
         <td colspan=\"3\" style=\"text-align:center;\"><input type=\"submit\" name=\"gen_sitemap\" value=\"Генерировать\"></td>
      </tr>
   </form>
   </table>";
}

if (isset($_SESSION['auth']) AND $_SESSION['auth'] == TRUE) {

   /*ПОДГОТОВКА ПЕРЕМЕННЫХ*/
      $title = 'admin sitemap page';
   
      $info = '';
      if (isset($_SESSION['info'])) {
         $info = $_SESSION['info'];
      }
   
      $content = '';
      $xmlmap = '';

      /*ЛОГИКА*/
      if (isset($_POST['gen_sitemap'])) {
         
         if (file_exists('..//sitemap.xml'))
            unlink('..//sitemap.xml');

         $f = fopen('..//sitemap.xml', 'w');

         $xmlmap .= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
         $xmlmap .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
         $xmlmap .= "  <url>\n";
         $xmlmap .= "     <loc>$prefhostHTTP$hostHTTP</loc>\n";
         $date = date('Y-m-d', time()); 
         $xmlmap .= "     <lastmod>$date</lastmod>\n";
         $xmlmap .= "     <changefreq>daily</changefreq>\n";
         $xmlmap .= "     <priority>1.0</priority>\n";
         $xmlmap .= "  </url>\n";

         if (isset($_POST['check_category']) || isset($_POST['check_product'])) {
            
            $query = "SELECT uri FROM category";
            $result = mysqli_query($link, $query) or die(mysqli_error($link));
            for ($dataCategory = []; $row = mysqli_fetch_assoc($result); $dataCategory[] = $row);

            if ($dataCategory) {
               foreach ($dataCategory as $cat) {

                  $catURI = $cat['uri'];

                  $xmlmap .= "  <url>\n";
                  $xmlmap .= "     <loc>$prefhostHTTP$hostHTTP/$catURI/</loc>\n";
                  $date = date('Y-m-d', time()); 
                  $xmlmap .= "     <lastmod>$date</lastmod>\n";
                  $xmlmap .= "     <changefreq>monthly</changefreq>\n";
                  $xmlmap .= "     <priority>0.9</priority>\n";
                  $xmlmap .= "  </url>\n";

                  if (isset($_POST['check_product'])) {
                     $query = "SELECT uri, modified_time FROM product";
                     $result = mysqli_query($link, $query) or die(mysqli_error($link));
                     for ($dataProduct = []; $row = mysqli_fetch_assoc($result); $dataProduct[] = $row);

                     if ($dataProduct) {
                        foreach ($dataProduct as $prod) {
                           $prodURI = $prod['uri'];

                           if (!empty($prod['modified_time'])) {
                              $dateProd = date('Y-m-d', $prod['modified_time']);
                           } else $dateProd = '';
         
                           $xmlmap .= "  <url>\n";
                           $xmlmap .= "     <loc>$prefhostHTTP$hostHTTP/$catURI/$prodURI</loc>\n";
                           if (!empty($dateProd)) {
                              $xmlmap .= "     <lastmod>$dateProd</lastmod>\n";
                           }
                           $xmlmap .= "     <changefreq>monthly</changefreq>\n";
                           $xmlmap .= "     <priority>0.7</priority>\n";
                           $xmlmap .= "  </url>\n";
                        }
                     }
                  }
               }
            }
         }

         $xmlmap .= "</urlset>";
         
         fwrite($f, $xmlmap);
         fclose($f);
      
      }

      $content .= printTableSitemap($rezultCat, $rezultProd);


      include 'elems/layout.php';

      /*echo '<pre>';
      print_r($cat);
      print "</pre>";*/
   
   } else {
      header('Location: /admin/login.php'); die();
   }