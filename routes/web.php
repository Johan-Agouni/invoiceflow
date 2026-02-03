<?php

declare(strict_types=1);

use App\Router;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ClientController;
use App\Controllers\InvoiceController;
use App\Controllers\QuoteController;
use App\Controllers\SettingsController;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;

$router = new Router();

// Public routes (guests only)
$router->get('/login', [AuthController::class, 'showLogin'], [GuestMiddleware::class]);
$router->post('/login', [AuthController::class, 'login'], [GuestMiddleware::class]);
$router->get('/register', [AuthController::class, 'showRegister'], [GuestMiddleware::class]);
$router->post('/register', [AuthController::class, 'register'], [GuestMiddleware::class]);
$router->get('/forgot-password', [AuthController::class, 'showForgotPassword'], [GuestMiddleware::class]);
$router->post('/forgot-password', [AuthController::class, 'forgotPassword'], [GuestMiddleware::class]);
$router->get('/reset-password/{token}', [AuthController::class, 'showResetPassword'], [GuestMiddleware::class]);
$router->post('/reset-password', [AuthController::class, 'resetPassword'], [GuestMiddleware::class]);

// Logout
$router->get('/logout', [AuthController::class, 'logout']);

// Protected routes
$router->get('/', [DashboardController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);

// Clients
$router->get('/clients', [ClientController::class, 'index'], [AuthMiddleware::class]);
$router->get('/clients/create', [ClientController::class, 'create'], [AuthMiddleware::class]);
$router->post('/clients', [ClientController::class, 'store'], [AuthMiddleware::class]);
$router->get('/clients/{id}', [ClientController::class, 'show'], [AuthMiddleware::class]);
$router->get('/clients/{id}/edit', [ClientController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/clients/{id}', [ClientController::class, 'update'], [AuthMiddleware::class]);
$router->post('/clients/{id}/delete', [ClientController::class, 'destroy'], [AuthMiddleware::class]);

// Invoices
$router->get('/invoices', [InvoiceController::class, 'index'], [AuthMiddleware::class]);
$router->get('/invoices/create', [InvoiceController::class, 'create'], [AuthMiddleware::class]);
$router->post('/invoices', [InvoiceController::class, 'store'], [AuthMiddleware::class]);
$router->get('/invoices/{id}', [InvoiceController::class, 'show'], [AuthMiddleware::class]);
$router->get('/invoices/{id}/edit', [InvoiceController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/invoices/{id}', [InvoiceController::class, 'update'], [AuthMiddleware::class]);
$router->post('/invoices/{id}/send', [InvoiceController::class, 'send'], [AuthMiddleware::class]);
$router->post('/invoices/{id}/paid', [InvoiceController::class, 'markPaid'], [AuthMiddleware::class]);
$router->get('/invoices/{id}/pdf', [InvoiceController::class, 'pdf'], [AuthMiddleware::class]);
$router->post('/invoices/{id}/delete', [InvoiceController::class, 'destroy'], [AuthMiddleware::class]);

// Quotes
$router->get('/quotes', [QuoteController::class, 'index'], [AuthMiddleware::class]);
$router->get('/quotes/create', [QuoteController::class, 'create'], [AuthMiddleware::class]);
$router->post('/quotes', [QuoteController::class, 'store'], [AuthMiddleware::class]);
$router->get('/quotes/{id}', [QuoteController::class, 'show'], [AuthMiddleware::class]);
$router->get('/quotes/{id}/edit', [QuoteController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/quotes/{id}', [QuoteController::class, 'update'], [AuthMiddleware::class]);
$router->post('/quotes/{id}/send', [QuoteController::class, 'send'], [AuthMiddleware::class]);
$router->post('/quotes/{id}/accept', [QuoteController::class, 'accept'], [AuthMiddleware::class]);
$router->post('/quotes/{id}/decline', [QuoteController::class, 'decline'], [AuthMiddleware::class]);
$router->post('/quotes/{id}/convert', [QuoteController::class, 'convertToInvoice'], [AuthMiddleware::class]);
$router->get('/quotes/{id}/pdf', [QuoteController::class, 'pdf'], [AuthMiddleware::class]);
$router->post('/quotes/{id}/delete', [QuoteController::class, 'destroy'], [AuthMiddleware::class]);

// Settings
$router->get('/settings', [SettingsController::class, 'index'], [AuthMiddleware::class]);
$router->post('/settings/company', [SettingsController::class, 'updateCompany'], [AuthMiddleware::class]);
$router->post('/settings/invoice', [SettingsController::class, 'updateInvoice'], [AuthMiddleware::class]);
$router->post('/settings/bank', [SettingsController::class, 'updateBank'], [AuthMiddleware::class]);
$router->post('/settings/profile', [SettingsController::class, 'updateProfile'], [AuthMiddleware::class]);
$router->post('/settings/password', [SettingsController::class, 'updatePassword'], [AuthMiddleware::class]);
$router->post('/settings/logo', [SettingsController::class, 'uploadLogo'], [AuthMiddleware::class]);

return $router;
