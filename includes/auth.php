<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    // Redirige vers login si l'utilisateur n'est pas connecté
    header("Location: /login.php");
    exit();
}
?>
