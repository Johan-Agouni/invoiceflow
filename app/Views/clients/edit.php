<?php ob_start(); ?>

<div class="max-w-2xl">
    <div class="mb-8">
        <a href="/clients" class="text-gray-500 hover:text-gray-700 text-sm flex items-center gap-1 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Retour aux clients
        </a>
        <h1 class="text-3xl font-bold text-gray-900">Modifier le client</h1>
    </div>

    <form action="/clients/<?= $client['id'] ?>" method="POST" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-6">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">Nom de l'entreprise *</label>
                <input type="text" id="company_name" name="company_name" required
                       value="<?= htmlspecialchars($client['company_name']) ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label for="contact_name" class="block text-sm font-medium text-gray-700 mb-1">Nom du contact</label>
                <input type="text" id="contact_name" name="contact_name"
                       value="<?= htmlspecialchars($client['contact_name'] ?? '') ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($client['email'] ?? '') ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                <input type="tel" id="phone" name="phone"
                       value="<?= htmlspecialchars($client['phone'] ?? '') ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label for="vat_number" class="block text-sm font-medium text-gray-700 mb-1">N° TVA</label>
                <input type="text" id="vat_number" name="vat_number"
                       value="<?= htmlspecialchars($client['vat_number'] ?? '') ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="md:col-span-2">
                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                <input type="text" id="address" name="address"
                       value="<?= htmlspecialchars($client['address'] ?? '') ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">Code postal</label>
                <input type="text" id="postal_code" name="postal_code"
                       value="<?= htmlspecialchars($client['postal_code'] ?? '') ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label for="city" class="block text-sm font-medium text-gray-700 mb-1">Ville</label>
                <input type="text" id="city" name="city"
                       value="<?= htmlspecialchars($client['city'] ?? '') ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
                <input type="text" id="country" name="country"
                       value="<?= htmlspecialchars($client['country'] ?? 'France') ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="md:col-span-2">
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea id="notes" name="notes" rows="3"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($client['notes'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="flex justify-between pt-4 border-t">
            <form action="/clients/<?= $client['id'] ?>/delete" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce client ?')">
                <button type="submit" class="px-4 py-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition">
                    Supprimer le client
                </button>
            </form>
            <div class="flex gap-3">
                <a href="/clients" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                    Annuler
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Enregistrer
                </button>
            </div>
        </div>
    </form>
</div>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/app.php'; ?>
