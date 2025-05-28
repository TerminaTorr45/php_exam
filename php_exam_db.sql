-- Supprimer les tables si elles existent déjà (ordre inverse des dépendances)
DROP TABLE IF EXISTS Invoice;
DROP TABLE IF EXISTS Stock;
DROP TABLE IF EXISTS Cart;
DROP TABLE IF EXISTS Article;
DROP TABLE IF EXISTS User;

-- Table User
CREATE TABLE User (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    balance DECIMAL(10,2) DEFAULT 0.00,
    profile_picture VARCHAR(255),
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table Article
CREATE TABLE Article (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    author_id INT,
    image_url VARCHAR(255),
    FOREIGN KEY (author_id) REFERENCES User(id) ON DELETE SET NULL
);

-- Table Cart
CREATE TABLE Cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    article_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES User(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES Article(id) ON DELETE CASCADE
);

-- Table Stock
CREATE TABLE Stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    quantity INT NOT NULL,
    FOREIGN KEY (article_id) REFERENCES Article(id) ON DELETE CASCADE
);

-- Table Invoice
CREATE TABLE Invoice (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    amount DECIMAL(10,2) NOT NULL,
    billing_address VARCHAR(255),
    billing_city VARCHAR(100),
    billing_postal_code VARCHAR(20),
    FOREIGN KEY (user_id) REFERENCES User(id) ON DELETE CASCADE
);

