<?php
include 'includes/db.php';

if ($mysqli->connect_error) {
    die("Erreur de connexion : " . $mysqli->connect_error);
}
echo "Connexion réussie à la base de données !";
?>
