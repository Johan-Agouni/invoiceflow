<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Connexion' ?> - InvoiceFlow</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-600 to-blue-800 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-white">InvoiceFlow</h1>
            <p class="text-blue-200 mt-2">Gérez vos devis et factures simplement</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl p-8">
            <?php if (!empty($flash)): ?>
                <?php foreach ($flash as $type => $message): ?>
                    <div class="mb-6 p-4 rounded-lg text-sm <?= $type === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
                        <?= $message ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?= $content ?? '' ?>
        </div>

        <p class="text-center text-blue-200 text-sm mt-6">
            &copy; <?= date('Y') ?> InvoiceFlow. Tous droits réservés.
        </p>
    </div>
</body>
</html>
