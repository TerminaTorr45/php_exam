<?php
session_start();

// Connexion à la base
$mysqli = new mysqli("localhost", "root", "", "php_exam_db");
if ($mysqli->connect_error) {
    die("Erreur de connexion : " . $mysqli->connect_error);
}

// Vérifie si l'utilisateur est connecté
$is_admin = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_query = $mysqli->prepare("SELECT role FROM User WHERE id = ?");
    $user_query->bind_param("i", $user_id);
    $user_query->execute();
    $user_result = $user_query->get_result();
    if ($user_row = $user_result->fetch_assoc()) {
        $is_admin = ($user_row['role'] === 'admin');
    }
}

// Récupère les articles
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = "
    SELECT A.id, A.name, A.description, A.price, A.published_at, A.image_url, U.username AS author
    FROM Article A
    LEFT JOIN User U ON A.author_id = U.id
    WHERE 1=1
";
if (!empty($search)) {
    $search_term = '%' . $mysqli->real_escape_string($search) . '%';
    $query .= " AND (A.name LIKE ? OR A.description LIKE ?)";
}
$query .= " ORDER BY A.published_at DESC";

$stmt = $mysqli->prepare($query);
if (!empty($search)) {
    $stmt->bind_param("ss", $search_term, $search_term);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil - Articles</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Helvetica Neue', Arial, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        .header {
            background-color: #000;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .header a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border: 1px solid white;
            border-radius: 4px;
            margin-left: 0.5rem;
            transition: all 0.3s ease;
        }

        .header a:hover {
            background-color: white;
            color: black;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            padding: 1rem;
        }

        .product-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            background-color: #f8f8f8;
        }

        .product-info {
            padding: 1.5rem;
        }

        .product-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .product-price {
            font-size: 1.4rem;
            font-weight: 700;
            color: #000;
            margin: 0.5rem 0;
        }

        .product-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .product-meta {
            font-size: 0.8rem;
            color: #888;
            border-top: 1px solid #eee;
            padding-top: 1rem;
        }

        .no-products {
            text-align: center;
            padding: 3rem;
            font-size: 1.2rem;
            color: #666;
        }

        .search-form {
            display: flex;
            gap: 0.5rem;
            margin: 0 1rem;
        }

        .search-input {
            padding: 0.5rem;
            border: none;
            border-radius: 4px;
            width: 300px;
            font-size: 1rem;
        }

        .search-button {
            padding: 0.5rem 1rem;
            background-color: white;
            color: black;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-button:hover {
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
<header class="header">
    <h1>🏠 SNEAKER MARKET</h1>
    <form class="search-form" method="GET" action="home.php">
        <input type="text" name="search" class="search-input" placeholder="Rechercher un article..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="search-button">🔍</button>
    </form>
    <div>
        <a href="sell.php">Vendre</a>
        <a href="account.php">Mon compte</a>
        <a href="cart.php">🛒 Panier</a> 
        <?php if ($is_admin): ?>
            <a href="admin.php" style="background-color: crimson; color: white;">Admin</a>
        <?php endif; ?>
        <a href="logout.php">Se déconnecter</a>
    </div>
</header>


<div class="container">
    <div class="products-grid">
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<a href='detail.php?id=" . $row['id'] . "' class='product-card'>";
                if (!empty($row['image_url'])) {
                    echo "<img src='" . htmlspecialchars($row['image_url']) . "' alt='" . htmlspecialchars($row['name']) . "' class='product-image'>";
                } else {
                    echo "<div class='product-image'></div>";
                }
                echo "<div class='product-info'>";
                echo "<h2 class='product-name'>" . htmlspecialchars($row['name']) . "</h2>";
                echo "<div class='product-price'>" . number_format($row['price'], 2) . " €</div>";
                echo "<p class='product-description'>" . nl2br(htmlspecialchars($row['description'])) . "</p>";
                echo "<div class='product-meta'>";
                echo "Publié par " . htmlspecialchars($row['author']) . " le " . $row['published_at'];
                echo "</div>";
                echo "</div>";
                echo "</a>";
            }
        } else {
            echo "<div class='no-products'>Aucun article en vente.</div>";
        }

        $mysqli->close();
        ?>
    </div>
</div>
</body>
</html>
