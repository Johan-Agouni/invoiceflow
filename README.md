# InvoiceFlow

<p align="center">
  <a href="https://github.com/Johan-Agouni/invoiceflow/actions/workflows/ci.yml"><img src="https://github.com/Johan-Agouni/invoiceflow/actions/workflows/ci.yml/badge.svg" alt="CI Pipeline"></a>
  <a href="https://codecov.io/gh/Johan-Agouni/invoiceflow"><img src="https://codecov.io/gh/Johan-Agouni/invoiceflow/branch/master/graph/badge.svg" alt="Coverage"></a>
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/License-MIT-green" alt="License">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Stripe-Integrated-635BFF?style=for-the-badge&logo=stripe&logoColor=white" alt="Stripe">
  <img src="https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker&logoColor=white" alt="Docker">
  <img src="https://img.shields.io/badge/API-REST-FF6C37?style=for-the-badge&logo=postman&logoColor=white" alt="API">
</p>

<p align="center">
  <strong>Systeme de facturation B2B moderne pour freelances et TPE</strong>
  <br>
  <sub>API REST complete - Paiements Stripe - Export PDF/FEC - Multi-devises - 2FA - Audit Trail</sub>
</p>

<p align="center">
  <a href="#-fonctionnalites">Fonctionnalites</a> -
  <a href="#-api-rest">API</a> -
  <a href="#-installation">Installation</a> -
  <a href="#-demo">Demo</a> -
  <a href="./CHANGELOG.md">Changelog</a>
</p>

---

## Fonctionnalites

### Gestion des clients (B2B)
- Fiches entreprises completes (SIRET, TVA intracommunautaire)
- Historique des factures et statistiques par client
- Recherche et filtres avances

### Devis professionnels
- Creation avec lignes personnalisables
- **Conversion automatique devis -> facture** en un clic
- Statuts : brouillon, envoye, accepte, refuse, expire
- Export PDF avec votre logo

### Factures conformes
- Numerotation automatique (FAC-2024-0001)
- Calcul automatique TVA (taux multiples)
- **Generation PDF** avec mentions legales obligatoires
- Statuts : brouillon, en attente, payee, en retard, annulee
- Relances automatiques configurables

### Paiements en ligne (Stripe)
- Lien de paiement securise pour vos clients
- Support carte bancaire et SEPA
- Webhooks pour mise a jour automatique
- Remboursements depuis l'interface

### Dashboard analytique
- Chiffre d'affaires mensuel/annuel avec graphiques
- KPIs : factures en retard, taux de conversion devis
- Graphiques de tendance
- Vue d'ensemble financiere

### API REST complete
- **Documentation interactive Swagger UI** : `/swagger`
- Authentification Bearer Token
- **Rate limiting** (100 req/min)
- CRUD complet : clients, factures, devis
- Pagination et filtres

### Export comptable
- Export CSV des factures
- Export Excel avec formatage
- **Export FEC** (Fichier des Ecritures Comptables) - obligatoire en France
- Compatible logiciels comptables

### Factures recurrentes
- Creation de modeles de facturation automatique
- Frequences : hebdomadaire, mensuel, trimestriel, annuel
- Envoi automatique optionnel
- Gestion pause/reprise

### Multi-devises
- Support de 10+ devises (EUR, USD, GBP, CHF, CAD, etc.)
- Taux de change automatiques (BCE)
- Conversion automatique vers EUR pour la comptabilite

### Securite avancee
- **Authentification a deux facteurs (2FA)** - TOTP compatible Google Authenticator
- Codes de recuperation
- Appareils de confiance
- **Audit Trail** complet (journalisation des actions)
- Headers de securite OWASP (HSTS, CSP, etc.)

### Multi-langue
- Francais (FR)
- Anglais (EN)

---

## Stack technique

| Couche | Technologies |
|--------|--------------|
| **Backend** | PHP 8.2+ (MVC natif), PDO |
| **Base de donnees** | MySQL 8.0 / MariaDB |
| **Frontend** | Tailwind CSS 3, Chart.js |
| **PDF** | Dompdf 2.0 |
| **Email** | PHPMailer 6.9 |
| **Paiements** | Stripe API |
| **Cache** | Redis (optionnel) |
| **Tests** | PHPUnit 10.5 |
| **Analyse statique** | PHPStan niveau 8, PHP CS Fixer |
| **CI/CD** | GitHub Actions |
| **DevOps** | Docker, Docker Compose |

---

## Architecture

