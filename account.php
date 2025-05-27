<?php
include 'includes/auth.php';

$mysqli = new mysqli("localhost", "root", "", "php_exam_db");
if ($mysqli->connect_error) {
    die("Erreur de connexion: " . $mysqli->connect_error);
}

$current_user_id = $_SESSION['user_id'] ?? null;
$viewed_user_id = $_GET['id'] ?? $current_user_id;

if (!$viewed_user_id) {
    echo "Vous devez être connecté pour accéder à cette page.";
    exit;
}

$viewed_user_id = intval($viewed_user_id);
$is_owner = $current_user_id && $current_user_id == $viewed_user_id;

// Récupération des infos utilisateur
$user_query = $mysqli->query("SELECT * FROM User WHERE id = $viewed_user_id");
if ($user_query->num_rows === 0) {
    echo "Utilisateur introuvable.";
    exit;
}
$user = $user_query->fetch_assoc();

// Mise à jour email ou mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_owner) {
    $email = isset($_POST['email']) ? $mysqli->real_escape_string($_POST['email']) : '';
    $password = isset($_POST['password']) ? $mysqli->real_escape_string($_POST['password']) : '';
    if (!empty($email)) {
        $mysqli->query("UPDATE User SET email = '$email' WHERE id = $current_user_id");
    }
    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $mysqli->query("UPDATE User SET password = '$hashed' WHERE id = $current_user_id");
    }
    echo "<p>Informations mises à jour.</p>";
    $user = $mysqli->query("SELECT * FROM User WHERE id = $viewed_user_id")->fetch_assoc();
}

// Ajouter de l'argent au solde
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_funds']) && $is_owner) {
    $amount = floatval($_POST['amount']);
    if ($amount > 0) {
        $mysqli->query("UPDATE User SET balance = balance + $amount WHERE id = $current_user_id");
        echo "<p>Argent ajouté !</p>";
        $user = $mysqli->query("SELECT * FROM User WHERE id = $viewed_user_id")->fetch_assoc();
    }
}

// Récupérer les articles postés par cet utilisateur
$articles = $mysqli->query("SELECT * FROM Article WHERE author_id = $viewed_user_id ORDER BY published_at DESC");

// Récupérer les factures si c'est le compte de l'utilisateur actuel
$invoices = $is_owner ? $mysqli->query("SELECT * FROM Invoice WHERE user_id = $current_user_id") : null;

// Articles achetés (via les factures)
$purchases = $is_owner ? $mysqli->query(
    "SELECT A.* FROM Article A
     JOIN Cart C ON C.article_id = A.id
     JOIN Invoice I ON I.user_id = C.user_id
     WHERE C.user_id = $current_user_id"
) : null;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - <?= htmlspecialchars($user['username']) ?></title>
    <link rel="stylesheet" href="styles/account.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <h2>Profil de <?= htmlspecialchars($user['username']) ?></h2>
            <div class="profile-info">
                <div class="info-card">
                    <p>Email : <?= htmlspecialchars($user['email']) ?></p>
                </div>
                <div class="info-card">
                    <p>Solde : <span class="balance"><?= $user['balance'] ?> €</span></p>
                </div>
            </div>
        </div>

        <?php if ($is_owner): ?>
        <form method="POST">
            <h3>Modifier mes informations</h3>
            <label>Email : <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>"></label>
            <label>Mot de passe : <input type="password" name="password"></label>
            <button type="submit">Mettre à jour</button>
        </form>

        <form method="POST">
            <h3>Ajouter de l'argent</h3>
            <input type="number" name="amount" step="0.01" placeholder="Montant">
            <button type="submit" name="add_funds">Ajouter</button>
        </form>
        <?php endif; ?>

        <h3>Articles postés par <?= htmlspecialchars($user['username']) ?></h3>
        <div class="articles-grid">
            <?php while ($row = $articles->fetch_assoc()): ?>
                <div class="article-card">
                    <a href="/detail.php?id=<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></a>
                </div>
            <?php endwhile; ?>
        </div>

        <?php if ($is_owner): ?>
            <h3>Mes achats</h3>
            <div class="articles-grid">
                <?php while ($purchases && $row = $purchases->fetch_assoc()): ?>
                    <div class="article-card">
                        <p><?= htmlspecialchars($row['name']) ?> - <?= $row['price'] ?> €</p>
                    </div>
                <?php endwhile; ?>
            </div>

            <h3>Mes factures</h3>
            <div class="articles-grid">
                <?php while ($invoices && $row = $invoices->fetch_assoc()): ?>
                    <div class="article-card">
                        <p>Facture #<?= $row['id'] ?> - <?= $row['amount'] ?> € - <?= $row['transaction_date'] ?></p>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
