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

<h2>Profil de <?= htmlspecialchars($user['username']) ?></h2>
<p>Email : <?= htmlspecialchars($user['email']) ?></p>
<p>Solde : <?= $user['balance'] ?> €</p>

<?php if ($is_owner): ?>
<form method="POST">
    <h3>Modifier mes informations</h3>
    <label>Email : <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>"></label><br>
    <label>Mot de passe : <input type="password" name="password"></label><br>
    <button type="submit">Mettre à jour</button>
</form>
<br>
<form method="POST">
    <h3>Ajouter de l'argent</h3>
    <input type="number" name="amount" step="0.01" placeholder="Montant">
    <button type="submit" name="add_funds">Ajouter</button>
</form>
<?php endif; ?>

<h3>Articles postés par <?= htmlspecialchars($user['username']) ?></h3>
<ul>
<?php while ($row = $articles->fetch_assoc()): ?>
    <li><a href="/detail.php?id=<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></a></li>
<?php endwhile; ?>
</ul>

<?php if ($is_owner): ?>
    <h3>Mes achats</h3>
    <ul>
    <?php while ($purchases && $row = $purchases->fetch_assoc()): ?>
        <li><?= htmlspecialchars($row['name']) ?> - <?= $row['price'] ?> €</li>
    <?php endwhile; ?>
    </ul>

    <h3>Mes factures</h3>
    <ul>
    <?php while ($invoices && $row = $invoices->fetch_assoc()): ?>
        <li>Facture #<?= $row['id'] ?> - <?= $row['amount'] ?> € - <?= $row['transaction_date'] ?></li>
    <?php endwhile; ?>
    </ul>
<?php endif; ?>
