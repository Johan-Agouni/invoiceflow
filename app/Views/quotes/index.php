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

<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Devis</h1>
        <p class="text-gray-600 mt-1"><?= count($quotes) ?> devis</p>
    </div>
    <a href="/quotes/create" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nouveau devis
    </a>
</div>

<!-- Filtres -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
    <div class="flex gap-2 flex-wrap">
        <a href="/quotes" class="px-4 py-2 rounded-lg text-sm font-medium <?= !$currentStatus ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100' ?>">
            Tous
        </a>
        <a href="/quotes?status=draft" class="px-4 py-2 rounded-lg text-sm font-medium <?= $currentStatus === 'draft' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100' ?>">
            Brouillons
        </a>
        <a href="/quotes?status=sent" class="px-4 py-2 rounded-lg text-sm font-medium <?= $currentStatus === 'sent' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100' ?>">
            Envoyés
        </a>
        <a href="/quotes?status=accepted" class="px-4 py-2 rounded-lg text-sm font-medium <?= $currentStatus === 'accepted' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100' ?>">
            Acceptés
        </a>
        <a href="/quotes?status=declined" class="px-4 py-2 rounded-lg text-sm font-medium <?= $currentStatus === 'declined' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100' ?>">
            Refusés
        </a>
    </div>
</div>

<?php if (empty($quotes)): ?>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun devis</h3>
    <p class="text-gray-500 mb-6">Commencez par créer votre premier devis.</p>
    <a href="/quotes/create" class="inline-flex items-center gap-2 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Créer un devis
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
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Validité</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php foreach ($quotes as $quote): ?>
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">
                    <a href="/quotes/<?= $quote['id'] ?>" class="text-blue-600 hover:underline font-medium">
                        <?= htmlspecialchars($quote['number']) ?>
                    </a>
                </td>
                <td class="px-6 py-4 text-gray-900"><?= htmlspecialchars($quote['client_name']) ?></td>
                <td class="px-6 py-4 text-gray-500"><?= date('d/m/Y', strtotime($quote['issue_date'])) ?></td>
                <td class="px-6 py-4 text-gray-500"><?= date('d/m/Y', strtotime($quote['valid_until'])) ?></td>
                <td class="px-6 py-4 font-medium"><?= number_format($quote['total_amount'], 2, ',', ' ') ?> €</td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 text-xs font-medium rounded-full <?= $statusClasses[$quote['status']] ?? 'bg-gray-100' ?>">
                        <?= $statusLabels[$quote['status']] ?? $quote['status'] ?>
                    </span>
                </td>
                <td class="px-6 py-4 text-right">
                    <div class="flex justify-end gap-2">
                        <a href="/quotes/<?= $quote['id'] ?>/pdf" target="_blank" class="text-gray-400 hover:text-blue-600" title="Télécharger PDF">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </a>
                        <?php if (!in_array($quote['status'], ['accepted', 'invoiced'])): ?>
                        <a href="/quotes/<?= $quote['id'] ?>/edit" class="text-gray-400 hover:text-blue-600" title="Modifier">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </a>
                        <?php endif; ?>
                        <?php if ($quote['status'] === 'accepted'): ?>
                        <form action="/quotes/<?= $quote['id'] ?>/convert" method="POST" class="inline">
                            <button type="submit" class="text-gray-400 hover:text-green-600" title="Convertir en facture">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            </button>
                        </form>
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
