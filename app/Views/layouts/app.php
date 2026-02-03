<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'InvoiceFlow' ?> - InvoiceFlow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2563eb',
                        'primary-dark': '#1d4ed8',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-lg min-h-screen fixed">
            <div class="p-6 border-b">
                <h1 class="text-2xl font-bold text-primary">InvoiceFlow</h1>
            </div>
            <nav class="p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="/dashboard" class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-gray-100 <?= strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false || $_SERVER['REQUEST_URI'] === '/' ? 'bg-primary text-white hover:bg-primary-dark' : 'text-gray-700' ?>">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            Tableau de bord
                        </a>
                    </li>
                    <li>
                        <a href="/clients" class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-gray-100 <?= strpos($_SERVER['REQUEST_URI'], '/clients') !== false ? 'bg-primary text-white hover:bg-primary-dark' : 'text-gray-700' ?>">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            Clients
                        </a>
                    </li>
                    <li>
                        <a href="/quotes" class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-gray-100 <?= strpos($_SERVER['REQUEST_URI'], '/quotes') !== false ? 'bg-primary text-white hover:bg-primary-dark' : 'text-gray-700' ?>">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Devis
                        </a>
                    </li>
                    <li>
                        <a href="/invoices" class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-gray-100 <?= strpos($_SERVER['REQUEST_URI'], '/invoices') !== false ? 'bg-primary text-white hover:bg-primary-dark' : 'text-gray-700' ?>">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                            Factures
                        </a>
                    </li>
                    <li class="pt-4 mt-4 border-t">
                        <a href="/settings" class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-gray-100 <?= strpos($_SERVER['REQUEST_URI'], '/settings') !== false ? 'bg-primary text-white hover:bg-primary-dark' : 'text-gray-700' ?>">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Paramètres
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="absolute bottom-0 left-0 right-0 p-4 border-t bg-gray-50">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center text-white font-bold">
                        <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div>
                        <p class="font-medium text-sm text-gray-900"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Utilisateur') ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></p>
                    </div>
                </div>
                <a href="/logout" class="flex items-center gap-2 text-sm text-gray-600 hover:text-red-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Déconnexion
                </a>
            </div>
        </aside>

        <!-- Main content -->
        <main class="flex-1 ml-64 p-8">
            <?php if (!empty($flash)): ?>
                <?php foreach ($flash as $type => $message): ?>
                    <div class="mb-6 p-4 rounded-lg <?= $type === 'error' ? 'bg-red-100 text-red-700 border border-red-200' : 'bg-green-100 text-green-700 border border-green-200' ?>">
                        <?= $message ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?= $content ?? '' ?>
        </main>
    </div>
</body>
</html>
