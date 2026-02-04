<?php ob_start(); ?>

<div class="max-w-4xl">
    <div class="mb-8">
        <a href="/quotes" class="text-gray-500 hover:text-gray-700 text-sm flex items-center gap-1 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Retour aux devis
        </a>
        <h1 class="text-3xl font-bold text-gray-900">Nouveau devis</h1>
    </div>

    <form action="/quotes" method="POST" class="space-y-6" id="quoteForm">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <!-- Informations générales -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Informations générales</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="client_id" class="block text-sm font-medium text-gray-700 mb-1">Client *</label>
                    <select id="client_id" name="client_id" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Sélectionner un client</option>
                        <?php foreach ($clients as $client): ?>
                        <option value="<?= $client['id'] ?>" <?= ($_GET['client_id'] ?? '') == $client['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($client['company_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="issue_date" class="block text-sm font-medium text-gray-700 mb-1">Date d'émission *</label>
                    <input type="date" id="issue_date" name="issue_date" required
                           value="<?= date('Y-m-d') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="valid_until" class="block text-sm font-medium text-gray-700 mb-1">Valide jusqu'au *</label>
                    <input type="date" id="valid_until" name="valid_until" required
                           value="<?= date('Y-m-d', strtotime('+30 days')) ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
        </div>

        <!-- Lignes -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Lignes</h2>
                <button type="button" onclick="addLine()" class="text-blue-600 hover:text-blue-700 text-sm flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Ajouter une ligne
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full" id="linesTable">
                    <thead>
                        <tr class="text-left text-xs font-medium text-gray-500 uppercase">
                            <th class="pb-3 w-1/2">Description</th>
                            <th class="pb-3 w-20">Qté</th>
                            <th class="pb-3 w-28">Prix HT</th>
                            <th class="pb-3 w-20">TVA %</th>
                            <th class="pb-3 w-28 text-right">Total HT</th>
                            <th class="pb-3 w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="linesBody">
                        <tr class="line-row">
                            <td class="py-2 pr-2">
                                <input type="text" name="item_description[]" required placeholder="Description du service ou produit"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </td>
                            <td class="py-2 pr-2">
                                <input type="number" name="item_quantity[]" value="1" min="0.01" step="0.01" required
                                       onchange="calculateTotals()"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </td>
                            <td class="py-2 pr-2">
                                <input type="number" name="item_price[]" value="0" min="0" step="0.01" required
                                       onchange="calculateTotals()"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </td>
                            <td class="py-2 pr-2">
                                <input type="number" name="item_vat[]" value="<?= $settings['default_vat_rate'] ?? 20 ?>" min="0" max="100" step="0.1"
                                       onchange="calculateTotals()"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </td>
                            <td class="py-2 text-right font-medium line-total">0,00 €</td>
                            <td class="py-2 pl-2">
                                <button type="button" onclick="removeLine(this)" class="text-gray-400 hover:text-red-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Totaux -->
            <div class="mt-6 pt-6 border-t flex justify-end">
                <div class="w-64 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Sous-total HT</span>
                        <span class="font-medium" id="subtotal">0,00 €</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">TVA</span>
                        <span class="font-medium" id="vatAmount">0,00 €</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold pt-2 border-t">
                        <span>Total TTC</span>
                        <span id="total">0,00 €</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Notes (optionnel)</h2>
            <textarea name="notes" rows="3" placeholder="Conditions particulières, délais de réalisation..."
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3">
            <a href="/quotes" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                Annuler
            </a>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                Créer le devis
            </button>
        </div>
    </form>
</div>

<script>
const defaultVat = <?= $settings['default_vat_rate'] ?? 20 ?>;

function addLine() {
    const tbody = document.getElementById('linesBody');
    const row = document.createElement('tr');
    row.className = 'line-row';
    row.innerHTML = `
        <td class="py-2 pr-2">
            <input type="text" name="item_description[]" required placeholder="Description"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </td>
        <td class="py-2 pr-2">
            <input type="number" name="item_quantity[]" value="1" min="0.01" step="0.01" required
                   onchange="calculateTotals()"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </td>
        <td class="py-2 pr-2">
            <input type="number" name="item_price[]" value="0" min="0" step="0.01" required
                   onchange="calculateTotals()"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </td>
        <td class="py-2 pr-2">
            <input type="number" name="item_vat[]" value="${defaultVat}" min="0" max="100" step="0.1"
                   onchange="calculateTotals()"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </td>
        <td class="py-2 text-right font-medium line-total">0,00 €</td>
        <td class="py-2 pl-2">
            <button type="button" onclick="removeLine(this)" class="text-gray-400 hover:text-red-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        </td>
    `;
    tbody.appendChild(row);
}

function removeLine(btn) {
    const rows = document.querySelectorAll('.line-row');
    if (rows.length > 1) {
        btn.closest('tr').remove();
        calculateTotals();
    }
}

function calculateTotals() {
    const rows = document.querySelectorAll('.line-row');
    let subtotal = 0;
    let vatAmount = 0;

    rows.forEach(row => {
        const qty = parseFloat(row.querySelector('[name="item_quantity[]"]').value) || 0;
        const price = parseFloat(row.querySelector('[name="item_price[]"]').value) || 0;
        const vat = parseFloat(row.querySelector('[name="item_vat[]"]').value) || 0;

        const lineTotal = qty * price;
        subtotal += lineTotal;
        vatAmount += lineTotal * (vat / 100);

        row.querySelector('.line-total').textContent = formatCurrency(lineTotal);
    });

    document.getElementById('subtotal').textContent = formatCurrency(subtotal);
    document.getElementById('vatAmount').textContent = formatCurrency(vatAmount);
    document.getElementById('total').textContent = formatCurrency(subtotal + vatAmount);
}

function formatCurrency(value) {
    return value.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
}

document.querySelectorAll('[name="item_quantity[]"], [name="item_price[]"], [name="item_vat[]"]').forEach(input => {
    input.addEventListener('input', calculateTotals);
});

// Empêcher la soumission du formulaire quand on appuie sur Entrée dans les champs de saisie
document.getElementById('quoteForm').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && e.target.type !== 'submit') {
        e.preventDefault();
        // Optionnel: passer au champ suivant
        const inputs = Array.from(this.querySelectorAll('input:not([type="hidden"]), select, textarea'));
        const currentIndex = inputs.indexOf(e.target);
        if (currentIndex < inputs.length - 1) {
            inputs[currentIndex + 1].focus();
        }
    }
});
</script>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/app.php'; ?>
