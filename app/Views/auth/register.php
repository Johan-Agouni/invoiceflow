<?php ob_start(); ?>

<h2 class="text-2xl font-bold text-gray-900 mb-6">Créer un compte</h2>

<form action="/register" method="POST" class="space-y-5">
    <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf_token) ?>">

    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom complet</label>
        <input type="text" id="name" name="name" required autofocus
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Adresse email</label>
        <input type="email" id="email" name="email" required
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
    </div>

    <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
        <input type="password" id="password" name="password" required minlength="8"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
        <p class="mt-1 text-xs text-gray-500">Minimum 8 caractères</p>
    </div>

    <div>
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
    </div>

    <button type="submit"
            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 transition">
        Créer mon compte
    </button>
</form>

<p class="mt-6 text-center text-gray-600">
    Déjà un compte ?
    <a href="/login" class="text-blue-600 hover:text-blue-700 font-medium">Se connecter</a>
</p>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/auth.php'; ?>
