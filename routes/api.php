<?php

/**
 * API Routes
 *
 * RESTful API endpoints for InvoiceFlow
 * All routes are prefixed with /api/v1
 *
 * Authentication: Bearer token required for all endpoints except /auth/token
 * Rate limiting: 100 requests per minute per token
 */

use App\Api\AuthApiController;
use App\Api\ClientApiController;
use App\Api\InvoiceApiController;
use App\Api\QuoteApiController;

// ============================================================================
// Authentication
// ============================================================================

// Generate API token (login)
$router->post('/api/v1/auth/token', [AuthApiController::class, 'token']);

// Get current user info
$router->get('/api/v1/auth/me', [AuthApiController::class, 'me']);

// List all tokens
$router->get('/api/v1/auth/tokens', [AuthApiController::class, 'listTokens']);

// Revoke current token
$router->delete('/api/v1/auth/token', [AuthApiController::class, 'revokeToken']);

// Revoke all tokens
$router->delete('/api/v1/auth/tokens', [AuthApiController::class, 'revokeAllTokens']);

// ============================================================================
// Clients
// ============================================================================

// List clients
$router->get('/api/v1/clients', [ClientApiController::class, 'index']);

// Create client
$router->post('/api/v1/clients', [ClientApiController::class, 'store']);

// Get client
$router->get('/api/v1/clients/{id}', [ClientApiController::class, 'show']);

// Update client
$router->put('/api/v1/clients/{id}', [ClientApiController::class, 'update']);

// Delete client
$router->delete('/api/v1/clients/{id}', [ClientApiController::class, 'destroy']);

// ============================================================================
// Invoices
// ============================================================================

// Invoice statistics
$router->get('/api/v1/invoices/stats', [InvoiceApiController::class, 'stats']);

// Export invoices (CSV/Excel)
$router->get('/api/v1/invoices/export/csv', [InvoiceApiController::class, 'exportCsv']);
$router->get('/api/v1/invoices/export/excel', [InvoiceApiController::class, 'exportExcel']);

// List invoices
$router->get('/api/v1/invoices', [InvoiceApiController::class, 'index']);

// Create invoice
$router->post('/api/v1/invoices', [InvoiceApiController::class, 'store']);

// Get invoice
$router->get('/api/v1/invoices/{id}', [InvoiceApiController::class, 'show']);

// Update invoice
$router->put('/api/v1/invoices/{id}', [InvoiceApiController::class, 'update']);

// Delete invoice
$router->delete('/api/v1/invoices/{id}', [InvoiceApiController::class, 'destroy']);

// Send invoice
$router->post('/api/v1/invoices/{id}/send', [InvoiceApiController::class, 'send']);

// Mark as paid
$router->post('/api/v1/invoices/{id}/pay', [InvoiceApiController::class, 'markPaid']);

// Download PDF
$router->get('/api/v1/invoices/{id}/pdf', [InvoiceApiController::class, 'pdf']);

// Send payment reminder
$router->post('/api/v1/invoices/{id}/remind', [InvoiceApiController::class, 'sendReminder']);

// ============================================================================
// Quotes
// ============================================================================

// Quote statistics
$router->get('/api/v1/quotes/stats', [QuoteApiController::class, 'stats']);

// List quotes
$router->get('/api/v1/quotes', [QuoteApiController::class, 'index']);

// Create quote
$router->post('/api/v1/quotes', [QuoteApiController::class, 'store']);

// Get quote
$router->get('/api/v1/quotes/{id}', [QuoteApiController::class, 'show']);

// Update quote
$router->put('/api/v1/quotes/{id}', [QuoteApiController::class, 'update']);

// Delete quote
$router->delete('/api/v1/quotes/{id}', [QuoteApiController::class, 'destroy']);

// Send quote
$router->post('/api/v1/quotes/{id}/send', [QuoteApiController::class, 'send']);

// Accept quote
$router->post('/api/v1/quotes/{id}/accept', [QuoteApiController::class, 'accept']);

// Decline quote
$router->post('/api/v1/quotes/{id}/decline', [QuoteApiController::class, 'decline']);

// Convert to invoice
$router->post('/api/v1/quotes/{id}/convert', [QuoteApiController::class, 'convert']);

// Download PDF
$router->get('/api/v1/quotes/{id}/pdf', [QuoteApiController::class, 'pdf']);
