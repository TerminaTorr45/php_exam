<?php
session_start();

// Connexion à la base de données
$mysqli = new mysqli("localhost", "root", "root", "php_exam_db");
if ($mysqli->connect_error) {
    die("Erreur de connexion : " . $mysqli->connect_error);
}

// Récupère l'ID de l'article depuis l'URL
$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Récupération des infos de l’article
$stmt = $mysqli->prepare("
    SELECT A.*, U.username AS author, S.quantity 
    FROM Article A 
    LEFT JOIN User U ON A.author_id = U.id 
    LEFT JOIN Stock S ON A.id = S.article_id 
    WHERE A.id = ?
");
$stmt->bind_param("i", $article_id);
$stmt->execute();
$result = $stmt->get_result();
$article = $result->fetch_assoc();
$stmt->close();

if (!$article) {
    echo "❌ Article introuvable.";
    exit;
}

// Ajout au panier
$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Vérifie que l'article n'est pas déjà dans le panier
    $check = $mysqli->prepare("SELECT id FROM Cart WHERE user_id = ? AND article_id = ?");
    $check->bind_param("ii", $user_id, $article_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        $insert = $mysqli->prepare("INSERT INTO Cart (user_id, article_id) VALUES (?, ?)");
        $insert->bind_param("ii", $user_id, $article_id);
        $insert->execute();
        $insert->close();
        $message = "✅ Article ajouté au panier.";
    } else {
        $message = "ℹ️ Article déjà dans votre panier.";
    }

    $check->close();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détail de l'article</title>
</head>
<body>
    <h1>🧾 Détail de l'article</h1>
    <p><a href="home.php">⬅️ Retour à l'accueil</a></p>

    <?php if ($message): ?>
        <p><strong><?= htmlspecialchars($message) ?></strong></p>
    <?php endif; ?>

    <h2><?= htmlspecialchars($article['name']) ?></h2>

    <?php if (!empty($article['image_url'])): ?>
        <img src="<?= htmlspecialchars($article['image_url']) ?>" alt="Image" width="300"><br>
    <?php endif; ?>

    <p><strong>Prix :</strong> <?= number_format($article['price'], 2) ?> €</p>
    <p><strong>Description :</strong> <?= nl2br(htmlspecialchars($article['description'])) ?></p>
    <p><strong>Auteur :</strong> <?= htmlspecialchars($article['author']) ?></p>
    <p><strong>Date de publication :</strong> <?= $article['published_at'] ?></p>
    <p><strong>Stock disponible :</strong> <?= $article['quantity'] ?></p>

    <?php if (isset($_SESSION['user_id'])): ?>
        <?php if ($article['quantity'] > 0): ?>
            <form method="POST">
                <button type="submit">🛒 Ajouter au panier</button>
            </form>
        <?php else: ?>
            <p style="color:red;"><strong>Rupture de stock.</strong></p>
        <?php endif; ?>
    <?php else: ?>
        <p><a href="login.php">Connectez-vous</a> pour ajouter cet article à votre panier.</p>
    <?php endif; ?>
</body>
</html>
