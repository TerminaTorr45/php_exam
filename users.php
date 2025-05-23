<?php
// Connexion à la base de données
$mysqli = new mysqli("localhost", "root", "ton_mot_de_passe", "php_exam_db");
if ($mysqli->connect_error) {
    die("Erreur de connexion : " . $mysqli->connect_error);
}

// Requête SQL pour récupérer les utilisateurs
$sql = "SELECT id, username, email, balance, role, created_at FROM User";
$result = $mysqli->query($sql);

// Affichage HTML
echo "<h1>Liste des utilisateurs</h1>";

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Nom d'utilisateur</th><th>Email</th><th>Solde</th><th>Rôle</th><th>Créé le</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['username']}</td>
                <td>{$row['email']}</td>
                <td>{$row['balance']}</td>
                <td>{$row['role']}</td>
                <td>{$row['created_at']}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "Aucun utilisateur trouvé.";
}

// Fermeture de la connexion
$mysqli->close();
?>
