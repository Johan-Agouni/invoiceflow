<?php ob_start(); ?>

<?php
$statusClasses = [
    'draft' => 'bg-gray-100 text-gray-700',
    'pending' => 'bg-yellow-100 text-yellow-700',
    'paid' => 'bg-green-100 text-green-700',
    'overdue' => 'bg-red-100 text-red-700',
    'cancelled' => 'bg-gray-100 text-gray-500',
];
$statusLabels = [
    'draft' => 'Brouillon',
    'pending' => 'En attente',
    'paid' => 'Payée',
    'overdue' => 'En retard',
    'cancelled' => 'Annulée',
];
?>

<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Factures</h1>
        <p class="text-gray-600 mt-1"><?= count($invoices) ?> facture<?= count($invoices) > 1 ? 's' : '' ?></p>
    </div>
    <a href="/invoices/create" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nouvelle facture
    </a>
</div>

<!-- Filtres -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
    <div class="flex gap-2">
        <a href="/invoices" class="px-4 py-2 rounded-lg text-sm font-medium <?= !$currentStatus ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100' ?>">
            Toutes
        </a>
        <a href="/invoices?status=draft" class="px-4 py-2 rounded-lg text-sm font-medium <?= $currentStatus === 'draft' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100' ?>">
            Brouillons
        </a>
        <a href="/invoices?status=pending" class="px-4 py-2 rounded-lg text-sm font-medium <?= $currentStatus === 'pending' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100' ?>">
            En attente
        </a>
        <a href="/invoices?status=paid" class="px-4 py-2 rounded-lg text-sm font-medium <?= $currentStatus === 'paid' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100' ?>">
            Payées
        </a>
        <a href="/invoices?status=overdue" class="px-4 py-2 rounded-lg text-sm font-medium <?= $currentStatus === 'overdue' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100' ?>">
            En retard
        </a>
    </div>
</div>

<?php if (empty($invoices)): ?>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune facture</h3>
    <p class="text-gray-500 mb-6">Commencez par créer votre première facture.</p>
    <a href="/invoices/create" class="inline-flex items-center gap-2 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Créer une facture
    </a>
</div>
<?php else: ?>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Numéro</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Échéance</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php foreach ($invoices as $invoice): ?>
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">
                    <a href="/invoices/<?= $invoice['id'] ?>" class="text-blue-600 hover:underline font-medium">
                        <?= htmlspecialchars($invoice['number']) ?>
                    </a>
                </td>
                <td class="px-6 py-4 text-gray-900"><?= htmlspecialchars($invoice['client_name']) ?></td>
                <td class="px-6 py-4 text-gray-500"><?= date('d/m/Y', strtotime($invoice['issue_date'])) ?></td>
                <td class="px-6 py-4 text-gray-500"><?= date('d/m/Y', strtotime($invoice['due_date'])) ?></td>
                <td class="px-6 py-4 font-medium"><?= number_format($invoice['total_amount'], 2, ',', ' ') ?> €</td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 text-xs font-medium rounded-full <?= $statusClasses[$invoice['status']] ?? 'bg-gray-100' ?>">
                        <?= $statusLabels[$invoice['status']] ?? $invoice['status'] ?>
                    </span>
                </td>
                <td class="px-6 py-4 text-right">
                    <div class="flex justify-end gap-2">
                        <a href="/invoices/<?= $invoice['id'] ?>/pdf" target="_blank" class="text-gray-400 hover:text-blue-600" title="Télécharger PDF">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </a>
                        <?php if ($invoice['status'] !== 'paid'): ?>
                        <a href="/invoices/<?= $invoice['id'] ?>/edit" class="text-gray-400 hover:text-blue-600" title="Modifier">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/app.php'; ?>
