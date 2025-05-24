<?php
// acexx/back-end/logout.php
session_start();
session_destroy();
header("Location: pag_adm.php");
exit();
?>