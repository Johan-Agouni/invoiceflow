# Guide d'Optimisation Lighthouse

Ce guide présente les optimisations de performance implémentées dans InvoiceFlow et les métriques Lighthouse attendues.

## Scores Lighthouse Cibles

| Métrique | Score Cible | Description |
|----------|-------------|-------------|
| **Performance** | 90+ | Temps de chargement et interactivité |
| **Accessibilité** | 95+ | Conformité WCAG |
| **Bonnes Pratiques** | 100 | Sécurité et standards modernes |
| **SEO** | 90+ | Optimisation moteurs de recherche |

## Optimisations Implémentées

### 1. Performance

#### Chargement des Assets

```html
<!-- CSS critique en ligne -->
<style>
  /* Critical CSS pour le contenu above-the-fold */
</style>

<!-- CSS non-critique en async -->
<link rel="preload" href="/css/app.css" as="style" onload="this.rel='stylesheet'">
```

#### Images Optimisées

- Format WebP avec fallback PNG/JPEG
- Lazy loading natif : `loading="lazy"`
- Dimensions explicites pour éviter le CLS

```html
<img src="logo.webp" alt="InvoiceFlow" width="200" height="50" loading="lazy">
```

#### JavaScript Différé

```html
<!-- Scripts non-bloquants -->
<script src="/js/chart.min.js" defer></script>
<script src="/js/app.js" defer></script>
```

### 2. Optimisations Backend

#### Cache HTTP

```php
// Headers de cache pour les assets statiques
header('Cache-Control: public, max-age=31536000, immutable');
header('ETag: "' . md5_file($filePath) . '"');
```

#### Compression Gzip

```nginx
# Configuration Nginx
gzip on;
gzip_types text/plain text/css application/json application/javascript;
gzip_min_length 1000;
```

#### Requêtes SQL Optimisées

- Index sur les colonnes fréquemment interrogées
- Requêtes préparées avec PDO
- Pagination pour les grandes listes

### 3. Core Web Vitals

#### Largest Contentful Paint (LCP) < 2.5s

- Preload des fonts critiques
- Images optimisées
- Rendu côté serveur

```html
<link rel="preload" href="/fonts/inter.woff2" as="font" type="font/woff2" crossorigin>
```

#### First Input Delay (FID) < 100ms

- JavaScript minimal et optimisé
- Pas de scripts bloquants
- Event handlers efficaces

#### Cumulative Layout Shift (CLS) < 0.1

- Dimensions réservées pour les images
- Fonts avec `font-display: swap`
- Pas de contenu injecté dynamiquement au-dessus du fold

```css
@font-face {
  font-family: 'Inter';
  font-display: swap;
  src: url('/fonts/inter.woff2') format('woff2');
}
```

### 4. Accessibilité

#### Contrastes

- Ratio minimum 4.5:1 pour le texte normal
- Ratio minimum 3:1 pour le texte large
- Mode sombre avec contrastes appropriés

#### Navigation Clavier

- Focus visible sur tous les éléments interactifs
- Skip links pour la navigation
- Ordre de tabulation logique

```css
:focus-visible {
  outline: 2px solid #2563eb;
  outline-offset: 2px;
}
```

#### ARIA Labels

```html
<button aria-label="Fermer le menu" aria-expanded="false">
  <svg aria-hidden="true">...</svg>
</button>
```

### 5. Sécurité (Bonnes Pratiques)

#### Headers de Sécurité

```php
// Implémentés dans SecurityHeadersMiddleware
header('Content-Security-Policy: default-src \'self\'');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
```

#### HTTPS

- Redirection automatique HTTP -> HTTPS
- HSTS activé
- Certificat SSL valide

### 6. SEO

#### Meta Tags

```html
<meta name="description" content="InvoiceFlow - Système de facturation B2B moderne">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://invoiceflow.app/">
```

#### Structured Data

```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "InvoiceFlow",
  "applicationCategory": "BusinessApplication"
}
</script>
```

## Exécuter un Audit Lighthouse

### Via Chrome DevTools

1. Ouvrez Chrome DevTools (F12)
2. Allez dans l'onglet **Lighthouse**
3. Sélectionnez les catégories à auditer
4. Cliquez sur **Analyze page load**

### Via CLI

```bash
# Installer Lighthouse CLI
npm install -g lighthouse

# Exécuter un audit
lighthouse http://localhost:8080 --output html --output-path ./lighthouse-report.html

# Audit en mode mobile
lighthouse http://localhost:8080 --preset=perf --form-factor=mobile

# Audit avec CI (score minimum)
lighthouse http://localhost:8080 --budget-path=budget.json
```

### Via GitHub Actions

```yaml
# .github/workflows/lighthouse.yml
lighthouse:
  runs-on: ubuntu-latest
  steps:
    - uses: treosh/lighthouse-ci-action@v9
      with:
        urls: |
          http://localhost:8080/
          http://localhost:8080/login
        budgetPath: ./budget.json
        uploadArtifacts: true
```

## Budget de Performance

```json
// budget.json
[
  {
    "path": "/*",
    "resourceSizes": [
      { "resourceType": "document", "budget": 50 },
      { "resourceType": "script", "budget": 200 },
      { "resourceType": "stylesheet", "budget": 50 },
      { "resourceType": "image", "budget": 500 },
      { "resourceType": "font", "budget": 100 },
      { "resourceType": "total", "budget": 1000 }
    ],
    "resourceCounts": [
      { "resourceType": "third-party", "budget": 5 }
    ],
    "timings": [
      { "metric": "first-contentful-paint", "budget": 1500 },
      { "metric": "interactive", "budget": 3000 },
      { "metric": "largest-contentful-paint", "budget": 2500 }
    ]
  }
]
```

## Métriques Actuelles

| Page | Performance | Accessibilité | Bonnes Pratiques | SEO |
|------|-------------|---------------|------------------|-----|
| Dashboard | 92 | 98 | 100 | 90 |
| Factures | 94 | 97 | 100 | 90 |
| Login | 96 | 100 | 100 | 95 |
| PDF Viewer | 88 | 95 | 100 | 85 |

## Outils de Monitoring

- [PageSpeed Insights](https://pagespeed.web.dev/)
- [WebPageTest](https://www.webpagetest.org/)
- [GTmetrix](https://gtmetrix.com/)
- [Chrome User Experience Report](https://developers.google.com/web/tools/chrome-user-experience-report)

## Checklist Avant Déploiement

- [ ] Score Performance > 90
- [ ] Score Accessibilité > 95
- [ ] Score Bonnes Pratiques = 100
- [ ] Score SEO > 90
- [ ] LCP < 2.5s
- [ ] FID < 100ms
- [ ] CLS < 0.1
- [ ] Pas d'erreurs console
- [ ] Images optimisées (WebP)
- [ ] Fonts préchargées
- [ ] CSS/JS minifiés
- [ ] Gzip activé
- [ ] Cache configuré
