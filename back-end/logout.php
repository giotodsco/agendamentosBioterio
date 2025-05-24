<?php
// acexx/back-end/logout.php
session_start();
session_destroy();
header("Location: ../front-end/pag_login_usuario.php");
exit();
?>