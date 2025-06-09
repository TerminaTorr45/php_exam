# 🛍️ E-Commerce Platform

Une plateforme e-commerce moderne et robuste développée en PHP, offrant une expérience utilisateur complète avec gestion des utilisateurs, panier d'achat, et administration...

## 🌟 Fonctionnalités

- 👤 **Gestion des utilisateurs**
  - Inscription et connexion
  - Profil utilisateur personnalisable
  - Récupération de mot de passe
  - Gestion des rôles (utilisateur/admin)

- 🛒 **Fonctionnalités e-commerce**
  - Catalogue de produits
  - Panier d'achat
  - Système de paiement
  - Historique des commandes

- 👨‍💼 **Administration**
  - Gestion des produits
  - Gestion des stocks
  - Suivi des ventes
  - Gestion des utilisateurs

## 🛠️ Technologies utilisées

- PHP
- MySQL
- PHPMailer
- HTML/CSS
- JavaScript : (Pour PHP-Mailer)

## 📋 Prérequis

- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Composer

## 🚀 Installation

1. Clonez le repository :
```bash
git clone [https://github.com/TerminaTorr45/php_exam.git]
```

2. Installez les dépendances :
```bash
composer install 
```
ou

```bash
npm i 
```

3. Configurez la base de données :
- Importez le fichier `php_exam_db.sql` dans votre base de données MySQL
- Configurez les paramètres de connexion dans le fichier de configuration

4. Configurez le serveur web :
- Assurez-vous que le document root pointe vers le répertoire du projet
- Configurez les permissions appropriées

5. Lancer le code en local :
```bash
php -S localhost:8000
```
PS : Lors de la création de votre compte nous vous conseillons de mettre votre addresse-mail pour être tenue informé des nouveautés 👍

## 📁 Structure du projet

```
├── admin.php              # Interface d'administration
├── cart/                  # Gestion du panier
├── css/                   # Styles CSS
├── includes/             # Fonctions et configurations
├── styles/               # Assets supplémentaires
├── vendor/               # Dépendances Composer
├── *.php                 # Pages principales
└── php_exam_db.sql       # Structure de la base de données
```

## 🔒 Sécurité

- Mots de passe hashés
- Protection contre les injections SQL
- Validation des entrées utilisateur
- Gestion sécurisée des sessions

## 📧 Contact

Pour toute question ou suggestion, n'hésitez pas à nous contacter !
