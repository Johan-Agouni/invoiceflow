/**
 * InvoiceFlow JavaScript SDK
 *
 * Client JavaScript pour l'API REST InvoiceFlow
 * Compatible navigateurs et Node.js
 *
 * @version 1.0.0
 * @author Johan Agouni
 * @license MIT
 */

class InvoiceFlowError extends Error {
    constructor(message, code, response) {
        super(message);
        this.name = 'InvoiceFlowError';
        this.code = code;
        this.response = response;
    }
}

class InvoiceFlowSDK {
    /**
     * Crée une instance du SDK InvoiceFlow
     * @param {Object} config - Configuration du SDK
     * @param {string} config.baseUrl - URL de base de l'API
     * @param {string} config.apiToken - Token d'authentification API
     * @param {number} [config.timeout=30000] - Timeout des requêtes en ms
     */
    constructor(config) {
        if (!config.baseUrl) {
            throw new Error('baseUrl is required');
        }
        if (!config.apiToken) {
            throw new Error('apiToken is required');
        }

        this.baseUrl = config.baseUrl.replace(/\/$/, '');
        this.apiToken = config.apiToken;
        this.timeout = config.timeout || 30000;

        // Initialisation des ressources
        this.clients = new ClientsResource(this);
        this.invoices = new InvoicesResource(this);
        this.quotes = new QuotesResource(this);
        this.settings = new SettingsResource(this);
    }

