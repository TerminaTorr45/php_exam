<?php
// admin/users.php
session_start();
$mysqli = new mysqli("localhost", "root", "", "php_exam_db");
if ($mysqli->connect_error) die("Erreur de connexion: " . $mysqli->connect_error);

$current_user_id = $_SESSION['user_id'] ?? null;
$user_query = $mysqli->query("SELECT * FROM User WHERE id = $current_user_id");
if (!$current_user_id || $user_query->num_rows === 0) die("Accès refusé");
$user = $user_query->fetch_assoc();
if ($user['role'] !== 'admin') die("Accès refusé");

if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    if ($delete_id != $current_user_id) {
        $mysqli->query("DELETE FROM User WHERE id = $delete_id");
    }
}

$users = $mysqli->query("SELECT * FROM User ORDER BY created_at DESC");
?>
<h1>Gestion des utilisateurs</h1>
<table border="1">
<tr><th>ID</th><th>Username</th><th>Email</th><th>Rôle</th><th>Actions</th></tr>
<?php while ($row = $users->fetch_assoc()): ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= htmlspecialchars($row['username']) ?></td>
    <td><?= htmlspecialchars($row['email']) ?></td>
    <td><?= $row['role'] ?></td>
    <td>
        <a href="/account.php?id=<?= $row['id'] ?>">Voir</a>
        <?php if ($row['id'] != $current_user_id): ?> |
        <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Supprimer cet utilisateur ?');">Supprimer</a>
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>
</table>

<?php
// admin/articles.php
$articles = $mysqli->query("SELECT A.*, U.username FROM Article A LEFT JOIN User U ON A.author_id = U.id ORDER BY published_at DESC");
?>
<h1>Gestion des articles</h1>
<table border="1">
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
        <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Supprimer cet article ?');">Supprimer</a>
    </td>
</tr>
<?php endwhile; ?>
</table>
