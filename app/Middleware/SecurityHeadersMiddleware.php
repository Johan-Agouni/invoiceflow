<?php

declare(strict_types=1);

namespace App\Middleware;

/**
 * Middleware pour les headers de sécurité HTTP
 *
 * Implémente les recommandations OWASP pour la sécurité des headers HTTP
 */
class SecurityHeadersMiddleware
{
    /**
     * Applique les headers de sécurité à la réponse
     */
    public function handle(): void
    {
        // Empêche le MIME type sniffing
        header('X-Content-Type-Options: nosniff');

        // Protection contre le clickjacking
        header('X-Frame-Options: DENY');

        // Active le filtre XSS du navigateur
        header('X-XSS-Protection: 1; mode=block');

        // Politique de référent stricte
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // HSTS - Force HTTPS (max-age = 1 an)
        if ($this->isHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }

        // Content Security Policy
        $csp = $this->buildContentSecurityPolicy();
        header("Content-Security-Policy: {$csp}");

        // Permissions Policy (remplace Feature-Policy)
        header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(self)');

        // Protection contre les attaques Spectre
        // Note: COEP désactivé pour permettre le chargement des CDN (Tailwind, Chart.js, Stripe)
        header('Cross-Origin-Opener-Policy: same-origin-allow-popups');
        // header('Cross-Origin-Embedder-Policy: require-corp'); // Désactivé pour CDN
        header('Cross-Origin-Resource-Policy: cross-origin');
    }

    /**
     * Construit la Content Security Policy
     */
    private function buildContentSecurityPolicy(): string
    {
        $directives = [
            // Source par défaut
            "default-src 'self'",

            // Scripts - autoriser self et les scripts inline avec nonce/hash
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdn.tailwindcss.com https://js.stripe.com",

            // Styles - autoriser self et inline pour Tailwind
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdn.tailwindcss.com https://fonts.googleapis.com",

            // Images
            "img-src 'self' data: https:",

            // Fonts
            "font-src 'self' https://fonts.gstatic.com",

            // Connexions (API, WebSocket)
            "connect-src 'self' https://api.stripe.com",

            // Formulaires
            "form-action 'self'",

            // Frames
            "frame-src 'self' https://js.stripe.com https://hooks.stripe.com",

            // Objets (Flash, etc.) - désactivé
            "object-src 'none'",

            // Base URI
            "base-uri 'self'",

            // Upgrade des requêtes HTTP vers HTTPS
            'upgrade-insecure-requests',
        ];

        return implode('; ', $directives);
    }

    /**
     * Vérifie si la connexion est en HTTPS
     */
    private function isHttps(): bool
    {
        // Vérification directe
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        // Derrière un reverse proxy
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }

        // Port 443
        if (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
            return true;
        }

        return false;
    }
}
