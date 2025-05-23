<?php
session_start();
include 'includes/auth.php';

// Vérifie que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$mysqli = new mysqli("localhost", "root", "root", "php_exam_db");
if ($mysqli->connect_error) {
    die("Erreur de connexion : " . $mysqli->connect_error);
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $image_url = trim($_POST['image_url']);
    $quantity = intval($_POST['quantity']);
    $author_id = $_SESSION['user_id'];

    if (!empty($name) && $price > 0 && $quantity >= 0) {
        // Insérer dans la table Article
        $stmt = $mysqli->prepare("INSERT INTO Article (name, description, price, author_id, image_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdiss", $name, $description, $price, $author_id, $image_url);
        $stmt->execute();

        $article_id = $stmt->insert_id;
        $stmt->close();

        // Insérer dans la table Stock
        $stmt2 = $mysqli->prepare("INSERT INTO Stock (article_id, quantity) VALUES (?, ?)");
        $stmt2->bind_param("ii", $article_id, $quantity);
        $stmt2->execute();
        $stmt2->close();

        $message = "✅ Article ajouté avec succès !";
    } else {
        $message = "❌ Merci de remplir tous les champs obligatoires.";
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Vendre un article</title>
</head>
<body>
    <h1>🛒 Mettre en vente un article</h1>
    <p><a href="home.php">⬅️ Retour à l'accueil</a></p>

    <?php if ($message): ?>
        <p><strong><?= htmlspecialchars($message) ?></strong></p>
    <?php endif; ?>

    <form method="POST">
        <label>Nom de l'article *</label><br>
        <input type="text" name="name" required><br><br>

        <label>Description</label><br>
        <textarea name="description"></textarea><br><br>

        <label>Prix (€) *</label><br>
        <input type="number" name="price" step="0.01" required><br><br>

        <label>Image (URL)</label><br>
        <input type="text" name="image_url"><br><br>

        <label>Quantité en stock *</label><br>
        <input type="number" name="quantity" required min="0"><br><br>

        <button type="submit">Mettre en vente</button>
    </form>
</body>
</html>
