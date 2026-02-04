<?php ob_start(); ?>

<div class="mb-8">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="/settings" class="hover:text-blue-600">Paramètres</a>
        <span class="mx-2">/</span>
        <a href="/settings/two-factor" class="hover:text-blue-600">2FA</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900">Configuration</span>
    </nav>
    <h1 class="text-3xl font-bold text-gray-900">Configurer l'authentification à deux facteurs</h1>
    <p class="text-gray-600 mt-1">Scannez le QR code avec votre application d'authentification</p>
</div>

<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <div class="text-center mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-2">Étape 1 : Scanner le QR code</h2>
            <p class="text-gray-600">
                Utilisez une application comme Google Authenticator, Authy ou 1Password pour scanner ce code.
            </p>
        </div>

        <!-- QR Code -->
        <div class="flex justify-center mb-8">
            <div class="p-4 bg-white border-2 border-gray-200 rounded-xl">
                <?= $qrCode ?>
            </div>
        </div>

        <!-- Manual Entry -->
        <div class="mb-8">
            <p class="text-sm text-gray-500 text-center mb-2">Ou entrez ce code manuellement :</p>
            <div class="flex items-center justify-center gap-2">
                <code class="px-4 py-2 bg-gray-100 rounded-lg font-mono text-lg tracking-wider select-all">
                    <?= htmlspecialchars($secret) ?>
                </code>
                <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($secret) ?>')"
                        class="p-2 text-gray-500 hover:text-gray-700" title="Copier">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Verification Form -->
        <div class="border-t pt-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-2 text-center">Étape 2 : Vérifier le code</h2>
            <p class="text-gray-600 text-center mb-6">
                Entrez le code à 6 chiffres généré par votre application pour confirmer la configuration.
            </p>

            <form action="/settings/two-factor/enable" method="POST" class="max-w-xs mx-auto">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf_token) ?>">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1 text-center">Code de vérification</label>
                    <input type="text" name="code" maxlength="6" pattern="[0-9]{6}" required
                           placeholder="000000"
                           autocomplete="one-time-code"
                           inputmode="numeric"
                           class="w-full px-4 py-3 text-center text-2xl font-mono tracking-widest border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <button type="submit" class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                    Activer l'authentification à deux facteurs
                </button>
            </form>
        </div>
    </div>

    <!-- Help Section -->
    <div class="mt-6 p-4 bg-blue-50 rounded-xl">
        <h3 class="font-medium text-blue-900 mb-2">Applications recommandées</h3>
        <ul class="text-sm text-blue-800 space-y-1">
            <li>- Google Authenticator (iOS / Android)</li>
            <li>- Authy (iOS / Android / Desktop)</li>
            <li>- 1Password (iOS / Android / Desktop)</li>
            <li>- Microsoft Authenticator (iOS / Android)</li>
        </ul>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/app.php'; ?>
