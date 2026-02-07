<?php
require_once 'includes/session.php';

$_SESSION = [];
session_destroy();

header("Location:../admin/login.php");
exit;
