<?php
$mysqli = new mysqli("localhost", "root", "", "php_exam_db");

// Récupérer le mot de passe actuel en clair
$result = $mysqli->query("SELECT id, password FROM User WHERE username = 'admin'");
$user = $result->fetch_assoc();

if ($user) {
    $plain_password = $user['password'];
    $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

    // Mettre à jour la base de données avec le mot de passe haché
    $stmt = $mysqli->prepare("UPDATE User SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $user['id']);
    $stmt->execute();

    echo "Mot de passe haché avec succès !";
} else {
    echo "Utilisateur admin non trouvé.";
}
?>
