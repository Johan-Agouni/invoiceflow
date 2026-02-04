<?php ob_start(); ?>

<div class="mb-8">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="/settings" class="hover:text-blue-600">Paramètres</a>
        <span class="mx-2">/</span>
        <a href="/settings/two-factor" class="hover:text-blue-600">2FA</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900">Codes de récupération</span>
    </nav>
    <h1 class="text-3xl font-bold text-gray-900">Codes de récupération</h1>
    <p class="text-gray-600 mt-1">Conservez ces codes en lieu sûr pour accéder à votre compte en cas de perte de votre appareil</p>
</div>

<div class="max-w-2xl">
    <?php if ($recoveryCodes): ?>
        <!-- New codes to save -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 mb-6">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <h3 class="font-semibold text-yellow-800">Sauvegardez ces codes maintenant</h3>
                    <p class="text-sm text-yellow-700 mt-1">
                        Ces codes ne seront plus affichés. Enregistrez-les dans un endroit sécurisé comme un gestionnaire de mots de passe.
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-2 gap-3 mb-6">
                <?php foreach ($recoveryCodes as $code): ?>
                    <code class="px-4 py-2 bg-gray-100 rounded-lg font-mono text-center select-all">
                        <?= htmlspecialchars($code) ?>
                    </code>
                <?php endforeach; ?>
            </div>

            <div class="flex gap-3">
                <button onclick="copyAllCodes()" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    Copier tous les codes
                </button>
                <button onclick="downloadCodes()" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Télécharger (.txt)
                </button>
            </div>
        </div>

        <script>
            const codes = <?= json_encode($recoveryCodes) ?>;

            function copyAllCodes() {
                const text = codes.join('\n');
                navigator.clipboard.writeText(text).then(() => {
                    alert('Codes copiés dans le presse-papiers');
                });
            }

            function downloadCodes() {
                const text = 'InvoiceFlow - Codes de récupération 2FA\n' +
                            '=====================================\n\n' +
                            codes.join('\n') +
                            '\n\n=====================================\n' +
                            'Gardez ces codes en lieu sûr.\n' +
                            'Chaque code ne peut être utilisé qu\'une seule fois.';

                const blob = new Blob([text], { type: 'text/plain' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'invoiceflow-recovery-codes.txt';
                a.click();
                URL.revokeObjectURL(url);
            }
        </script>
    <?php else: ?>
        <!-- Info about recovery codes -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="text-center py-8">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Codes de récupération</h3>
                <p class="text-gray-600 mb-4">
                    Vous avez <strong><?= $codesCount ?></strong> codes de récupération restants.
                </p>
                <p class="text-sm text-gray-500">
                    Si vous avez moins de 3 codes restants, pensez à en régénérer de nouveaux.
                </p>
            </div>
        </div>
    <?php endif; ?>

    <div class="mt-6 flex items-center justify-between">
        <a href="/settings/two-factor" class="text-gray-600 hover:text-gray-800">
            &larr; Retour aux paramètres 2FA
        </a>

        <?php if (!$recoveryCodes): ?>
            <form action="/settings/two-factor/regenerate-codes" method="POST" onsubmit="return confirm('Attention : Cette action invalidera vos codes actuels.')">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Régénérer les codes
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/app.php'; ?>
