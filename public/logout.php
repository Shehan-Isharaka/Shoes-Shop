<?php
session_start();
unset($_SESSION['customer']);
session_destroy();
header("Location: index.php");
exit;
