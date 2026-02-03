<?php ob_start(); ?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Tableau de bord</h1>
    <p class="text-gray-600 mt-1">Bienvenue, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Utilisateur') ?></p>
</div>

<!-- Stats cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">CA ce mois</p>
                <p class="text-2xl font-bold text-gray-900"><?= number_format($invoiceStats['paid_this_month'], 0, ',', ' ') ?> &euro;</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">En attente</p>
                <p class="text-2xl font-bold text-gray-900"><?= number_format($invoiceStats['total_pending'], 0, ',', ' ') ?> &euro;</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">En retard</p>
                <p class="text-2xl font-bold text-<?= $invoiceStats['overdue_count'] > 0 ? 'red' : 'gray' ?>-900"><?= $invoiceStats['overdue_count'] ?> facture<?= $invoiceStats['overdue_count'] > 1 ? 's' : '' ?></p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Clients</p>
                <p class="text-2xl font-bold text-gray-900"><?= $clientCount ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Revenue chart -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Chiffre d'affaires</h2>
        <canvas id="revenueChart" height="100"></canvas>
    </div>

    <!-- Quick actions -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Actions rapides</h2>
        <div class="space-y-3">
            <a href="/invoices/create" class="flex items-center gap-3 p-3 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nouvelle facture
            </a>
            <a href="/quotes/create" class="flex items-center gap-3 p-3 rounded-lg bg-purple-50 text-purple-700 hover:bg-purple-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nouveau devis
            </a>
            <a href="/clients/create" class="flex items-center gap-3 p-3 rounded-lg bg-green-50 text-green-700 hover:bg-green-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                Nouveau client
            </a>
        </div>

        <?php if (!empty($overdueInvoices)): ?>
        <div class="mt-6 pt-6 border-t">
            <h3 class="text-sm font-medium text-red-600 mb-3">Factures en retard</h3>
            <div class="space-y-2">
                <?php foreach (array_slice($overdueInvoices, 0, 3) as $invoice): ?>
                <a href="/invoices/<?= $invoice['id'] ?>" class="block p-2 rounded bg-red-50 hover:bg-red-100 transition">
                    <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($invoice['company_name']) ?></p>
                    <p class="text-xs text-red-600"><?= number_format($invoice['total_amount'], 2, ',', ' ') ?> &euro; - <?= htmlspecialchars($invoice['number']) ?></p>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent invoices -->
<div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="p-6 border-b border-gray-100 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-900">Factures récentes</h2>
        <a href="/invoices" class="text-sm text-blue-600 hover:text-blue-700">Voir tout</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Numéro</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($recentInvoices)): ?>
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                        Aucune facture pour le moment.
                        <a href="/invoices/create" class="text-blue-600 hover:underline">Créer votre première facture</a>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($recentInvoices as $invoice): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <a href="/invoices/<?= $invoice['id'] ?>" class="text-blue-600 hover:underline font-medium"><?= htmlspecialchars($invoice['number']) ?></a>
                    </td>
                    <td class="px-6 py-4 text-gray-900"><?= htmlspecialchars($invoice['client_name']) ?></td>
                    <td class="px-6 py-4 font-medium"><?= number_format($invoice['total_amount'], 2, ',', ' ') ?> &euro;</td>
                    <td class="px-6 py-4">
                        <?php
                        $statusClasses = [
                            'draft' => 'bg-gray-100 text-gray-700',
                            'pending' => 'bg-yellow-100 text-yellow-700',
                            'paid' => 'bg-green-100 text-green-700',
                            'overdue' => 'bg-red-100 text-red-700',
                        ];
                        $statusLabels = [
                            'draft' => 'Brouillon',
                            'pending' => 'En attente',
                            'paid' => 'Payée',
                            'overdue' => 'En retard',
                        ];
                        ?>
                        <span class="px-2 py-1 text-xs font-medium rounded-full <?= $statusClasses[$invoice['status']] ?? 'bg-gray-100' ?>">
                            <?= $statusLabels[$invoice['status']] ?? $invoice['status'] ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-500"><?= date('d/m/Y', strtotime($invoice['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= $chartLabels ?>,
        datasets: [{
            label: 'Chiffre d\'affaires',
            data: <?= $chartData ?>,
            backgroundColor: 'rgba(37, 99, 235, 0.8)',
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString('fr-FR') + ' €';
                    }
                }
            }
        }
    }
});
</script>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/app.php'; ?>
