<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "php_exam_db");
if ($mysqli->connect_error) die("Erreur de connexion: " . $mysqli->connect_error);

$current_user_id = $_SESSION['user_id'] ?? null;
$user_query = $mysqli->query("SELECT * FROM User WHERE id = $current_user_id");
if (!$current_user_id || $user_query->num_rows === 0) die("Accès refusé");
$user = $user_query->fetch_assoc();
if ($user['role'] !== 'admin') die("Accès refusé");

// Suppression d'utilisateur
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    if ($delete_id != $current_user_id) {
        $mysqli->query("DELETE FROM User WHERE id = $delete_id");
    }
}

// Mise à jour d'utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $id = intval($_POST['id']);
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;

    if ($password) {
        $stmt = $mysqli->prepare("UPDATE User SET username = ?, email = ?, role = ?, password = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $username, $email, $role, $password, $id);
    } else {
        $stmt = $mysqli->prepare("UPDATE User SET username = ?, email = ?, role = ? WHERE id = ?");
        $stmt->bind_param("sssi", $username, $email, $role, $id);
    }
    $stmt->execute();
}

$users = $mysqli->query("SELECT * FROM User ORDER BY created_at DESC");
?>

<h1>Gestion des utilisateurs</h1>
<table border="1" cellpadding="5">
<tr><th>ID</th><th>Username</th><th>Email</th><th>Rôle</th><th>Mot de passe</th><th>Actions</th></tr>

<?php while ($row = $users->fetch_assoc()): ?>
<tr>
    <form method="POST">
        <input type="hidden" name="id" value="<?= $row['id'] ?>">
        <input type="hidden" name="update_user" value="1">
        <td><?= $row['id'] ?></td>
        <td><input type="text" name="username" value="<?= htmlspecialchars($row['username']) ?>"></td>
        <td><input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>"></td>
        <td>
            <select name="role">
                <option value="user" <?= $row['role'] === 'user' ? 'selected' : '' ?>>user</option>
                <option value="admin" <?= $row['role'] === 'admin' ? 'selected' : '' ?>>admin</option>
            </select>
        </td>
        <td><input type="password" name="password" placeholder="Nouveau mot de passe"></td>
        <td>
            <button type="submit">💾 Modifier</button>
            <?php if ($row['id'] != $current_user_id): ?>
                <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Supprimer cet utilisateur ?');">🗑️ Supprimer</a>
            <?php endif; ?>
        </td>
    </form>
</tr>
<?php endwhile; ?>
</table>

<?php
// Gestion des articles
$articles = $mysqli->query("SELECT A.*, U.username FROM Article A LEFT JOIN User U ON A.author_id = U.id ORDER BY published_at DESC");

// Suppression d'article
if (isset($_GET['delete_article'])) {
    $article_id = intval($_GET['delete_article']);
    $mysqli->query("DELETE FROM Article WHERE id = $article_id");
}
?>

<h1>Gestion des articles</h1>
<table border="1" cellpadding="5">
<tr><th>ID</th><th>Nom</th><th>Auteur</th><th>Prix</th><th>Date</th><th>Actions</th></tr>
<?php while ($row = $articles->fetch_assoc()): ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td><?= htmlspecialchars($row['username']) ?></td>
    <td><?= $row['price'] ?> €</td>
    <td><?= $row['published_at'] ?></td>
    <td>
        <a href="/detail.php?id=<?= $row['id'] ?>">Voir</a> |
        <a href="/edit.php?id=<?= $row['id'] ?>">Modifier</a> |
        <a href="?delete_article=<?= $row['id'] ?>" onclick="return confirm('Supprimer cet article ?');">🗑️ Supprimer</a>
    </td>
</tr>
<?php endwhile; ?>
</table>
