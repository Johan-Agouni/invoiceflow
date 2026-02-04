# Guide de Test Stripe - Mode Sandbox

Ce guide explique comment tester les paiements Stripe en mode sandbox (test) dans InvoiceFlow.

## Configuration du Mode Test

### 1. Clés API de Test

Stripe fournit des clés de test distinctes des clés de production. Elles sont préfixées par `pk_test_` et `sk_test_`.

```env
# .env - Mode Test (Sandbox)
STRIPE_PUBLISHABLE_KEY=pk_test_51...
STRIPE_SECRET_KEY=sk_test_51...
STRIPE_WEBHOOK_SECRET=whsec_...
```

> **Important** : Ne jamais utiliser les clés de production (`pk_live_`, `sk_live_`) pour les tests.

### 2. Obtenir vos Clés de Test

1. Connectez-vous à [Stripe Dashboard](https://dashboard.stripe.com)
2. Activez le mode **Test** (toggle en haut à droite)
3. Allez dans **Developers > API Keys**
4. Copiez vos clés de test

## Cartes de Test

Stripe fournit des numéros de carte de test pour simuler différents scénarios.

### Paiements Réussis

| Numéro de Carte | Marque | Description |
|-----------------|--------|-------------|
| `4242 4242 4242 4242` | Visa | Paiement réussi |
| `5555 5555 5555 4444` | Mastercard | Paiement réussi |
| `3782 822463 10005` | American Express | Paiement réussi |

**Paramètres de test :**
- **Date d'expiration** : N'importe quelle date future (ex: 12/34)
- **CVC** : N'importe quel code à 3 chiffres (ex: 123)
- **Code postal** : N'importe lequel (ex: 75001)

### Paiements Refusés

| Numéro de Carte | Code d'Erreur | Description |
|-----------------|---------------|-------------|
| `4000 0000 0000 0002` | `card_declined` | Carte refusée (générique) |
| `4000 0000 0000 9995` | `insufficient_funds` | Fonds insuffisants |
| `4000 0000 0000 9987` | `lost_card` | Carte déclarée perdue |
| `4000 0000 0000 9979` | `stolen_card` | Carte déclarée volée |
| `4000 0000 0000 0069` | `expired_card` | Carte expirée |
| `4000 0000 0000 0127` | `incorrect_cvc` | CVC incorrect |

### Authentification 3D Secure

| Numéro de Carte | Comportement |
|-----------------|--------------|
| `4000 0025 0000 3155` | Requiert authentification 3DS |
| `4000 0000 0000 3220` | 3DS requis, authentification réussie |
| `4000 0000 0000 3063` | 3DS requis, authentification échouée |

### Paiements SEPA (Virement Bancaire)

| IBAN de Test | Description |
|--------------|-------------|
| `FR14 2004 1010 0505 0001 3M02 606` | Paiement SEPA réussi |
| `FR76 3000 6000 0112 3456 7890 189` | Paiement SEPA réussi |

## Test des Webhooks en Local

### Option 1 : Docker (Recommandé)

```bash
# Lancer avec le profil stripe
docker-compose --profile stripe up -d

# Les webhooks seront automatiquement transférés à l'application
```

### Option 2 : Stripe CLI

```bash
# Installer Stripe CLI
# macOS
brew install stripe/stripe-cli/stripe

# Windows (via scoop)
scoop install stripe

# Linux
curl -s https://packages.stripe.dev/api/security/keypair/stripe-cli-gpg/public | gpg --dearmor | sudo tee /usr/share/keyrings/stripe.gpg
echo "deb [signed-by=/usr/share/keyrings/stripe.gpg] https://packages.stripe.dev/stripe-cli-debian-local stable main" | sudo tee -a /etc/apt/sources.list.d/stripe.list
sudo apt update && sudo apt install stripe

# Se connecter
stripe login

# Écouter les webhooks
stripe listen --forward-to localhost:8080/webhook/stripe
```

### Option 3 : Stripe Dashboard (Tunnel)

1. Allez dans **Developers > Webhooks**
2. Cliquez sur **Add local listener**
3. Suivez les instructions pour configurer le tunnel

## Scénarios de Test

### 1. Paiement de Facture Réussi

1. Créez une facture en mode brouillon
2. Envoyez la facture au client
3. Cliquez sur le lien de paiement
4. Utilisez la carte `4242 4242 4242 4242`
5. Vérifiez que la facture passe en statut "Payée"

### 2. Paiement Refusé

1. Créez et envoyez une facture
2. Utilisez la carte `4000 0000 0000 0002`
3. Vérifiez que le paiement est refusé
4. La facture reste en statut "En attente"

### 3. Test de Remboursement

1. Effectuez un paiement réussi
2. Allez dans les détails de la facture
3. Cliquez sur "Rembourser"
4. Vérifiez le remboursement dans Stripe Dashboard

### 4. Test de Webhook

```bash
# Déclencher un événement de test
stripe trigger payment_intent.succeeded

# Vérifier les logs
stripe events list --limit 5
```

## Vérification des Paiements

### Dashboard Stripe

1. Connectez-vous au [Dashboard Stripe](https://dashboard.stripe.com)
2. Assurez-vous d'être en mode **Test**
3. Allez dans **Payments** pour voir les transactions de test

### Logs de l'Application

Les événements webhook sont loggés dans :
```
storage/logs/stripe.log
```

### Base de Données

```sql
-- Vérifier les paiements
SELECT * FROM invoices WHERE stripe_payment_id IS NOT NULL;

-- Voir les factures payées
SELECT * FROM invoices WHERE status = 'paid' ORDER BY paid_at DESC;
```

## Bonnes Pratiques

1. **Ne jamais utiliser de vraies cartes** en mode test
2. **Tester tous les scénarios** d'échec avant la mise en production
3. **Vérifier les webhooks** fonctionnent correctement
4. **Nettoyer les données de test** régulièrement

## Passage en Production

Avant de passer en production :

1. [ ] Remplacez les clés de test par les clés live
2. [ ] Configurez le webhook en production
3. [ ] Testez avec une vraie carte (montant minimal)
4. [ ] Activez le mode live dans Stripe Dashboard
5. [ ] Vérifiez les CGV et mentions légales

```env
# .env - Mode Production
STRIPE_PUBLISHABLE_KEY=pk_live_51...
STRIPE_SECRET_KEY=sk_live_51...
STRIPE_WEBHOOK_SECRET=whsec_...
```

## Ressources

- [Stripe Testing Documentation](https://stripe.com/docs/testing)
- [Stripe CLI Reference](https://stripe.com/docs/stripe-cli)
- [Webhook Events Reference](https://stripe.com/docs/api/events/types)
- [3D Secure Testing](https://stripe.com/docs/payments/3d-secure)