```
invoiceflow/
├── .github/workflows/       # CI/CD GitHub Actions
├── app/
│   ├── Api/                 # Controleurs API REST
│   ├── Controllers/         # Controleurs MVC
│   ├── Models/              # Modeles de donnees
│   ├── Views/               # Templates PHP
│   ├── Middleware/          # Auth, CSRF, RateLimit
│   ├── Services/            # PDF, Mail, Stripe, 2FA, Audit, Currency, FEC
│   ├── Database.php         # Abstraction PDO
│   └── Router.php           # Routage
├── config/                  # Configuration app, DB, mail
├── database/migrations/     # Schema SQL versionne
├── docker/                  # Configuration Docker
│   ├── php/                 # PHP-FPM Dockerfile
│   └── nginx/               # Configuration Nginx
├── public/
│   ├── index.php            # Point d'entree
│   ├── swagger/             # Swagger UI
│   └── api-docs.json        # Documentation OpenAPI
├── routes/
│   ├── web.php              # Routes web
│   └── api.php              # Routes API
├── storage/                 # Logs, cache, rate limits
├── tests/                   # Tests PHPUnit
│   ├── Unit/
│   └── Feature/
├── sdk/                     # SDK JavaScript client
├── docs/                    # Documentation technique
├── docker-compose.yml
├── phpstan.neon             # Configuration PHPStan
├── .php-cs-fixer.php        # Configuration PHP CS Fixer
└── CHANGELOG.md
```

---

## Diagramme d'architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         CLIENT                                   │
│                    (Browser / API)                               │
└─────────────────────────┬───────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────┐
│                        NGINX                                     │
│                   (Reverse Proxy)                                │
│              Port 8080 -> PHP-FPM:9000                          │
└─────────────────────────┬───────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────┐
│                      PHP-FPM 8.2                                 │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │                     Middleware                            │   │
│  │  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌────────────┐  │   │
│  │  │  Auth   │  │  CSRF   │  │  Guest  │  │ RateLimit  │  │   │
│  │  └─────────┘  └─────────┘  └─────────┘  └────────────┘  │   │
│  └──────────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │                      Router                               │   │
│  │           /web.php          /api.php                      │   │
│  └──────────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │                   Controllers                             │   │
│  │  ┌──────────────┐     ┌───────────────────────────────┐  │   │
│  │  │     Web      │     │            API                │  │   │
│  │  │ - Dashboard  │     │ - AuthApiController           │  │   │
│  │  │ - Invoices   │     │ - ClientApiController         │  │   │
│  │  │ - Quotes     │     │ - InvoiceApiController        │  │   │
│  │  │ - Clients    │     │ - QuoteApiController          │  │   │
│  │  │ - Settings   │     │                               │  │   │
│  │  └──────────────┘     └───────────────────────────────┘  │   │
│  └──────────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │                     Services                              │   │
│  │  ┌────────────┐  ┌────────────┐  ┌────────────────────┐  │   │
│  │  │ PdfService │  │MailService │  │   StripeService    │  │   │
│  │  └────────────┘  └────────────┘  └────────────────────┘  │   │
│  │  ┌────────────────────────────────────────────────────┐  │   │
│  │  │               RateLimiter                          │  │   │
│  │  └────────────────────────────────────────────────────┘  │   │
│  └──────────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │                      Models                               │   │
│  │   User │ Client │ Invoice │ Quote │ Setting │ ApiToken   │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────┬───────────────────────────────────────┘
                          │
          ┌───────────────┼───────────────┐
          │               │               │
          ▼               ▼               ▼
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│    MySQL     │  │    Redis     │  │   Stripe     │
│   Database   │  │    Cache     │  │     API      │
│              │  │ (optionnel)  │  │              │
└──────────────┘  └──────────────┘  └──────────────┘
```

---

## Flux de paiement Stripe

```
┌────────────┐     ┌────────────┐     ┌────────────┐     ┌────────────┐
│   Client   │     │ InvoiceFlow│     │   Stripe   │     │  Webhook   │
└─────┬──────┘     └─────┬──────┘     └─────┬──────┘     └─────┬──────┘
      │                  │                  │                  │
      │ 1. Voir facture  │                  │                  │
      │─────────────────>│                  │                  │
      │                  │                  │                  │
      │ 2. Clic "Payer"  │                  │                  │
      │─────────────────>│                  │                  │
      │                  │                  │                  │
      │                  │ 3. Create        │                  │
      │                  │ Checkout Session │                  │
      │                  │─────────────────>│                  │
      │                  │                  │                  │
      │                  │ 4. Session URL   │                  │
      │                  │<─────────────────│                  │
      │                  │                  │                  │
      │ 5. Redirect to   │                  │                  │
      │    Stripe        │                  │                  │
      │<─────────────────│                  │                  │
      │                  │                  │                  │
      │ 6. Paiement      │                  │                  │
      │─────────────────────────────────────>                  │
      │                  │                  │                  │
      │ 7. Confirmation  │                  │                  │
      │<─────────────────────────────────────                  │
      │                  │                  │                  │
      │                  │                  │ 8. Webhook       │
      │                  │                  │ (payment.success)│
      │                  │                  │─────────────────>│
      │                  │                  │                  │
      │                  │ 9. Update status │                  │
      │                  │<─────────────────────────────────────
      │                  │   (paid)         │                  │
      │                  │                  │                  │
      │ 10. Email        │                  │                  │
      │  confirmation    │                  │                  │
      │<─────────────────│                  │                  │
      │                  │                  │                  │
