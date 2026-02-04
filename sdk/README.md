# InvoiceFlow JavaScript SDK

Client JavaScript pour l'API REST InvoiceFlow. Compatible avec les navigateurs et Node.js.

## Installation

### Via npm (Node.js)

```bash
npm install @invoiceflow/sdk
```

### Via CDN (Navigateur)

```html
<script src="https://unpkg.com/@invoiceflow/sdk"></script>
```

### Téléchargement direct

Copiez `invoiceflow-sdk.js` dans votre projet.

## Configuration

```javascript
import { InvoiceFlowSDK } from '@invoiceflow/sdk';

const invoiceflow = new InvoiceFlowSDK({
    baseUrl: 'https://votre-instance.invoiceflow.app',
    apiToken: 'votre-token-api',
    timeout: 30000, // optionnel, en millisecondes
});
```

## Utilisation

### Vérifier la connexion

```javascript
const isConnected = await invoiceflow.ping();
console.log('Connecté:', isConnected);
```

### Statistiques

```javascript
const stats = await invoiceflow.getStats();
console.log('Revenu du mois:', stats.paid_this_month);
```

### Clients

```javascript
// Liste des clients
const clients = await invoiceflow.clients.list();

// Recherche
const results = await invoiceflow.clients.search('ACME');

// Récupérer un client
const client = await invoiceflow.clients.get(123);

// Créer un client
const newClient = await invoiceflow.clients.create({
    company_name: 'ACME Corp',
    email: 'contact@acme.com',
    contact_name: 'John Doe',
    address: '123 Main Street',
    city: 'Paris',
    postal_code: '75001',
    country: 'France',
});

// Mettre à jour
await invoiceflow.clients.update(123, {
    company_name: 'ACME Corporation',
});

// Supprimer
await invoiceflow.clients.delete(123);

// Factures d'un client
const clientInvoices = await invoiceflow.clients.getInvoices(123);

// Devis d'un client
const clientQuotes = await invoiceflow.clients.getQuotes(123);
```

### Factures

```javascript
// Liste des factures
const invoices = await invoiceflow.invoices.list();

// Avec pagination
const paginatedInvoices = await invoiceflow.invoices.list({
    page: 1,
    per_page: 10,
});

// Filtrer par statut
const pendingInvoices = await invoiceflow.invoices.filterByStatus('pending');
const overdueInvoices = await invoiceflow.invoices.getOverdue();

// Récupérer une facture
const invoice = await invoiceflow.invoices.get(456);

// Créer une facture
const newInvoice = await invoiceflow.invoices.create({
    client_id: 123,
    items: [
        {
            description: 'Développement web',
            quantity: 10,
            unit_price: 500,
            vat_rate: 20,
        },
    ],
    notes: 'Merci pour votre confiance',
});

// Envoyer par email
await invoiceflow.invoices.send(456);

// Marquer comme payée
await invoiceflow.invoices.markAsPaid(456);

// Ou avec une date spécifique
await invoiceflow.invoices.markAsPaid(456, '2024-06-15');

// Télécharger le PDF
const pdfBlob = await invoiceflow.invoices.downloadPdf(456);

// Sauvegarder le PDF (navigateur)
const url = URL.createObjectURL(pdfBlob);
const a = document.createElement('a');
a.href = url;
a.download = 'facture-456.pdf';
a.click();
```

### Devis

```javascript
// Liste des devis
const quotes = await invoiceflow.quotes.list();

// Filtrer par statut
const sentQuotes = await invoiceflow.quotes.filterByStatus('sent');

// Créer un devis
const newQuote = await invoiceflow.quotes.create({
    client_id: 123,
    valid_until: '2024-07-15',
    items: [
        {
            description: 'Prestation de conseil',
            quantity: 5,
            unit_price: 800,
            vat_rate: 20,
        },
    ],
});

// Envoyer par email
await invoiceflow.quotes.send(789);

// Accepter un devis
await invoiceflow.quotes.accept(789);

// Refuser un devis
await invoiceflow.quotes.decline(789);

// Convertir en facture
const invoice = await invoiceflow.quotes.convertToInvoice(789);
```

