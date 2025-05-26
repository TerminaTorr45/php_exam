<?php
session_start();

// Connexion à la base
$mysqli = new mysqli("localhost", "root", "", "php_exam_db");
if ($mysqli->connect_error) {
    die("Erreur de connexion : " . $mysqli->connect_error);
}

// Requête pour récupérer les articles et leur auteur
$query = "
    SELECT A.id, A.name, A.description, A.price, A.published_at, A.image_url, U.username AS author
    FROM Article A
    LEFT JOIN User U ON A.author_id = U.id
    ORDER BY A.published_at DESC
";
$result = $mysqli->query($query);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil - Articles</title>
</head>
<body>
    <h1>🏠 Accueil</h1>
    <p><a href="logout.php">Se déconnecter</a></p>
    <hr>

    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<div style='border:1px solid #ccc; padding:10px; margin:10px 0;'>";
            echo "<h2>" . htmlspecialchars($row['name']) . "</h2>";
            if (!empty($row['image_url'])) {
                echo "<img src='" . htmlspecialchars($row['image_url']) . "' alt='Image' width='200'><br>";
            }
            echo "<p><strong>Prix:</strong> " . number_format($row['price'], 2) . " €</p>";
            echo "<p><strong>Description:</strong> " . nl2br(htmlspecialchars($row['description'])) . "</p>";
            echo "<p><strong>Publié par:</strong> " . htmlspecialchars($row['author']) . " le " . $row['published_at'] . "</p>";
            echo "</div>";
        }
    } else {
        echo "<p>Aucun article en vente.</p>";
    }

    $mysqli->close();
    ?>
</body>
</html>
