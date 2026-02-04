<?php ob_start(); ?>

<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-blue-600">InvoiceFlow</h1>
            <p class="text-gray-600 mt-2">Vérification en deux étapes</p>
        </div>

        <?php if (!empty($flash['error'])): ?>
            <div class="mb-6 p-4 rounded-lg bg-red-100 text-red-700 border border-red-200">
                <?= htmlspecialchars($flash['error']) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($flash['warning'])): ?>
            <div class="mb-6 p-4 rounded-lg bg-yellow-100 text-yellow-700 border border-yellow-200">
                <?= htmlspecialchars($flash['warning']) ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-lg p-8">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-900">Code d'authentification</h2>
                <p class="text-gray-600 text-sm mt-1">
                    Entrez le code à 6 chiffres de votre application d'authentification
                </p>
            </div>

            <form action="/two-factor/verify" method="POST">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf_token) ?>">

                <div class="mb-6">
                    <input type="text" name="code" maxlength="12" required
                           placeholder="000000"
                           autocomplete="one-time-code"
                           inputmode="numeric"
                           autofocus
                           class="w-full px-4 py-4 text-center text-2xl font-mono tracking-widest border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-2 text-center">
                        Ou utilisez un code de récupération
                    </p>
                </div>

                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="trust_device" value="1"
                               class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-600">
                            Faire confiance à cet appareil pendant 30 jours
                        </span>
                    </label>
                </div>

                <button type="submit" class="w-full py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                    Vérifier
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="/login" class="text-sm text-gray-600 hover:text-blue-600">
                    &larr; Retour à la connexion
                </a>
            </div>
        </div>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-500">
                Vous n'avez plus accès à votre application d'authentification ?<br>
                <a href="#" class="text-blue-600 hover:underline" onclick="document.querySelector('input[name=code]').placeholder = 'XXXX-XXXX-XXXX'; return false;">
                    Utilisez un code de récupération
                </a>
            </p>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification 2FA - InvoiceFlow</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <?= $content ?>
</body>
</html>
