<?php

/**
 * French translations
 */

return [
    // General
    'app_name' => 'InvoiceFlow',
    'dashboard' => 'Tableau de bord',
    'settings' => 'Parametres',
    'logout' => 'Deconnexion',
    'login' => 'Connexion',
    'register' => 'Inscription',
    'save' => 'Enregistrer',
    'cancel' => 'Annuler',
    'delete' => 'Supprimer',
    'edit' => 'Modifier',
    'create' => 'Creer',
    'search' => 'Rechercher',
    'filter' => 'Filtrer',
    'actions' => 'Actions',
    'back' => 'Retour',
    'yes' => 'Oui',
    'no' => 'Non',
    'confirm' => 'Confirmer',
    'loading' => 'Chargement...',

    // Navigation
    'nav' => [
        'dashboard' => 'Tableau de bord',
        'clients' => 'Clients',
        'invoices' => 'Factures',
        'quotes' => 'Devis',
        'settings' => 'Parametres',
    ],

    // Auth
    'auth' => [
        'email' => 'Adresse email',
        'password' => 'Mot de passe',
        'remember_me' => 'Se souvenir de moi',
        'forgot_password' => 'Mot de passe oublie ?',
        'login_button' => 'Se connecter',
        'register_button' => "S'inscrire",
        'name' => 'Nom complet',
        'confirm_password' => 'Confirmer le mot de passe',
        'already_registered' => 'Deja inscrit ?',
        'not_registered' => 'Pas encore inscrit ?',
    ],

    // Dashboard
    'dashboard' => [
        'welcome' => 'Bienvenue',
        'total_revenue' => 'Chiffre d\'affaires total',
        'pending_invoices' => 'Factures en attente',
        'overdue_invoices' => 'Factures en retard',
        'conversion_rate' => 'Taux de conversion',
        'monthly_revenue' => 'Revenus mensuels',
        'recent_invoices' => 'Factures recentes',
        'recent_quotes' => 'Devis recents',
        'quick_actions' => 'Actions rapides',
        'new_invoice' => 'Nouvelle facture',
        'new_quote' => 'Nouveau devis',
        'new_client' => 'Nouveau client',
    ],

    // Clients
    'clients' => [
        'title' => 'Clients',
        'new' => 'Nouveau client',
        'edit' => 'Modifier le client',
        'company_name' => 'Raison sociale',
        'contact_name' => 'Nom du contact',
        'email' => 'Email',
        'phone' => 'Telephone',
        'address' => 'Adresse',
        'postal_code' => 'Code postal',
        'city' => 'Ville',
        'country' => 'Pays',
        'vat_number' => 'Numero TVA',
        'siret' => 'SIRET',
        'notes' => 'Notes',
        'no_clients' => 'Aucun client trouve',
        'total_invoices' => 'Total factures',
        'total_paid' => 'Total paye',
        'total_pending' => 'En attente',
    ],

    // Invoices
    'invoices' => [
        'title' => 'Factures',
        'new' => 'Nouvelle facture',
        'edit' => 'Modifier la facture',
        'number' => 'Numero',
        'client' => 'Client',
        'issue_date' => 'Date d\'emission',
        'due_date' => 'Date d\'echeance',
        'status' => 'Statut',
        'subtotal' => 'Sous-total HT',
        'vat' => 'TVA',
        'total' => 'Total TTC',
        'notes' => 'Notes / Conditions',
        'items' => 'Lignes de facture',
        'add_item' => 'Ajouter une ligne',
        'description' => 'Description',
        'quantity' => 'Quantite',
        'unit_price' => 'Prix unitaire',
        'vat_rate' => 'Taux TVA',
        'line_total' => 'Total ligne',
        'no_invoices' => 'Aucune facture trouvee',
        'send' => 'Envoyer',
        'mark_paid' => 'Marquer payee',
        'download_pdf' => 'Telecharger PDF',
        'payment_received' => 'Paiement recu',
        'paid_at' => 'Payee le',

        // Status
        'status_draft' => 'Brouillon',
        'status_pending' => 'En attente',
        'status_paid' => 'Payee',
        'status_overdue' => 'En retard',
        'status_cancelled' => 'Annulee',
    ],

    // Quotes
    'quotes' => [
        'title' => 'Devis',
        'new' => 'Nouveau devis',
        'edit' => 'Modifier le devis',
        'number' => 'Numero',
        'client' => 'Client',
        'issue_date' => 'Date d\'emission',
        'valid_until' => 'Valide jusqu\'au',
        'status' => 'Statut',
        'subtotal' => 'Sous-total HT',
        'vat' => 'TVA',
        'total' => 'Total TTC',
        'notes' => 'Notes / Conditions',
        'items' => 'Lignes de devis',
        'add_item' => 'Ajouter une ligne',
        'no_quotes' => 'Aucun devis trouve',
        'send' => 'Envoyer',
        'accept' => 'Accepter',
        'decline' => 'Refuser',
        'convert' => 'Convertir en facture',
        'download_pdf' => 'Telecharger PDF',

        // Status
        'status_draft' => 'Brouillon',
        'status_sent' => 'Envoye',
        'status_accepted' => 'Accepte',
        'status_declined' => 'Refuse',
        'status_expired' => 'Expire',
        'status_invoiced' => 'Facture',
    ],

    // Settings
    'settings' => [
        'title' => 'Parametres',
        'company' => 'Informations entreprise',
        'company_name' => 'Nom de l\'entreprise',
        'company_address' => 'Adresse',
        'company_postal_code' => 'Code postal',
        'company_city' => 'Ville',
        'company_country' => 'Pays',
        'company_phone' => 'Telephone',
        'company_email' => 'Email',
        'company_siret' => 'SIRET',
        'company_vat_number' => 'Numero TVA',
        'company_logo' => 'Logo',
        'bank' => 'Informations bancaires',
        'bank_name' => 'Nom de la banque',
        'bank_iban' => 'IBAN',
        'bank_bic' => 'BIC/SWIFT',
        'invoice_settings' => 'Parametres de facturation',
        'default_vat_rate' => 'Taux TVA par defaut',
        'payment_terms' => 'Conditions de paiement (jours)',
        'invoice_footer' => 'Pied de page facture',
        'language' => 'Langue',
        'saved' => 'Parametres enregistres',
    ],

    // Payments
    'payments' => [
        'pay_now' => 'Payer maintenant',
        'pay_by_card' => 'Payer par carte',
        'pay_by_sepa' => 'Payer par virement SEPA',
        'payment_successful' => 'Paiement effectue',
        'payment_failed' => 'Echec du paiement',
        'refund' => 'Rembourser',
        'refunded' => 'Rembourse',
    ],

    // Messages
    'messages' => [
        'created' => 'Element cree avec succes',
        'updated' => 'Element mis a jour avec succes',
        'deleted' => 'Element supprime avec succes',
        'sent' => 'Element envoye avec succes',
        'error' => 'Une erreur est survenue',
        'confirm_delete' => 'Etes-vous sur de vouloir supprimer cet element ?',
        'no_results' => 'Aucun resultat trouve',
    ],

    // Validation
    'validation' => [
        'required' => 'Ce champ est obligatoire',
        'email' => 'Adresse email invalide',
        'min' => 'Minimum :min caracteres requis',
        'max' => 'Maximum :max caracteres autorises',
        'numeric' => 'Ce champ doit etre un nombre',
        'date' => 'Date invalide',
    ],

    // PDF
    'pdf' => [
        'invoice_title' => 'FACTURE',
        'quote_title' => 'DEVIS',
        'bill_to' => 'Facturer a',
        'invoice_number' => 'Numero de facture',
        'quote_number' => 'Numero de devis',
        'date' => 'Date',
        'due_date' => 'Echeance',
        'valid_until' => 'Valide jusqu\'au',
        'description' => 'Description',
        'quantity' => 'Qte',
        'unit_price' => 'Prix unit.',
        'vat' => 'TVA',
        'total' => 'Total',
        'subtotal' => 'Sous-total HT',
        'vat_total' => 'Total TVA',
        'total_due' => 'Total TTC',
        'bank_details' => 'Coordonnees bancaires',
        'payment_terms' => 'Conditions de paiement',
        'thank_you' => 'Merci pour votre confiance !',
    ],

    // Emails
    'emails' => [
        'invoice_subject' => 'Facture :number de :company',
        'invoice_body' => 'Veuillez trouver ci-joint votre facture.',
        'quote_subject' => 'Devis :number de :company',
        'quote_body' => 'Veuillez trouver ci-joint votre devis.',
        'reminder_subject' => 'Rappel : Facture :number en attente',
        'reminder_body' => 'Nous vous rappelons que la facture :number est en attente de paiement.',
    ],
];
