<?php ob_start(); ?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Paramètres</h1>
    <p class="text-gray-600 mt-1">Configurez votre compte et vos informations d'entreprise</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Sidebar navigation -->
    <div class="lg:col-span-1">
        <nav class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sticky top-8">
            <ul class="space-y-1">
                <li><a href="#company" class="block px-4 py-2 rounded-lg hover:bg-gray-100 text-gray-700 font-medium">Entreprise</a></li>
                <li><a href="#invoice" class="block px-4 py-2 rounded-lg hover:bg-gray-100 text-gray-700">Facturation</a></li>
                <li><a href="#bank" class="block px-4 py-2 rounded-lg hover:bg-gray-100 text-gray-700">Coordonnées bancaires</a></li>
                <li><a href="#profile" class="block px-4 py-2 rounded-lg hover:bg-gray-100 text-gray-700">Profil</a></li>
                <li><a href="#password" class="block px-4 py-2 rounded-lg hover:bg-gray-100 text-gray-700">Mot de passe</a></li>
            </ul>
        </nav>
    </div>

    <!-- Forms -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Company info -->
        <form action="/settings/company" method="POST" id="company" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Informations entreprise</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'entreprise</label>
                    <input type="text" name="company_name" value="<?= htmlspecialchars($settings['company_name'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                    <input type="text" name="company_address" value="<?= htmlspecialchars($settings['company_address'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Code postal</label>
                    <input type="text" name="company_postal_code" value="<?= htmlspecialchars($settings['company_postal_code'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ville</label>
                    <input type="text" name="company_city" value="<?= htmlspecialchars($settings['company_city'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
                    <input type="text" name="company_country" value="<?= htmlspecialchars($settings['company_country'] ?? 'France') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="company_email" value="<?= htmlspecialchars($settings['company_email'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                    <input type="tel" name="company_phone" value="<?= htmlspecialchars($settings['company_phone'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SIRET</label>
                    <input type="text" name="company_siret" value="<?= htmlspecialchars($settings['company_siret'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">N° TVA intracommunautaire</label>
                    <input type="text" name="company_vat_number" value="<?= htmlspecialchars($settings['company_vat_number'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Enregistrer
                </button>
            </div>
        </form>

        <!-- Logo upload -->
        <form action="/settings/logo" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Logo</h2>

            <div class="flex items-center gap-6">
                <?php if (!empty($settings['company_logo'])): ?>
                <img src="<?= htmlspecialchars($settings['company_logo']) ?>" alt="Logo" class="w-24 h-24 object-contain border rounded-lg">
                <?php else: ?>
                <div class="w-24 h-24 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <?php endif; ?>
                <div>
                    <input type="file" name="logo" accept="image/jpeg,image/png,image/gif" class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="text-xs text-gray-500 mt-1">JPG, PNG ou GIF. Max 2 Mo.</p>
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Mettre à jour le logo
                </button>
            </div>
        </form>

        <!-- Invoice settings -->
        <form action="/settings/invoice" method="POST" id="invoice" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Paramètres de facturation</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Taux de TVA par défaut (%)</label>
                    <input type="number" name="default_vat_rate" value="<?= $settings['default_vat_rate'] ?? 20 ?>" min="0" max="100" step="0.1"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Délai de paiement (jours)</label>
                    <input type="number" name="payment_terms" value="<?= $settings['payment_terms'] ?? 30 ?>" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mentions légales (pied de facture)</label>
                    <textarea name="invoice_footer" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($settings['invoice_footer'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Enregistrer
                </button>
            </div>
        </form>

        <!-- Bank details -->
        <form action="/settings/bank" method="POST" id="bank" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Coordonnées bancaires</h2>
            <p class="text-sm text-gray-500 mb-4">Ces informations apparaîtront sur vos factures.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom de la banque</label>
                    <input type="text" name="bank_name" value="<?= htmlspecialchars($settings['bank_name'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">IBAN</label>
                    <input type="text" name="bank_iban" value="<?= htmlspecialchars($settings['bank_iban'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">BIC</label>
                    <input type="text" name="bank_bic" value="<?= htmlspecialchars($settings['bank_bic'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Enregistrer
                </button>
            </div>
        </form>

        <!-- Profile -->
        <form action="/settings/profile" method="POST" id="profile" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Profil</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Enregistrer
                </button>
            </div>
        </form>

        <!-- Password -->
        <form action="/settings/password" method="POST" id="password" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Changer le mot de passe</h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe actuel</label>
                    <input type="password" name="current_password" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nouveau mot de passe</label>
                    <input type="password" name="new_password" required minlength="8"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Minimum 8 caractères</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirmer le nouveau mot de passe</label>
                    <input type="password" name="new_password_confirmation" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Changer le mot de passe
                </button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/app.php'; ?>
