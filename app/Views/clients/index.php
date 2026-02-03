<?php ob_start(); ?>

<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Clients</h1>
        <p class="text-gray-600 mt-1"><?= count($clients) ?> client<?= count($clients) > 1 ? 's' : '' ?></p>
    </div>
    <a href="/clients/create" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nouveau client
    </a>
</div>

<?php if (empty($clients)): ?>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun client</h3>
    <p class="text-gray-500 mb-6">Commencez par ajouter votre premier client pour créer des devis et factures.</p>
    <a href="/clients/create" class="inline-flex items-center gap-2 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Ajouter un client
    </a>
</div>
<?php else: ?>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Entreprise</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Factures</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CA total</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">En attente</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php foreach ($clients as $client): ?>
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">
                    <a href="/clients/<?= $client['id'] ?>" class="font-medium text-gray-900 hover:text-blue-600">
                        <?= htmlspecialchars($client['company_name']) ?>
                    </a>
                    <?php if ($client['city']): ?>
                    <p class="text-sm text-gray-500"><?= htmlspecialchars($client['city']) ?></p>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4">
                    <?php if ($client['contact_name']): ?>
                    <p class="text-gray-900"><?= htmlspecialchars($client['contact_name']) ?></p>
                    <?php endif; ?>
                    <?php if ($client['email']): ?>
                    <p class="text-sm text-gray-500"><?= htmlspecialchars($client['email']) ?></p>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4 text-gray-900"><?= $client['total_invoices'] ?></td>
                <td class="px-6 py-4 font-medium text-green-600"><?= number_format($client['total_paid'], 0, ',', ' ') ?> &euro;</td>
                <td class="px-6 py-4">
                    <?php if ($client['total_pending'] > 0): ?>
                    <span class="text-yellow-600 font-medium"><?= number_format($client['total_pending'], 0, ',', ' ') ?> &euro;</span>
                    <?php else: ?>
                    <span class="text-gray-400">-</span>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4 text-right">
                    <div class="flex justify-end gap-2">
                        <a href="/clients/<?= $client['id'] ?>/edit" class="text-gray-400 hover:text-blue-600" title="Modifier">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </a>
                        <form action="/clients/<?= $client['id'] ?>/delete" method="POST" class="inline" onsubmit="return confirm('Supprimer ce client ?')">
                            <button type="submit" class="text-gray-400 hover:text-red-600" title="Supprimer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
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
