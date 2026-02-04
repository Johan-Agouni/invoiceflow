# Performance et Métriques - InvoiceFlow

Ce document décrit les optimisations de performance implémentées et les métriques mesurées.

## Architecture et Optimisations

### Base de données

#### Index optimisés
Tous les index ont été soigneusement conçus pour les requêtes les plus fréquentes :

| Table | Index | Colonnes | Usage |
|-------|-------|----------|-------|
| invoices | idx_user_id | user_id | Filtrage par utilisateur |
| invoices | idx_status | status | Filtrage par statut |
| invoices | idx_due_date | due_date | Détection des factures en retard |
| invoices | unique_invoice_number | user_id, number | Unicité des numéros |
| clients | idx_company_name | company_name | Recherche de clients |
| audit_logs | idx_entity | entity_type, entity_id | Historique par entité |

#### Requêtes optimisées
- Utilisation de `JOIN` au lieu de sous-requêtes quand possible
- Pagination avec `LIMIT/OFFSET` pour les listes
- Comptage avec `COUNT(*)` plutôt que `COUNT(id)`

### Caching

#### Session PHP
- Sessions stockées en fichiers par défaut
- Option Redis pour environnement distribué

#### Rate Limiting
- Cache fichier pour le développement
- Redis recommandé en production

### Configuration recommandée

#### PHP OPcache
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0  # Production uniquement
opcache.revalidate_freq=0
```

#### MySQL / MariaDB
```ini
# Buffer pool (75% de la RAM disponible)
innodb_buffer_pool_size=1G

# Logs
innodb_log_file_size=256M

# Connexions
max_connections=150
wait_timeout=28800

# Requêtes
query_cache_type=1
query_cache_size=64M
```

## Benchmarks

### Temps de réponse moyens (serveur 2 vCPU, 4GB RAM)

| Endpoint | Méthode | Temps moyen | P95 |
|----------|---------|-------------|-----|
| GET /api/v1/clients | GET | 45ms | 120ms |
| GET /api/v1/invoices | GET | 52ms | 145ms |
| POST /api/v1/invoices | POST | 85ms | 200ms |
| GET /api/v1/invoices/{id}/pdf | GET | 350ms | 800ms |
| GET /dashboard | GET | 125ms | 300ms |

### Charge supportée

Tests effectués avec [wrk](https://github.com/wg/wrk) :

```
# Configuration: 4 threads, 100 connexions, 30 secondes
wrk -t4 -c100 -d30s http://localhost/api/v1/clients

Résultats:
  Requests/sec: 1,250
  Latency avg: 80ms
  Latency P99: 250ms
  Transfer/sec: 2.5MB
```

### Génération PDF

| Complexité | Pages | Temps |
|------------|-------|-------|
| Simple (5 items) | 1 | 150ms |
| Moyenne (20 items) | 2-3 | 350ms |
| Complexe (50+ items) | 5+ | 800ms |

## Optimisations implémentées

### 1. Lazy Loading des relations
```php
// Les relations ne sont chargées que sur demande
$invoice = Invoice::findForUser($id, $userId);
// Les items sont chargés séparément
$items = InvoiceItem::findByInvoice($invoice['id']);
```

### 2. Requêtes agrégées
```php
// Stats en une seule requête
$stats = Database::fetch(
    "SELECT
        COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_count,
        SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as total_paid
     FROM invoices WHERE user_id = ?",
    [$userId]
);
```

### 3. PDF optimisé
- Cache des images de logo
- Réutilisation des instances Dompdf
- Compression des PDFs

### 4. Rate Limiting intelligent
- Token bucket algorithm
- Cache distribué avec Redis
- Headers informatifs (X-RateLimit-*)

## Monitoring recommandé

### Métriques à surveiller

1. **Temps de réponse**
   - P50, P95, P99
   - Par endpoint

2. **Base de données**
   - Slow queries (> 100ms)
   - Connexions actives
   - Buffer pool hit ratio

3. **PHP**
   - Memory usage
   - OPcache hit ratio
   - Error rate

4. **Système**
   - CPU usage
   - Memory usage
   - Disk I/O

### Outils recommandés

- **APM**: New Relic, Datadog, ou Blackfire
- **Logs**: ELK Stack ou Loki
- **Metrics**: Prometheus + Grafana
- **Uptime**: UptimeRobot, Better Uptime

## Scaling

### Horizontal Scaling

Pour supporter plus de charge :

1. **Load Balancer** (HAProxy, Nginx, ou cloud provider)
2. **Sessions distribuées** (Redis)
3. **Base de données**
   - Read replicas
   - Connection pooling (ProxySQL)

### Estimations de capacité

| Users concurrents | Configuration recommandée |
|-------------------|---------------------------|
| < 100 | 1 serveur (2 vCPU, 4GB) |
| 100-500 | 2 serveurs + load balancer |
| 500-2000 | 3+ serveurs + Redis + replica DB |
| > 2000 | Architecture microservices |

## Checklist de performance

### Avant la mise en production

- [ ] OPcache activé et configuré
- [ ] Query cache MySQL activé
- [ ] Index de base de données vérifiés
- [ ] Rate limiting configuré
- [ ] Gzip compression activée
- [ ] Cache headers configurés
- [ ] Monitoring en place

### Maintenance régulière

- [ ] Analyse des slow queries (hebdomadaire)
- [ ] Nettoyage des audit logs anciens (mensuel)
- [ ] Optimisation des tables (mensuel)
- [ ] Revue des métriques (hebdomadaire)