### Paramètres

```javascript
// Récupérer les paramètres
const settings = await invoiceflow.settings.get();

// Mettre à jour les infos entreprise
await invoiceflow.settings.updateCompany({
    company_name: 'Ma Société',
    company_address: '123 Rue de Paris',
    company_city: 'Paris',
    company_postal_code: '75001',
    company_siret: '12345678901234',
});

// Mettre à jour les paramètres de facturation
await invoiceflow.settings.updateInvoice({
    default_vat_rate: 20,
    payment_terms: 30,
    invoice_footer: 'Merci pour votre confiance.',
});

// Mettre à jour les coordonnées bancaires
await invoiceflow.settings.updateBank({
    bank_name: 'BNP Paribas',
    bank_iban: 'FR76...',
    bank_bic: 'BNPAFRPP',
});
```

## Gestion des erreurs

```javascript
import { InvoiceFlowSDK, InvoiceFlowError } from '@invoiceflow/sdk';

try {
    const invoice = await invoiceflow.invoices.get(999);
} catch (error) {
    if (error instanceof InvoiceFlowError) {
        console.error('Code:', error.code);
        console.error('Message:', error.message);
        console.error('Réponse:', error.response);

        switch (error.code) {
            case 401:
                console.log('Token invalide ou expiré');
                break;
            case 404:
                console.log('Ressource non trouvée');
                break;
            case 429:
                console.log('Limite de requêtes atteinte');
                break;
            default:
                console.log('Erreur inattendue');
        }
    }
}
```

## TypeScript

Le SDK inclut des définitions TypeScript. Utilisez-le directement dans vos projets TypeScript :

```typescript
import { InvoiceFlowSDK, InvoiceFlowError } from '@invoiceflow/sdk';

interface Client {
    id: number;
    company_name: string;
    email: string;
    // ...
}

const client: Client = await invoiceflow.clients.get(123);
```

## Exemples d'intégration

### React

```jsx
import { useState, useEffect } from 'react';
import { InvoiceFlowSDK } from '@invoiceflow/sdk';

const invoiceflow = new InvoiceFlowSDK({
    baseUrl: process.env.REACT_APP_API_URL,
    apiToken: process.env.REACT_APP_API_TOKEN,
});

function InvoiceList() {
    const [invoices, setInvoices] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        invoiceflow.invoices
            .list()
            .then((data) => setInvoices(data.data))
            .finally(() => setLoading(false));
    }, []);

    if (loading) return <div>Chargement...</div>;

    return (
        <ul>
            {invoices.map((inv) => (
                <li key={inv.id}>
                    {inv.number} - {inv.total_amount}€
                </li>
            ))}
        </ul>
    );
}
```

### Vue.js

```vue
<script setup>
import { ref, onMounted } from 'vue';
import { InvoiceFlowSDK } from '@invoiceflow/sdk';

const invoiceflow = new InvoiceFlowSDK({
    baseUrl: import.meta.env.VITE_API_URL,
    apiToken: import.meta.env.VITE_API_TOKEN,
});

const clients = ref([]);

onMounted(async () => {
    const response = await invoiceflow.clients.list();
    clients.value = response.data;
});
</script>

<template>
    <div v-for="client in clients" :key="client.id">
        {{ client.company_name }}
    </div>
</template>
```

### Node.js

```javascript
const { InvoiceFlowSDK } = require('@invoiceflow/sdk');

const invoiceflow = new InvoiceFlowSDK({
    baseUrl: process.env.INVOICEFLOW_URL,
    apiToken: process.env.INVOICEFLOW_TOKEN,
});

async function createMonthlyInvoices() {
    const clients = await invoiceflow.clients.list();

    for (const client of clients.data) {
        await invoiceflow.invoices.create({
            client_id: client.id,
            items: [
                {
                    description: 'Abonnement mensuel',
                    quantity: 1,
                    unit_price: 99,
                    vat_rate: 20,
                },
            ],
        });
    }
}

createMonthlyInvoices();
```

## Licence

MIT
