<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Translation Service
 *
 * Handles multi-language support for the application.
 * Supports French (fr) and English (en).
 */
class TranslationService
{
    private static ?self $instance = null;
    private array $translations = [];
    private string $locale = 'fr';
    private string $fallbackLocale = 'fr';
    private array $supportedLocales = ['fr', 'en'];

    private function __construct()
    {
        $this->loadLocale($this->locale);
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Set current locale
     */
    public function setLocale(string $locale): self
    {
        if (in_array($locale, $this->supportedLocales)) {
            $this->locale = $locale;
            $this->loadLocale($locale);
        }
        return $this;
    }

    /**
     * Get current locale
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Get supported locales
     */
    public function getSupportedLocales(): array
    {
        return $this->supportedLocales;
    }

    /**
     * Load translations for a locale
     */
    private function loadLocale(string $locale): void
    {
        $file = dirname(__DIR__, 2) . '/lang/' . $locale . '.php';

        if (file_exists($file)) {
            $this->translations[$locale] = require $file;
        }
    }

    /**
     * Get a translation
     *
     * @param string $key Dot notation key (e.g., 'invoices.title')
     * @param array $replace Placeholders to replace (e.g., [':name' => 'John'])
     * @param string|null $locale Override locale
     * @return string
     */
    public function get(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->locale;

        // Ensure locale is loaded
        if (!isset($this->translations[$locale])) {
            $this->loadLocale($locale);
        }

        // Get translation
        $translation = $this->getNestedValue($this->translations[$locale] ?? [], $key);

        // Fallback to default locale
        if ($translation === null && $locale !== $this->fallbackLocale) {
            if (!isset($this->translations[$this->fallbackLocale])) {
                $this->loadLocale($this->fallbackLocale);
            }
            $translation = $this->getNestedValue($this->translations[$this->fallbackLocale] ?? [], $key);
        }

        // Return key if no translation found
        if ($translation === null) {
            return $key;
        }

        // Replace placeholders
        foreach ($replace as $placeholder => $value) {
            $translation = str_replace($placeholder, (string) $value, $translation);
        }

        return $translation;
    }

    /**
     * Check if a translation exists
     */
    public function has(string $key, ?string $locale = null): bool
    {
        $locale = $locale ?? $this->locale;

        if (!isset($this->translations[$locale])) {
            $this->loadLocale($locale);
        }

        return $this->getNestedValue($this->translations[$locale] ?? [], $key) !== null;
    }

    /**
     * Get all translations for current locale
     */
    public function all(?string $locale = null): array
    {
        $locale = $locale ?? $this->locale;

        if (!isset($this->translations[$locale])) {
            $this->loadLocale($locale);
        }

        return $this->translations[$locale] ?? [];
    }

    /**
     * Get nested array value using dot notation
     */
    private function getNestedValue(array $array, string $key): ?string
    {
        $keys = explode('.', $key);

        foreach ($keys as $k) {
            if (!is_array($array) || !isset($array[$k])) {
                return null;
            }
            $array = $array[$k];
        }

        return is_string($array) ? $array : null;
    }

    /**
     * Detect locale from browser
     */
    public function detectFromBrowser(): string
    {
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';

        foreach ($this->supportedLocales as $locale) {
            if (stripos($acceptLanguage, $locale) !== false) {
                return $locale;
            }
        }

        return $this->fallbackLocale;
    }

    /**
     * Detect locale from session or browser
     */
    public function detectLocale(): string
    {
        // Check session
        if (isset($_SESSION['locale']) && in_array($_SESSION['locale'], $this->supportedLocales)) {
            return $_SESSION['locale'];
        }

        // Check user settings in database
        if (isset($_SESSION['user_id'])) {
            // Could load from database here
        }

        // Detect from browser
        return $this->detectFromBrowser();
    }

    /**
     * Save locale to session
     */
    public function saveToSession(string $locale): void
    {
        if (in_array($locale, $this->supportedLocales)) {
            $_SESSION['locale'] = $locale;
        }
    }
}

/**
 * Global translation helper function
 *
 * @param string $key Translation key
 * @param array $replace Placeholders to replace
 * @return string
 */
function __(?string $key = null, array $replace = []): string
{
    if ($key === null) {
        return '';
    }
    return TranslationService::getInstance()->get($key, $replace);
}

/**
 * Global translation helper (alias)
 */
function trans(string $key, array $replace = []): string
{
    return TranslationService::getInstance()->get($key, $replace);
}
