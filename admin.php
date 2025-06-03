<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "php_exam_db");
if ($mysqli->connect_error) die("Erreur de connexion: " . $mysqli->connect_error);

$current_user_id = $_SESSION['user_id'] ?? null;
if (!$current_user_id) die("Accès refusé");

$stmt = $mysqli->prepare("SELECT * FROM User WHERE id = ?");
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$user_query = $stmt->get_result();
if ($user_query->num_rows === 0) die("Accès refusé");
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

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SNEAKER MARKET</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        .admin-header {
            background: #fff;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .admin-header h1 {
            color: #000;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .admin-nav {
            display: flex;
            gap: 1rem;
        }

        .admin-nav a {
            color: #666;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .admin-nav a:hover {
            background: #f0f0f0;
            color: #000;
        }

        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .admin-section {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .admin-section h2 {
            padding: 1rem;
            background: #000;
            color: #fff;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #666;
            border-bottom: 2px solid #eee;
        }

        .admin-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }

        .admin-table tr:hover {
            background: #f8f9fa;
        }

        .admin-input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .admin-input:focus {
            outline: none;
            border-color: #000;
        }

        .admin-select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
            background: #fff;
        }

        .admin-button {
            background: #000;
            color: #fff;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .admin-button:hover {
            background: #333;
        }

        .admin-button.delete {
            background: #dc3545;
        }

        .admin-button.delete:hover {
            background: #c82333;
        }

        .admin-actions {
            display: flex;
            gap: 0.5rem;
        }

        @media (max-width: 768px) {
            .admin-header {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }

            .admin-nav {
                width: 100%;
                justify-content: center;
            }

            .admin-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>SNEAKER MARKET - Admin Dashboard</h1>
        <nav class="admin-nav">
            <a href="home.php">Retour au site</a>
            <a href="logout.php">Déconnexion</a>
        </nav>
    </header>

    <div class="admin-container">
        <section class="admin-section">
            <h2>Gestion des utilisateurs</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Mot de passe</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $users->fetch_assoc()): ?>
                    <tr>
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <input type="hidden" name="update_user" value="1">
                            <td><?= $row['id'] ?></td>
                            <td><input type="text" name="username" value="<?= htmlspecialchars($row['username']) ?>" class="admin-input"></td>
                            <td><input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>" class="admin-input"></td>
                            <td>
                                <select name="role" class="admin-select">
                                    <option value="user" <?= $row['role'] === 'user' ? 'selected' : '' ?>>user</option>
                                    <option value="admin" <?= $row['role'] === 'admin' ? 'selected' : '' ?>>admin</option>
                                </select>
                            </td>
                            <td><input type="password" name="password" placeholder="Nouveau mot de passe" class="admin-input"></td>
                            <td class="admin-actions">
                                <button type="submit" class="admin-button">💾</button>
                                <?php if ($row['id'] != $current_user_id): ?>
                                    <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Supprimer cet utilisateur ?');" class="admin-button delete">🗑️</a>
                                <?php endif; ?>
                            </td>
                        </form>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>

        <?php
        // Gestion des articles
        $articles = $mysqli->query("SELECT A.*, U.username FROM Article A LEFT JOIN User U ON A.author_id = U.id ORDER BY published_at DESC");

        // Suppression d'article
        if (isset($_GET['delete_article'])) {
            $article_id = intval($_GET['delete_article']);
            $mysqli->query("DELETE FROM Article WHERE id = $article_id");
        }
        ?>

        <section class="admin-section">
            <h2>Gestion des articles</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Auteur</th>
                        <th>Prix</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $articles->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= number_format($row['price'], 2, ',', ' ') ?> €</td>
                        <td><?= date('d/m/Y H:i', strtotime($row['published_at'])) ?></td>
                        <td class="admin-actions">
                            <a href="edit.php?id=<?= $row['id'] ?>" class="admin-button">✏️</a>
                            <a href="?delete_article=<?= $row['id'] ?>" onclick="return confirm('Supprimer cet article ?');" class="admin-button delete">🗑️</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>
    </div>
</body>
</html>
