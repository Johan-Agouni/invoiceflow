<?php ob_start(); ?>

<?php
$statusClasses = [
    'draft' => 'bg-gray-100 text-gray-700',
    'sent' => 'bg-blue-100 text-blue-700',
    'accepted' => 'bg-green-100 text-green-700',
    'declined' => 'bg-red-100 text-red-700',
    'expired' => 'bg-gray-100 text-gray-500',
    'invoiced' => 'bg-purple-100 text-purple-700',
];
$statusLabels = [
    'draft' => 'Brouillon',
    'sent' => 'Envoyé',
    'accepted' => 'Accepté',
    'declined' => 'Refusé',
    'expired' => 'Expiré',
    'invoiced' => 'Facturé',
];
?>

<div class="mb-8">
    <a href="/quotes" class="text-gray-500 hover:text-gray-700 text-sm flex items-center gap-1 mb-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Retour aux devis
    </a>
    <div class="flex justify-between items-start">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-bold text-gray-900"><?= htmlspecialchars($quote['number']) ?></h1>
                <span class="px-3 py-1 text-sm font-medium rounded-full <?= $statusClasses[$quote['status']] ?? 'bg-gray-100' ?>">
                    <?= $statusLabels[$quote['status']] ?? $quote['status'] ?>
                </span>
            </div>
            <p class="text-gray-600 mt-1"><?= htmlspecialchars($quote['company_name']) ?></p>
        </div>
        <div class="flex gap-3">
            <a href="/quotes/<?= $quote['id'] ?>/pdf" target="_blank" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Télécharger PDF
            </a>
            <?php if ($quote['status'] === 'accepted'): ?>
            <form action="/quotes/<?= $quote['id'] ?>/convert" method="POST" class="inline">
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    Convertir en facture
                </button>
            </form>
            <?php endif; ?>
            <?php if (!in_array($quote['status'], ['accepted', 'invoiced'])): ?>
            <a href="/quotes/<?= $quote['id'] ?>/edit" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                Modifier
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Détails devis -->
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
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Pour</h3>
                    <p class="font-medium text-gray-900"><?= htmlspecialchars($quote['company_name']) ?></p>
                    <?php if ($quote['client_address']): ?>
                    <p class="text-gray-600 text-sm mt-1">
                        <?= htmlspecialchars($quote['client_address']) ?><br>
                        <?= htmlspecialchars($quote['client_postal_code']) ?> <?= htmlspecialchars($quote['client_city']) ?>
                    </p>
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
                        <td class="px-6 py-3 text-right font-medium"><?= number_format($quote['subtotal'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr>
                        <td colspan="4" class="px-6 py-3 text-right text-sm text-gray-600">TVA</td>
                        <td class="px-6 py-3 text-right font-medium"><?= number_format($quote['vat_amount'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr class="bg-blue-50">
                        <td colspan="4" class="px-6 py-4 text-right font-semibold text-gray-900">Total TTC</td>
                        <td class="px-6 py-4 text-right text-xl font-bold text-blue-600"><?= number_format($quote['total_amount'], 2, ',', ' ') ?> €</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php if ($quote['notes']): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Notes</h3>
            <p class="text-gray-700"><?= nl2br(htmlspecialchars($quote['notes'])) ?></p>
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
                    <p class="font-medium text-gray-900"><?= htmlspecialchars($quote['number']) ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Date d'émission</p>
                    <p class="font-medium text-gray-900"><?= date('d/m/Y', strtotime($quote['issue_date'])) ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Valide jusqu'au</p>
                    <p class="font-medium text-gray-900"><?= date('d/m/Y', strtotime($quote['valid_until'])) ?></p>
                </div>
            </div>
        </div>

        <?php if (!in_array($quote['status'], ['invoiced'])): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
            <div class="space-y-3">
                <?php if ($quote['status'] === 'draft'): ?>
                <form action="/quotes/<?= $quote['id'] ?>/send" method="POST">
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Marquer comme envoyé
                    </button>
                </form>
                <?php endif; ?>

                <?php if ($quote['status'] === 'sent'): ?>
                <form action="/quotes/<?= $quote['id'] ?>/accept" method="POST">
                    <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        Marquer comme accepté
                    </button>
                </form>
                <form action="/quotes/<?= $quote['id'] ?>/decline" method="POST">
                    <button type="submit" class="w-full px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition">
                        Marquer comme refusé
                    </button>
                </form>
                <?php endif; ?>

                <?php if ($quote['status'] === 'accepted'): ?>
                <form action="/quotes/<?= $quote['id'] ?>/convert" method="POST">
                    <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        Convertir en facture
                    </button>
                </form>
                <?php endif; ?>

                <?php if (!in_array($quote['status'], ['accepted', 'invoiced'])): ?>
                <a href="/quotes/<?= $quote['id'] ?>/edit" class="block w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition text-center">
                    Modifier
                </a>
                <form action="/quotes/<?= $quote['id'] ?>/delete" method="POST" onsubmit="return confirm('Supprimer ce devis ?')">
                    <button type="submit" class="w-full px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                        Supprimer
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/app.php'; ?>
