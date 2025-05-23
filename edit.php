<?php
session_start();
include 'includes/auth.php';

$mysqli = new mysqli("localhost", "root", "ton_mot_de_passe", "php_exam_db");
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

    echo "<p>Article mis à jour.</p>";
    $article = $mysqli->query("SELECT * FROM Article WHERE id = $article_id")->fetch_assoc();
}

$stock_data = $mysqli->query("SELECT quantity FROM Stock WHERE article_id = $article_id")->fetch_assoc();
$current_stock = $stock_data ? $stock_data['quantity'] : 0;
?>

<h2>Modifier l'article</h2>

<form method="POST">
    <label>Nom :</label><br>
    <input type="text" name="name" value="<?= htmlspecialchars($article['name']) ?>" required><br>

    <label>Description :</label><br>
    <textarea name="description"><?= htmlspecialchars($article['description']) ?></textarea><br>

    <label>Prix :</label><br>
    <input type="number" name="price" step="0.01" value="<?= $article['price'] ?>" required><br>

    <label>URL de l'image :</label><br>
    <input type="text" name="image_url" value="<?= htmlspecialchars($article['image_url']) ?>"><br>

    <label>Stock :</label><br>
    <input type="number" name="stock" value="<?= $current_stock ?>" required><br><br>

    <button type="submit" name="update">Mettre à jour</button>
</form>

<form method="POST" onsubmit="return confirm('Supprimer cet article ?')">
    <button type="submit" name="delete" style="margin-top:20px; color:red;">Supprimer l'article</button>
</form>