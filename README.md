# 🛍️ E-Commerce Platform

Une plateforme e-commerce moderne et robuste développée en PHP, offrant une expérience utilisateur complète avec gestion des utilisateurs, panier d'achat, et administration.

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
- JavaScript

## 📋 Prérequis

- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Composer

## 🚀 Installation

1. Clonez le repository :
```bash
git clone [URL_DU_REPO]
```

2. Installez les dépendances :
```bash
composer install
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

## 📝 Licence

Ce projet est sous licence [insérer la licence appropriée]

## 👥 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à :
1. Fork le projet
2. Créer une branche pour votre fonctionnalité
3. Commiter vos changements
4. Pousser vers la branche
5. Ouvrir une Pull Request

## 📧 Contact

Pour toute question ou suggestion, n'hésitez pas à nous contacter à [insérer l'email de contact]