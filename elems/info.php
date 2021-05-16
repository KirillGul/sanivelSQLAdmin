<?php
//var_dump ($_SESSION);

if (isset($_SESSION['info'])) {
   $status = $_SESSION['info']['status'];
   $msg = $_SESSION['info']['msg'];
   echo "<p class=\"$status\">$msg</p>";

   unset($_SESSION['info']);
}

if (isset($_SESSION['timeScript'])) {
   $msg = $_SESSION['timeScript'];
   echo "<p>$msg</p>";

   unset($_SESSION['timeScript']);
}