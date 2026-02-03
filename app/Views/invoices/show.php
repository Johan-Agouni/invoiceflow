<?php ob_start(); ?>

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

<div class="mb-8">
    <a href="/invoices" class="text-gray-500 hover:text-gray-700 text-sm flex items-center gap-1 mb-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Retour aux factures
    </a>
    <div class="flex justify-between items-start">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-bold text-gray-900"><?= htmlspecialchars($invoice['number']) ?></h1>
                <span class="px-3 py-1 text-sm font-medium rounded-full <?= $statusClasses[$invoice['status']] ?? 'bg-gray-100' ?>">
                    <?= $statusLabels[$invoice['status']] ?? $invoice['status'] ?>
                </span>
            </div>
            <p class="text-gray-600 mt-1"><?= htmlspecialchars($invoice['company_name']) ?></p>
        </div>
        <div class="flex gap-3">
            <a href="/invoices/<?= $invoice['id'] ?>/pdf" target="_blank" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Télécharger PDF
            </a>
            <?php if ($invoice['status'] === 'draft'): ?>
            <form action="/invoices/<?= $invoice['id'] ?>/send" method="POST" class="inline">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Marquer comme envoyée
                </button>
            </form>
            <?php elseif ($invoice['status'] === 'pending' || $invoice['status'] === 'overdue'): ?>
            <form action="/invoices/<?= $invoice['id'] ?>/paid" method="POST" class="inline">
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    Marquer comme payée
                </button>
            </form>
            <?php endif; ?>
            <?php if ($invoice['status'] !== 'paid'): ?>
            <a href="/invoices/<?= $invoice['id'] ?>/edit" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                Modifier
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Détails facture -->
    <div class="lg:col-span-2 space-y-6">
        <!-- En-tête -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-2">De</h3>
                    <p class="font-medium text-gray-900"><?= htmlspecialchars($settings['company_name'] ?? 'Votre entreprise') ?></p>
                    <?php if ($settings['company_address']): ?>
                    <p class="text-gray-600 text-sm mt-1">
                        <?= htmlspecialchars($settings['company_address']) ?><br>
                        <?= htmlspecialchars($settings['company_postal_code']) ?> <?= htmlspecialchars($settings['company_city']) ?>
                    </p>
                    <?php endif; ?>
                    <?php if ($settings['company_siret']): ?>
                    <p class="text-gray-500 text-sm mt-1">SIRET: <?= htmlspecialchars($settings['company_siret']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Facturer à</h3>
                    <p class="font-medium text-gray-900"><?= htmlspecialchars($invoice['company_name']) ?></p>
                    <?php if ($invoice['client_address']): ?>
                    <p class="text-gray-600 text-sm mt-1">
                        <?= htmlspecialchars($invoice['client_address']) ?><br>
                        <?= htmlspecialchars($invoice['client_postal_code']) ?> <?= htmlspecialchars($invoice['client_city']) ?>
                    </p>
                    <?php endif; ?>
                    <?php if ($invoice['client_vat_number']): ?>
                    <p class="text-gray-500 text-sm mt-1">TVA: <?= htmlspecialchars($invoice['client_vat_number']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Lignes -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qté</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Prix HT</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">TVA</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total HT</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="px-6 py-4 text-gray-900"><?= htmlspecialchars($item['description']) ?></td>
                        <td class="px-6 py-4 text-right text-gray-600"><?= number_format($item['quantity'], 2, ',', ' ') ?></td>
                        <td class="px-6 py-4 text-right text-gray-600"><?= number_format($item['unit_price'], 2, ',', ' ') ?> €</td>
                        <td class="px-6 py-4 text-right text-gray-600"><?= number_format($item['vat_rate'], 0) ?>%</td>
                        <td class="px-6 py-4 text-right font-medium"><?= number_format($item['total'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="4" class="px-6 py-3 text-right text-sm text-gray-600">Sous-total HT</td>
                        <td class="px-6 py-3 text-right font-medium"><?= number_format($invoice['subtotal'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr>
                        <td colspan="4" class="px-6 py-3 text-right text-sm text-gray-600">TVA</td>
                        <td class="px-6 py-3 text-right font-medium"><?= number_format($invoice['vat_amount'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr class="bg-blue-50">
                        <td colspan="4" class="px-6 py-4 text-right font-semibold text-gray-900">Total TTC</td>
                        <td class="px-6 py-4 text-right text-xl font-bold text-blue-600"><?= number_format($invoice['total_amount'], 2, ',', ' ') ?> €</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php if ($invoice['notes']): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Notes</h3>
            <p class="text-gray-700"><?= nl2br(htmlspecialchars($invoice['notes'])) ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Détails</h3>
            <div class="space-y-4">
                <div>
                    <p class="text-sm text-gray-500">Numéro</p>
                    <p class="font-medium text-gray-900"><?= htmlspecialchars($invoice['number']) ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Date d'émission</p>
                    <p class="font-medium text-gray-900"><?= date('d/m/Y', strtotime($invoice['issue_date'])) ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Date d'échéance</p>
                    <p class="font-medium text-gray-900"><?= date('d/m/Y', strtotime($invoice['due_date'])) ?></p>
                </div>
                <?php if ($invoice['paid_at']): ?>
                <div>
                    <p class="text-sm text-gray-500">Payée le</p>
                    <p class="font-medium text-green-600"><?= date('d/m/Y', strtotime($invoice['paid_at'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($invoice['status'] !== 'paid'): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
            <div class="space-y-3">
                <?php if ($invoice['status'] === 'draft'): ?>
                <form action="/invoices/<?= $invoice['id'] ?>/send" method="POST">
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-center">
                        Marquer comme envoyée
                    </button>
                </form>
                <?php endif; ?>
                <?php if ($invoice['status'] === 'pending' || $invoice['status'] === 'overdue'): ?>
                <form action="/invoices/<?= $invoice['id'] ?>/paid" method="POST">
                    <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-center">
                        Marquer comme payée
                    </button>
                </form>
                <?php endif; ?>
                <a href="/invoices/<?= $invoice['id'] ?>/edit" class="block w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition text-center">
                    Modifier
                </a>
                <form action="/invoices/<?= $invoice['id'] ?>/delete" method="POST" onsubmit="return confirm('Supprimer cette facture ?')">
                    <button type="submit" class="w-full px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition text-center">
                        Supprimer
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/app.php'; ?>
