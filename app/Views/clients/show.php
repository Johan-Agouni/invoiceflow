<?php ob_start(); ?>

<div class="mb-8">
    <a href="/clients" class="text-gray-500 hover:text-gray-700 text-sm flex items-center gap-1 mb-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Retour aux clients
    </a>
    <div class="flex justify-between items-start">
        <div>
            <h1 class="text-3xl font-bold text-gray-900"><?= htmlspecialchars($client['company_name']) ?></h1>
            <?php if ($client['contact_name']): ?>
            <p class="text-gray-600 mt-1"><?= htmlspecialchars($client['contact_name']) ?></p>
            <?php endif; ?>
        </div>
        <div class="flex gap-3">
            <a href="/invoices/create?client_id=<?= $client['id'] ?>" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nouvelle facture
            </a>
            <a href="/clients/<?= $client['id'] ?>/edit" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                Modifier
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Informations client -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Informations</h2>

        <div class="space-y-4">
            <?php if ($client['email']): ?>
            <div>
                <p class="text-sm text-gray-500">Email</p>
                <a href="mailto:<?= htmlspecialchars($client['email']) ?>" class="text-blue-600 hover:underline">
                    <?= htmlspecialchars($client['email']) ?>
                </a>
            </div>
            <?php endif; ?>

            <?php if ($client['phone']): ?>
            <div>
                <p class="text-sm text-gray-500">Téléphone</p>
                <a href="tel:<?= htmlspecialchars($client['phone']) ?>" class="text-gray-900">
                    <?= htmlspecialchars($client['phone']) ?>
                </a>
            </div>
            <?php endif; ?>

            <?php if ($client['address']): ?>
            <div>
                <p class="text-sm text-gray-500">Adresse</p>
                <p class="text-gray-900">
                    <?= htmlspecialchars($client['address']) ?><br>
                    <?= htmlspecialchars($client['postal_code']) ?> <?= htmlspecialchars($client['city']) ?><br>
                    <?= htmlspecialchars($client['country']) ?>
                </p>
            </div>
            <?php endif; ?>

            <?php if ($client['vat_number']): ?>
            <div>
                <p class="text-sm text-gray-500">N° TVA</p>
                <p class="text-gray-900"><?= htmlspecialchars($client['vat_number']) ?></p>
            </div>
            <?php endif; ?>

            <?php if ($client['notes']): ?>
            <div>
                <p class="text-sm text-gray-500">Notes</p>
                <p class="text-gray-900"><?= nl2br(htmlspecialchars($client['notes'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistiques et actions -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Stats rapides -->
        <div class="grid grid-cols-3 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <p class="text-sm text-gray-500">Total facturé</p>
                <p class="text-2xl font-bold text-gray-900">0 €</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <p class="text-sm text-gray-500">Payé</p>
                <p class="text-2xl font-bold text-green-600">0 €</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <p class="text-sm text-gray-500">En attente</p>
                <p class="text-2xl font-bold text-yellow-600">0 €</p>
            </div>
        </div>

        <!-- Factures récentes -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900">Factures</h2>
                <a href="/invoices?client_id=<?= $client['id'] ?>" class="text-sm text-blue-600 hover:underline">Voir tout</a>
            </div>
            <div class="p-8 text-center text-gray-500">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                <p>Aucune facture pour ce client</p>
                <a href="/invoices/create?client_id=<?= $client['id'] ?>" class="inline-block mt-3 text-blue-600 hover:underline">
                    Créer une facture
                </a>
            </div>
        </div>

        <!-- Devis récents -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900">Devis</h2>
                <a href="/quotes?client_id=<?= $client['id'] ?>" class="text-sm text-blue-600 hover:underline">Voir tout</a>
            </div>
            <div class="p-8 text-center text-gray-500">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <p>Aucun devis pour ce client</p>
                <a href="/quotes/create?client_id=<?= $client['id'] ?>" class="inline-block mt-3 text-blue-600 hover:underline">
                    Créer un devis
                </a>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/app.php'; ?>
