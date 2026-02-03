<?php ob_start(); ?>

<h2 class="text-2xl font-bold text-gray-900 mb-2">Mot de passe oublié</h2>
<p class="text-gray-600 mb-6">Entrez votre email pour recevoir un lien de réinitialisation.</p>

<form action="/forgot-password" method="POST" class="space-y-5">
    <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf_token) ?>">

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Adresse email</label>
        <input type="email" id="email" name="email" required autofocus
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
    </div>

    <button type="submit"
            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 transition">
        Envoyer le lien
    </button>
</form>

<p class="mt-6 text-center text-gray-600">
    <a href="/login" class="text-blue-600 hover:text-blue-700 font-medium">Retour à la connexion</a>
</p>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/auth.php'; ?>
