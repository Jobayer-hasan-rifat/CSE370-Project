<?php
session_start();
session_destroy();
header("Location: /CSE370-Project/index.php");
exit();
?>
