<?php
session_start();
include 'includes/auth.php';

$mysqli = new mysqli("localhost", "root", "", "php_exam_db");
if ($mysqli->connect_error) {
    die("Erreur de connexion: " . $mysqli->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$is_admin = false;

// Vérifier si l'utilisateur est admin
$result = $mysqli->query("SELECT role FROM User WHERE id = $user_id");
if ($row = $result->fetch_assoc()) {
    $is_admin = ($row['role'] === 'admin');
}

// Récupérer l'ID de l'article à modifier
if (!isset($_GET['id'])) {
    echo "Article non spécifié.";
    exit;
}
$article_id = intval($_GET['id']);

// Récupérer l'article
$article = $mysqli->query("SELECT * FROM Article WHERE id = $article_id")->fetch_assoc();

if (!$article) {
    echo "Article introuvable.";
    exit;
}

// Vérification des droits
if ($article['author_id'] != $user_id && !$is_admin) {
    echo "Accès refusé. Vous n'avez pas le droit de modifier cet article.";
    exit;
}

// Suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $mysqli->query("DELETE FROM Article WHERE id = $article_id");
    $mysqli->query("DELETE FROM Stock WHERE article_id = $article_id");
    header("Location: /");
    exit;
}

// Modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $name = $mysqli->real_escape_string($_POST['name']);
    $description = $mysqli->real_escape_string($_POST['description']);
    $price = floatval($_POST['price']);
    $image_url = $mysqli->real_escape_string($_POST['image_url']);
    $stock = intval($_POST['stock']);

    // Mise à jour de l'article
    $mysqli->query("
        UPDATE Article 
        SET name = '$name', description = '$description', price = $price, image_url = '$image_url' 
        WHERE id = $article_id
    ");

    // Mise à jour du stock
    $stock_check = $mysqli->query("SELECT * FROM Stock WHERE article_id = $article_id");
    if ($stock_check->num_rows > 0) {
        $mysqli->query("UPDATE Stock SET quantity = $stock WHERE article_id = $article_id");
    } else {
        $mysqli->query("INSERT INTO Stock (article_id, quantity) VALUES ($article_id, $stock)");
    }

    $success_message = "Article mis à jour avec succès.";
    $article = $mysqli->query("SELECT * FROM Article WHERE id = $article_id")->fetch_assoc();
}

$stock_data = $mysqli->query("SELECT quantity FROM Stock WHERE article_id = $article_id")->fetch_assoc();
$current_stock = $stock_data ? $stock_data['quantity'] : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'article</title>
    <link rel="stylesheet" href="css/edit.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .success-message {
            text-align: center;
            color: #28a745;
            margin: 20px 0;
            padding: 10px;
            background-color: #d4edda;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Modifier l'article</h2>

        <?php if (isset($success_message)): ?>
            <div class="success-message"><?= $success_message ?></div>
        <?php endif; ?>

        <form method="POST" class="edit-form">
            <div class="form-group">
                <label>Nom :</label>
                <input type="text" name="name" value="<?= htmlspecialchars($article['name']) ?>" required>
            </div>

            <div class="form-group">
                <label>Description :</label>
                <textarea name="description"><?= htmlspecialchars($article['description']) ?></textarea>
            </div>

            <div class="form-group">
                <label>Prix :</label>
                <input type="number" name="price" step="0.01" value="<?= $article['price'] ?>" required>
            </div>

            <div class="form-group">
                <label>URL de l'image :</label>
                <input type="text" name="image_url" value="<?= htmlspecialchars($article['image_url']) ?>">
            </div>

            <div class="form-group">
                <label>Stock :</label>
                <input type="number" name="stock" value="<?= $current_stock ?>" required>
            </div>

            <div class="button-group">
                <button type="submit" name="update">Mettre à jour</button>
            </div>
        </form>

        <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet article ?')" class="delete-form">
            <button type="submit" name="delete">Supprimer l'article</button>
        </form>

        <div class="navigation-buttons">
            <a href="/" class="nav-button">Retour à l'accueil</a>
            <a href="/article.php?id=<?= $article_id ?>" class="nav-button">Voir l'article</a>
        </div>
    </div>
</body>
</html>