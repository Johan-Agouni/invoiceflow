<?php

/**
 * English translations
 */

return [
    // General
    'app_name' => 'InvoiceFlow',
    'dashboard' => 'Dashboard',
    'settings' => 'Settings',
    'logout' => 'Logout',
    'login' => 'Login',
    'register' => 'Register',
    'save' => 'Save',
    'cancel' => 'Cancel',
    'delete' => 'Delete',
    'edit' => 'Edit',
    'create' => 'Create',
    'search' => 'Search',
    'filter' => 'Filter',
    'actions' => 'Actions',
    'back' => 'Back',
    'yes' => 'Yes',
    'no' => 'No',
    'confirm' => 'Confirm',
    'loading' => 'Loading...',

    // Navigation
    'nav' => [
        'dashboard' => 'Dashboard',
        'clients' => 'Clients',
        'invoices' => 'Invoices',
        'quotes' => 'Quotes',
        'settings' => 'Settings',
    ],

    // Auth
    'auth' => [
        'email' => 'Email address',
        'password' => 'Password',
        'remember_me' => 'Remember me',
        'forgot_password' => 'Forgot password?',
        'login_button' => 'Sign in',
        'register_button' => 'Sign up',
        'name' => 'Full name',
        'confirm_password' => 'Confirm password',
        'already_registered' => 'Already registered?',
        'not_registered' => 'Not registered yet?',
    ],

    // Dashboard
    'dashboard' => [
        'welcome' => 'Welcome',
        'total_revenue' => 'Total Revenue',
        'pending_invoices' => 'Pending Invoices',
        'overdue_invoices' => 'Overdue Invoices',
        'conversion_rate' => 'Conversion Rate',
        'monthly_revenue' => 'Monthly Revenue',
        'recent_invoices' => 'Recent Invoices',
        'recent_quotes' => 'Recent Quotes',
        'quick_actions' => 'Quick Actions',
        'new_invoice' => 'New Invoice',
        'new_quote' => 'New Quote',
        'new_client' => 'New Client',
    ],

    // Clients
    'clients' => [
        'title' => 'Clients',
        'new' => 'New Client',
        'edit' => 'Edit Client',
        'company_name' => 'Company Name',
        'contact_name' => 'Contact Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'address' => 'Address',
        'postal_code' => 'Postal Code',
        'city' => 'City',
        'country' => 'Country',
        'vat_number' => 'VAT Number',
        'siret' => 'Company ID',
        'notes' => 'Notes',
        'no_clients' => 'No clients found',
        'total_invoices' => 'Total Invoices',
        'total_paid' => 'Total Paid',
        'total_pending' => 'Pending',
    ],

    // Invoices
    'invoices' => [
        'title' => 'Invoices',
        'new' => 'New Invoice',
        'edit' => 'Edit Invoice',
        'number' => 'Number',
        'client' => 'Client',
        'issue_date' => 'Issue Date',
        'due_date' => 'Due Date',
        'status' => 'Status',
        'subtotal' => 'Subtotal',
        'vat' => 'VAT',
        'total' => 'Total',
        'notes' => 'Notes / Terms',
        'items' => 'Invoice Items',
        'add_item' => 'Add Item',
        'description' => 'Description',
        'quantity' => 'Quantity',
        'unit_price' => 'Unit Price',
        'vat_rate' => 'VAT Rate',
        'line_total' => 'Line Total',
        'no_invoices' => 'No invoices found',
        'send' => 'Send',
        'mark_paid' => 'Mark as Paid',
        'download_pdf' => 'Download PDF',
        'payment_received' => 'Payment Received',
        'paid_at' => 'Paid on',

        // Status
        'status_draft' => 'Draft',
        'status_pending' => 'Pending',
        'status_paid' => 'Paid',
        'status_overdue' => 'Overdue',
        'status_cancelled' => 'Cancelled',
    ],

    // Quotes
    'quotes' => [
        'title' => 'Quotes',
        'new' => 'New Quote',
        'edit' => 'Edit Quote',
        'number' => 'Number',
        'client' => 'Client',
        'issue_date' => 'Issue Date',
        'valid_until' => 'Valid Until',
        'status' => 'Status',
        'subtotal' => 'Subtotal',
        'vat' => 'VAT',
        'total' => 'Total',
        'notes' => 'Notes / Terms',
        'items' => 'Quote Items',
        'add_item' => 'Add Item',
        'no_quotes' => 'No quotes found',
        'send' => 'Send',
        'accept' => 'Accept',
        'decline' => 'Decline',
        'convert' => 'Convert to Invoice',
        'download_pdf' => 'Download PDF',

        // Status
        'status_draft' => 'Draft',
        'status_sent' => 'Sent',
        'status_accepted' => 'Accepted',
        'status_declined' => 'Declined',
        'status_expired' => 'Expired',
        'status_invoiced' => 'Invoiced',
    ],

    // Settings
    'settings' => [
        'title' => 'Settings',
        'company' => 'Company Information',
        'company_name' => 'Company Name',
        'company_address' => 'Address',
        'company_postal_code' => 'Postal Code',
        'company_city' => 'City',
        'company_country' => 'Country',
        'company_phone' => 'Phone',
        'company_email' => 'Email',
        'company_siret' => 'Company ID',
        'company_vat_number' => 'VAT Number',
        'company_logo' => 'Logo',
        'bank' => 'Bank Information',
        'bank_name' => 'Bank Name',
        'bank_iban' => 'IBAN',
        'bank_bic' => 'BIC/SWIFT',
        'invoice_settings' => 'Invoice Settings',
        'default_vat_rate' => 'Default VAT Rate',
        'payment_terms' => 'Payment Terms (days)',
        'invoice_footer' => 'Invoice Footer',
        'language' => 'Language',
        'saved' => 'Settings saved',
    ],

    // Payments
    'payments' => [
        'pay_now' => 'Pay Now',
        'pay_by_card' => 'Pay by Card',
        'pay_by_sepa' => 'Pay by SEPA Transfer',
        'payment_successful' => 'Payment Successful',
        'payment_failed' => 'Payment Failed',
        'refund' => 'Refund',
        'refunded' => 'Refunded',
    ],

    // Messages
    'messages' => [
        'created' => 'Successfully created',
        'updated' => 'Successfully updated',
        'deleted' => 'Successfully deleted',
        'sent' => 'Successfully sent',
        'error' => 'An error occurred',
        'confirm_delete' => 'Are you sure you want to delete this item?',
        'no_results' => 'No results found',
    ],

    // Validation
    'validation' => [
        'required' => 'This field is required',
        'email' => 'Invalid email address',
        'min' => 'Minimum :min characters required',
        'max' => 'Maximum :max characters allowed',
        'numeric' => 'This field must be a number',
        'date' => 'Invalid date',
    ],

    // PDF
    'pdf' => [
        'invoice_title' => 'INVOICE',
        'quote_title' => 'QUOTE',
        'bill_to' => 'Bill To',
        'invoice_number' => 'Invoice Number',
        'quote_number' => 'Quote Number',
        'date' => 'Date',
        'due_date' => 'Due Date',
        'valid_until' => 'Valid Until',
        'description' => 'Description',
        'quantity' => 'Qty',
        'unit_price' => 'Unit Price',
        'vat' => 'VAT',
        'total' => 'Total',
        'subtotal' => 'Subtotal',
        'vat_total' => 'VAT Total',
        'total_due' => 'Total Due',
        'bank_details' => 'Bank Details',
        'payment_terms' => 'Payment Terms',
        'thank_you' => 'Thank you for your business!',
    ],

    // Emails
    'emails' => [
        'invoice_subject' => 'Invoice :number from :company',
        'invoice_body' => 'Please find attached your invoice.',
        'quote_subject' => 'Quote :number from :company',
        'quote_body' => 'Please find attached your quote.',
        'reminder_subject' => 'Reminder: Invoice :number pending',
        'reminder_body' => 'This is a reminder that invoice :number is pending payment.',
    ],
];
