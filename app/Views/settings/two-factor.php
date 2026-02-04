<?php ob_start(); ?>

<div class="mb-8">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="/settings" class="hover:text-blue-600">Paramètres</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900">Authentification à deux facteurs</span>
    </nav>
    <h1 class="text-3xl font-bold text-gray-900">Authentification à deux facteurs</h1>
    <p class="text-gray-600 mt-1">Ajoutez une couche de sécurité supplémentaire à votre compte</p>
</div>

<div class="max-w-3xl space-y-6">
    <!-- Status Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-start gap-4">
            <?php if ($isEnabled): ?>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-lg font-semibold text-gray-900">Authentification à deux facteurs activée</h2>
                    <p class="text-gray-600 mt-1">Votre compte est protégé par l'authentification à deux facteurs.</p>

                    <div class="mt-4 flex items-center gap-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Active
                        </span>
                        <span class="text-sm text-gray-500"><?= $recoveryCodesCount ?> codes de récupération restants</span>
                    </div>
                </div>
            <?php else: ?>
                <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-lg font-semibold text-gray-900">Authentification à deux facteurs désactivée</h2>
                    <p class="text-gray-600 mt-1">Activez l'authentification à deux facteurs pour une meilleure protection de votre compte.</p>

                    <a href="/settings/two-factor/setup" class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Activer le 2FA
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($isEnabled): ?>
        <!-- Recovery Codes -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Codes de récupération</h3>
            <p class="text-gray-600 text-sm mb-4">
                Les codes de récupération vous permettent d'accéder à votre compte si vous perdez l'accès à votre application d'authentification.
                Gardez-les en lieu sûr.
            </p>

            <div class="flex items-center gap-3">
                <a href="/settings/two-factor/recovery-codes" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    Voir les codes
                </a>

                <form action="/settings/two-factor/regenerate-codes" method="POST" class="inline" onsubmit="return confirm('Attention : Cette action invalidera vos codes actuels.')">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Régénérer
                    </button>
                </form>
            </div>
        </div>

        <!-- Trusted Devices -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Appareils de confiance</h3>
                    <p class="text-gray-600 text-sm">Ces appareils n'auront pas besoin de vérification 2FA pendant 30 jours.</p>
                </div>

                <?php if (count($trustedDevices) > 0): ?>
                    <form action="/settings/two-factor/revoke-all-devices" method="POST" onsubmit="return confirm('Révoquer tous les appareils de confiance ?')">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">
                            Tout révoquer
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (empty($trustedDevices)): ?>
                <p class="text-gray-500 text-sm py-4">Aucun appareil de confiance.</p>
            <?php else: ?>
                <div class="divide-y">
                    <?php foreach ($trustedDevices as $device): ?>
                        <div class="py-3 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900"><?= htmlspecialchars($device['device_name'] ?? 'Appareil inconnu') ?></p>
                                    <p class="text-sm text-gray-500">
                                        <?= htmlspecialchars($device['ip_address'] ?? '') ?> -
                                        Dernière utilisation : <?= date('d/m/Y H:i', strtotime($device['last_used_at'])) ?>
                                    </p>
                                </div>
                            </div>
                            <form action="/settings/two-factor/revoke-device" method="POST">
                                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <input type="hidden" name="device_id" value="<?= $device['id'] ?>">
                                <button type="submit" class="text-sm text-red-600 hover:text-red-800">Révoquer</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Disable 2FA -->
        <div class="bg-white rounded-xl shadow-sm border border-red-200 p-6">
            <h3 class="text-lg font-semibold text-red-600 mb-2">Désactiver l'authentification à deux facteurs</h3>
            <p class="text-gray-600 text-sm mb-4">
                Cette action réduira la sécurité de votre compte. Vous devrez entrer votre mot de passe pour confirmer.
            </p>

            <form action="/settings/two-factor/disable" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir désactiver le 2FA ?')">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <div class="flex items-end gap-3">
                    <div class="flex-1 max-w-xs">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
                        <input type="password" name="password" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        Désactiver le 2FA
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/app.php'; ?>
