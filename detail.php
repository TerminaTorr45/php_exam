<?php
session_start();
require_once 'includes/mail_config.php';

// Connexion à la base de données
$mysqli = new mysqli("localhost", "root", "", "php_exam_db");
if ($mysqli->connect_error) {
    die("Erreur de connexion : " . $mysqli->connect_error);
}

// Récupère l'ID de l'article depuis l'URL
$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Récupération des infos de l'article
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

        // Récupérer l'email de l'utilisateur
        $user_stmt = $mysqli->prepare("SELECT email, username FROM User WHERE id = ?");
        $user_stmt->bind_param("i", $user_id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        $user = $user_result->fetch_assoc();
        $user_stmt->close();

        // Envoyer l'email de confirmation
        $subject = "Article ajouté à votre panier - SNEAKER MARKET";
        $body = "
            <h2>Bonjour " . htmlspecialchars($user['username']) . ",</h2>
            <p>Vous avez ajouté l'article suivant à votre panier :</p>
            <div style='background: #f5f5f5; padding: 15px; border-radius: 8px; margin: 15px 0;'>
                <h3 style='margin: 0 0 10px 0;'>" . htmlspecialchars($article['name']) . "</h3>
                <p style='margin: 5px 0;'><strong>Prix :</strong> " . number_format($article['price'], 2) . " €</p>
                " . (!empty($article['description']) ? "<p style='margin: 5px 0;'><strong>Description :</strong> " . htmlspecialchars($article['description']) . "</p>" : "") . "
            </div>
            <p>Vous pouvez consulter votre panier en cliquant sur le lien suivant :</p>
            <p><a href='http://" . $_SERVER['HTTP_HOST'] . "/cart.php' style='background: #000; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Voir mon panier</a></p>
            <p>À bientôt sur SNEAKER MARKET !</p>
        ";

        sendMail($user['email'], $subject, $body);
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
    <link rel="stylesheet" href="styles/detail.css">
</head>
<body>
    <div class="container">
        <h1>🧾 Détail de l'article</h1>

        <?php if ($message): ?>
            <p><strong><?= htmlspecialchars($message) ?></strong></p>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if ($article['quantity'] > 0): ?>
                <form method="POST">
                    <button type="submit">🛒 Ajouter au panier</button>
                </form>
            <?php else: ?>
                <p style="color:red;"><strong>Rupture de stock.</strong></p>
            <?php endif; ?>

            <?php if ($_SESSION['user_id'] == $article['author_id']): ?>
                <p><a href="edit.php?id=<?= $article_id ?>">✏️ Modifier l'article</a></p>
            <?php endif; ?>
        <?php else: ?>
            <p><a href="login.php">Connectez-vous</a> pour ajouter cet article à votre panier.</p>
        <?php endif; ?>

        <h2><?= htmlspecialchars($article['name']) ?></h2>

        <?php if (!empty($article['image_url'])): ?>
            <img src="<?= htmlspecialchars($article['image_url']) ?>" alt="Image de l'article"><br>
        <?php endif; ?>

        <p><strong>Prix :</strong> <?= number_format($article['price'], 2) ?> €</p>
        <p><strong>Description :</strong> <?= nl2br(htmlspecialchars($article['description'])) ?></p>
        <p><strong>Auteur :</strong> <?= htmlspecialchars($article['author']) ?></p>
        <p><strong>Date de publication :</strong> <?= $article['published_at'] ?></p>
        <p><strong>Stock disponible :</strong> <?= $article['quantity'] ?></p>

        <p><a href="home.php">⬅️ Retour à l'accueil</a></p>
    </div>
</body>
</html>
