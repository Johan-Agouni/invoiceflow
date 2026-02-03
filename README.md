# InvoiceFlow

Système de facturation automatisé pour freelances et TPE, développé en PHP natif avec une architecture MVC.

## Fonctionnalités

### Gestion des clients
- Création et modification de fiches clients
- Historique des factures par client
- Coordonnées complètes (adresse, TVA, contact)

### Devis
- Création de devis avec lignes personnalisables
- Conversion automatique devis → facture
- Statuts : brouillon, envoyé, accepté, refusé, expiré
- Export PDF professionnel

### Factures
- Numérotation automatique conforme
- Calcul automatique TVA (taux personnalisable)
- Génération PDF avec mentions légales
- Statuts : brouillon, en attente, payée, en retard
- Relances automatiques par email

### Dashboard
- Chiffre d'affaires mensuel/annuel
- Graphiques d'évolution
- Factures en retard
- Actions rapides

### Paramètres
- Informations entreprise (SIRET, TVA)
- Logo personnalisé
- Coordonnées bancaires (IBAN/BIC)
- Mentions légales factures

## Stack technique

| Composant | Technologie |
|-----------|-------------|
| Backend | PHP 8.2+ (natif, PDO) |
| Base de données | MySQL 8.0 |
| Frontend | HTML5, Tailwind CSS, JavaScript |
| PDF | Dompdf |
| Email | PHPMailer |
| Graphiques | Chart.js |
| Conteneurisation | Docker, Docker Compose |

## Architecture

```
invoiceflow/
├── app/
│   ├── Controllers/      # Contrôleurs MVC
│   ├── Models/           # Modèles de données
│   ├── Views/            # Templates PHP
│   ├── Middleware/       # Authentification, CSRF
│   ├── Services/         # PDF, Mail
│   ├── Database.php      # Abstraction PDO
│   ├── Router.php        # Routage
│   ├── Controller.php    # Contrôleur de base
│   └── Model.php         # Modèle de base
├── config/               # Configuration
├── database/migrations/  # Schéma SQL
├── docker/               # Dockerfile
├── public/               # Point d'entrée + assets
├── routes/               # Définition des routes
├── storage/              # Logs, cache, PDFs
└── tests/                # Tests unitaires
```

## Installation

### Avec Docker (recommandé)

```bash
# Cloner le repository
git clone https://github.com/Johan-Agouni/invoiceflow.git
cd invoiceflow

# Copier la configuration
cp .env.example .env

# Lancer les conteneurs
docker-compose up -d

# Accéder à l'application
# App: http://localhost:8080
# phpMyAdmin: http://localhost:8081
```

### Sans Docker

```bash
# Prérequis: PHP 8.2+, MySQL 8.0, Composer

# Cloner le repository
git clone https://github.com/Johan-Agouni/invoiceflow.git
cd invoiceflow

# Installer les dépendances
composer install

# Configurer l'environnement
cp .env.example .env
# Éditer .env avec vos paramètres DB

# Importer la base de données
mysql -u root -p invoiceflow < database/migrations/init.sql

# Lancer le serveur de développement
php -S localhost:8080 -t public
```

## Compte de démonstration

```
Email: demo@invoiceflow.test
Mot de passe: password123
```

## Sécurité

- **Authentification** : Sessions PHP sécurisées, bcrypt (cost 12)
- **CSRF** : Token sur tous les formulaires POST
- **SQL Injection** : Prepared statements (PDO)
- **XSS** : Échappement htmlspecialchars
- **Headers** : X-Content-Type-Options, X-Frame-Options, X-XSS-Protection
- **Passwords** : Hachage bcrypt, reset token avec expiration

## Configuration email (SMTP)

Éditer `.env` :

```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre@email.com
MAIL_PASSWORD=votre_mot_de_passe_app
MAIL_FROM_ADDRESS=facturation@votreentreprise.com
MAIL_FROM_NAME=VotreEntreprise
```

## Screenshots

### Dashboard
![Dashboard](screenshots/dashboard.png)

### Création de facture
![Invoice](screenshots/invoice.png)

### PDF généré
![PDF](screenshots/pdf.png)

## Roadmap

- [ ] Export comptable (FEC)
- [ ] Multi-devises
- [ ] API REST
- [ ] Paiement en ligne (Stripe)
- [ ] Récurrence de factures
- [ ] Application mobile

## Licence

MIT License - voir [LICENSE](LICENSE)

## Auteur

**Johan Agouni**
- GitHub: [@Johan-Agouni](https://github.com/Johan-Agouni)
- Email: agouni.johan@proton.me