```

---

## API REST

### Documentation interactive

Swagger UI disponible a : **`http://localhost:8080/swagger`**

### Authentification

```bash
# Obtenir un token
curl -X POST http://localhost:8080/api/v1/auth/token \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "secret"}'

# Reponse
{
  "success": true,
  "data": {
    "token": "your-api-token",
    "token_type": "Bearer",
    "expires_at": null
  }
}
```

### Utilisation du token

```bash
curl -X GET http://localhost:8080/api/v1/invoices \
  -H "Authorization: Bearer your-api-token"
```

### Rate Limiting

- **100 requetes par minute** par token/IP
- Headers de reponse :
  - `X-RateLimit-Limit: 100`
  - `X-RateLimit-Remaining: 99`
  - `X-RateLimit-Reset: 1234567890`

### Endpoints principaux

| Methode | Endpoint | Description |
|---------|----------|-------------|
| `POST` | `/api/v1/auth/token` | Generer un token API |
| `GET` | `/api/v1/clients` | Lister les clients |
| `POST` | `/api/v1/clients` | Creer un client |
| `GET` | `/api/v1/invoices` | Lister les factures |
| `POST` | `/api/v1/invoices` | Creer une facture |
| `POST` | `/api/v1/invoices/{id}/pay` | Marquer comme payee |
| `GET` | `/api/v1/invoices/{id}/pdf` | Telecharger le PDF |
| `GET` | `/api/v1/invoices/stats` | Statistiques |
| `POST` | `/api/v1/quotes/{id}/convert` | Convertir en facture |
| `GET` | `/api/v1/invoices/export/csv` | Export CSV |
| `GET` | `/api/v1/invoices/export/excel` | Export Excel |
| `GET` | `/api/v1/invoices/export/fec` | Export FEC (comptabilite FR) |
| `GET` | `/api/v1/recurring` | Factures recurrentes |
| `GET` | `/api/v1/currencies` | Devises disponibles |

### SDK JavaScript

Un SDK JavaScript est disponible pour simplifier l'integration :

```javascript
import { InvoiceFlowSDK } from '@invoiceflow/sdk';

const client = new InvoiceFlowSDK({
    baseUrl: 'https://api.invoiceflow.app',
    apiToken: 'votre-token'
});

// Lister les factures
const invoices = await client.invoices.list();

// Creer une facture
const invoice = await client.invoices.create({
    client_id: 123,
    items: [{ description: 'Service', quantity: 1, unit_price: 500 }]
});
```

Voir [sdk/README.md](sdk/README.md) pour la documentation complete.

---

## Installation

### Avec Docker (recommande)

```bash
# Cloner
git clone https://github.com/Johan-Agouni/invoiceflow.git
cd invoiceflow

# Configurer
cp .env.example .env

# Lancer (avec outils de dev)
docker-compose --profile dev up -d

# Ou en production
docker-compose up -d

# C'est pret !
```

| Service | URL |
|---------|-----|
| Application | http://localhost:8080 |
| Swagger UI | http://localhost:8080/swagger |
| phpMyAdmin | http://localhost:8081 |
| MailHog | http://localhost:8025 |

### Sans Docker

```bash
# Prerequis: PHP 8.2+, MySQL 8.0, Composer

# Cloner et installer
git clone https://github.com/Johan-Agouni/invoiceflow.git
cd invoiceflow
composer install

# Configurer
cp .env.example .env
# Editer .env avec vos parametres

# Base de donnees
mysql -u root -p -e "CREATE DATABASE invoiceflow"
mysql -u root -p invoiceflow < database/migrations/init.sql
mysql -u root -p invoiceflow < database/migrations/002_api_tokens.sql
mysql -u root -p invoiceflow < database/migrations/003_stripe_payments.sql

# Lancer
php -S localhost:8080 -t public
```

---

## Tests et Qualite de Code

```bash
# Executer tous les tests
composer test

# Avec couverture de code
composer test:coverage

# Analyse statique (PHPStan niveau 8)
composer analyse

# Verification du style de code
composer cs:check

# Correction automatique du style
composer cs:fix

# Tout verifier
composer quality
```

