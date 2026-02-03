<?php ob_start(); ?>

<h2 class="text-2xl font-bold text-gray-900 mb-6">Connexion</h2>

<form action="/login" method="POST" class="space-y-5">
    <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf_token) ?>">

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Adresse email</label>
        <input type="email" id="email" name="email" required autofocus
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
    </div>

    <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
        <input type="password" id="password" name="password" required
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
    </div>

    <div class="flex items-center justify-between">
        <label class="flex items-center">
            <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <span class="ml-2 text-sm text-gray-600">Se souvenir de moi</span>
        </label>
        <a href="/forgot-password" class="text-sm text-blue-600 hover:text-blue-700">Mot de passe oublié ?</a>
    </div>

    <button type="submit"
            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 transition">
        Se connecter
    </button>
</form>

<p class="mt-6 text-center text-gray-600">
    Pas encore de compte ?
    <a href="/register" class="text-blue-600 hover:text-blue-700 font-medium">Créer un compte</a>
</p>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/auth.php'; ?>