    /**
     * Effectue une requête HTTP vers l'API
     * @private
     */
    async request(method, endpoint, data = null, options = {}) {
        const url = `${this.baseUrl}/api/v1${endpoint}`;

        const headers = {
            'Authorization': `Bearer ${this.apiToken}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            ...options.headers,
        };

        const config = {
            method,
            headers,
            signal: AbortSignal.timeout(this.timeout),
        };

        if (data && ['POST', 'PUT', 'PATCH'].includes(method)) {
            config.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, config);
            const contentType = response.headers.get('content-type');

            let responseData;
            if (contentType && contentType.includes('application/json')) {
                responseData = await response.json();
            } else {
                responseData = await response.text();
            }

            if (!response.ok) {
                throw new InvoiceFlowError(
                    responseData.message || responseData.error || 'Request failed',
                    response.status,
                    responseData
                );
            }

            return responseData;
        } catch (error) {
            if (error instanceof InvoiceFlowError) {
                throw error;
            }
            if (error.name === 'AbortError') {
                throw new InvoiceFlowError('Request timeout', 408, null);
            }
            throw new InvoiceFlowError(error.message, 0, null);
        }
    }

    /**
     * Vérifie la connexion à l'API
     * @returns {Promise<boolean>}
     */
    async ping() {
        try {
            await this.request('GET', '/stats');
            return true;
        } catch {
            return false;
        }
    }

    /**
     * Récupère les statistiques générales
     * @returns {Promise<Object>}
     */
    async getStats() {
        return this.request('GET', '/stats');
    }
}

/**
 * Ressource de base avec méthodes CRUD
 */
class Resource {
    constructor(client, resourceName) {
        this.client = client;
        this.resourceName = resourceName;
    }

    /**
     * Liste toutes les ressources
     * @param {Object} [params] - Paramètres de filtrage et pagination
     */
    async list(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const endpoint = `/${this.resourceName}${queryString ? '?' + queryString : ''}`;
        return this.client.request('GET', endpoint);
    }

    /**
     * Récupère une ressource par ID
     * @param {number} id - ID de la ressource
     */
    async get(id) {
        return this.client.request('GET', `/${this.resourceName}/${id}`);
    }

    /**
     * Crée une nouvelle ressource
     * @param {Object} data - Données de la ressource
     */
    async create(data) {
        return this.client.request('POST', `/${this.resourceName}`, data);
    }

    /**
     * Met à jour une ressource
     * @param {number} id - ID de la ressource
     * @param {Object} data - Données à mettre à jour
     */
    async update(id, data) {
        return this.client.request('PUT', `/${this.resourceName}/${id}`, data);
    }

    /**
     * Supprime une ressource
     * @param {number} id - ID de la ressource
     */
    async delete(id) {
        return this.client.request('DELETE', `/${this.resourceName}/${id}`);
    }
}

/**
 * Gestion des clients
 */
class ClientsResource extends Resource {
    constructor(client) {
        super(client, 'clients');
    }

    /**
     * Recherche des clients
     * @param {string} query - Terme de recherche
     */
    async search(query) {
        return this.list({ search: query });
    }

    /**
     * Récupère les factures d'un client
     * @param {number} clientId - ID du client
     */
    async getInvoices(clientId) {
        return this.client.request('GET', `/clients/${clientId}/invoices`);
    }

    /**
     * Récupère les devis d'un client
     * @param {number} clientId - ID du client
     */
    async getQuotes(clientId) {
        return this.client.request('GET', `/clients/${clientId}/quotes`);
    }
}

/**
 * Gestion des factures
 */
class InvoicesResource extends Resource {
    constructor(client) {
        super(client, 'invoices');
    }

    /**
     * Filtre les factures par statut
     * @param {string} status - Statut (draft, pending, paid, overdue, cancelled)
     */
    async filterByStatus(status) {
        return this.list({ status });
    }

    /**
     * Envoie une facture par email
     * @param {number} id - ID de la facture
     */
    async send(id) {
        return this.client.request('POST', `/invoices/${id}/send`);
    }

    /**
     * Marque une facture comme payée
     * @param {number} id - ID de la facture
     * @param {string} [paidAt] - Date de paiement (optionnel)
     */
    async markAsPaid(id, paidAt = null) {
        return this.client.request('POST', `/invoices/${id}/paid`, {
            paid_at: paidAt,
        });
    }

    /**
     * Télécharge le PDF d'une facture
     * @param {number} id - ID de la facture
     * @returns {Promise<Blob>}
     */
    async downloadPdf(id) {
        const url = `${this.client.baseUrl}/api/v1/invoices/${id}/pdf`;
        const response = await fetch(url, {
            headers: {
                'Authorization': `Bearer ${this.client.apiToken}`,
            },
        });

        if (!response.ok) {
            throw new InvoiceFlowError('Failed to download PDF', response.status);
        }

        return response.blob();
    }

    /**
     * Récupère les factures en retard
     */
    async getOverdue() {
        return this.filterByStatus('overdue');
    }

    /**
     * Récupère les factures du mois en cours
     */
    async getCurrentMonth() {
        const now = new Date();
        const start = new Date(now.getFullYear(), now.getMonth(), 1);
        const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);

        return this.list({
            date_from: start.toISOString().split('T')[0],
            date_to: end.toISOString().split('T')[0],
        });
    }
}

/**
 * Gestion des devis
 */
class QuotesResource extends Resource {
    constructor(client) {
        super(client, 'quotes');
    }

    /**
     * Filtre les devis par statut
     * @param {string} status - Statut (draft, sent, accepted, declined, expired, invoiced)
     */
    async filterByStatus(status) {
        return this.list({ status });
    }

    /**
     * Envoie un devis par email
     * @param {number} id - ID du devis
     */
    async send(id) {
        return this.client.request('POST', `/quotes/${id}/send`);
    }

    /**
     * Marque un devis comme accepté
     * @param {number} id - ID du devis
     */
    async accept(id) {
        return this.client.request('POST', `/quotes/${id}/accept`);
    }

    /**
     * Marque un devis comme refusé
     * @param {number} id - ID du devis
     */
    async decline(id) {
        return this.client.request('POST', `/quotes/${id}/decline`);
    }

    /**
     * Convertit un devis en facture
     * @param {number} id - ID du devis
     */
    async convertToInvoice(id) {
        return this.client.request('POST', `/quotes/${id}/convert`);
    }

    /**
     * Télécharge le PDF d'un devis
     * @param {number} id - ID du devis
     * @returns {Promise<Blob>}
     */
    async downloadPdf(id) {
        const url = `${this.client.baseUrl}/api/v1/quotes/${id}/pdf`;
        const response = await fetch(url, {
            headers: {
                'Authorization': `Bearer ${this.client.apiToken}`,
            },
        });

        if (!response.ok) {
            throw new InvoiceFlowError('Failed to download PDF', response.status);
        }

        return response.blob();
    }
}

/**
 * Gestion des paramètres
 */
class SettingsResource {
    constructor(client) {
        this.client = client;
    }

    /**
     * Récupère les paramètres
     */
    async get() {
        return this.client.request('GET', '/settings');
    }

    /**
     * Met à jour les paramètres de l'entreprise
     * @param {Object} data - Données de l'entreprise
     */
    async updateCompany(data) {
        return this.client.request('PUT', '/settings/company', data);
    }

    /**
     * Met à jour les paramètres de facturation
     * @param {Object} data - Paramètres de facturation
     */
    async updateInvoice(data) {
        return this.client.request('PUT', '/settings/invoice', data);
    }

    /**
     * Met à jour les coordonnées bancaires
     * @param {Object} data - Coordonnées bancaires
     */
    async updateBank(data) {
        return this.client.request('PUT', '/settings/bank', data);
    }
}

// Export pour différents environnements
if (typeof module !== 'undefined' && module.exports) {
    // Node.js / CommonJS
    module.exports = { InvoiceFlowSDK, InvoiceFlowError };
} else if (typeof window !== 'undefined') {
    // Browser global
    window.InvoiceFlowSDK = InvoiceFlowSDK;
    window.InvoiceFlowError = InvoiceFlowError;
}

// ES Modules export
export { InvoiceFlowSDK, InvoiceFlowError };