### CI/CD

Les tests sont automatiquement executes via GitHub Actions sur chaque push :
- Lint PHP
- **PHPStan niveau 8**
- **PHP CS Fixer**
- Tests unitaires et fonctionnels (PHP 8.2, 8.3)
- Couverture de code (Codecov)
- Audit de securite
- Build Docker

---

## Configuration Stripe

```env
# .env
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

### Test des webhooks en local

```bash
# Avec Docker (profile stripe)
docker-compose --profile stripe up -d

# Ou manuellement avec Stripe CLI
stripe listen --forward-to localhost:8080/webhook/stripe
```

### Cartes de Test (Mode Sandbox)

| Carte | Usage |
|-------|-------|
| `4242 4242 4242 4242` | Paiement reussi |
| `4000 0000 0000 0002` | Carte refusee |
| `4000 0025 0000 3155` | Authentification 3DS |

> **Documentation complete** : Voir [docs/STRIPE_TESTING.md](docs/STRIPE_TESTING.md) pour tous les scenarios de test.

---

## Securite

- **Authentification 2FA** : TOTP compatible Google Authenticator, Authy, 1Password
- **Sessions securisees** : httpOnly, secure, strict mode
- **API tokens** : hashes SHA-256 avec expiration optionnelle
- **Mots de passe** : bcrypt (cost 12) avec reset tokens expirables
- **CSRF** : Token sur tous les formulaires POST
- **SQL Injection** : 100% Prepared Statements (PDO)
- **XSS** : Echappement htmlspecialchars systematique
- **Headers OWASP** :
  - `Strict-Transport-Security` (HSTS)
  - `Content-Security-Policy` (CSP)
  - `X-Content-Type-Options: nosniff`
  - `X-Frame-Options: DENY`
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Permissions-Policy`
- **Rate Limiting** : 100 req/min avec Redis ou fichier
- **Audit Trail** : Journalisation complete des actions (RGPD compliant)
- **Multi-tenant** : Isolation stricte des donnees par utilisateur

---

## Performance & Lighthouse

InvoiceFlow est optimise pour obtenir d'excellents scores Lighthouse :

| Metrique | Score |
|----------|-------|
| Performance | 90+ |
| Accessibilite | 95+ |
| Bonnes Pratiques | 100 |
| SEO | 90+ |

### Optimisations implementees

- **Core Web Vitals** : LCP < 2.5s, FID < 100ms, CLS < 0.1
- **Assets optimises** : CSS/JS minifies, images WebP, fonts preload
- **Cache HTTP** : Headers Cache-Control et ETag
- **Compression** : Gzip active sur tous les assets

> **Documentation complete** : Voir [docs/LIGHTHOUSE.md](docs/LIGHTHOUSE.md) pour le guide d'optimisation.

---

## Roadmap

- [x] API REST complete avec documentation OpenAPI
- [x] Integration Stripe (paiements, webhooks, remboursements)
- [x] Tests PHPUnit (Unit + Feature)
- [x] CI/CD GitHub Actions
- [x] Rate limiting API
- [x] Documentation Swagger UI
- [x] Export comptable CSV/Excel
- [x] Multi-langue (FR/EN)
- [x] Relances automatiques
- [x] **Export FEC** (format comptable francais)
- [x] **Multi-devises** (EUR, USD, GBP, CHF, etc.)
- [x] **Facturation recurrente**
- [x] **Authentification 2FA**
- [x] **Audit Trail** (journalisation)
- [x] **SDK JavaScript** client
- [x] **PHPStan niveau 8** + PHP CS Fixer
- [x] **Headers de securite OWASP**
- [ ] Notifications temps reel (WebSocket)
- [ ] Application mobile (PWA)
- [ ] Integrations comptables (QuickBooks, Pennylane)

---

## Licence

MIT License - voir [LICENSE](LICENSE)

---

## Auteur

**Johan Agouni** - Developpeur Full-Stack Freelance

<p>
  <a href="https://github.com/Johan-Agouni"><img src="https://img.shields.io/badge/GitHub-181717?style=flat-square&logo=github" alt="GitHub"></a>
  <a href="mailto:agouni.johan@proton.me"><img src="https://img.shields.io/badge/Email-8B89CC?style=flat-square&logo=protonmail&logoColor=white" alt="Email"></a>
  <a href="https://www.malt.fr/profile/johanagouni"><img src="https://img.shields.io/badge/Malt-FC5757?style=flat-square&logo=malt&logoColor=white" alt="Malt"></a>
</p>

---

<p align="center">
  Si ce projet vous a ete utile, n'hesitez pas a lui donner une etoile !
</p>
