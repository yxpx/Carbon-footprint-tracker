<?php
session_start();
session_destroy();
header("Location: /Carbon-footprint-tracker/index.php");
exit();
?>
