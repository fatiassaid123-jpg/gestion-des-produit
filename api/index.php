<?php
session_start();
require_once "connexion.php";

// Authentication: require login to access product pages
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit;
}

$flashMessage = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// Migrate products created by the previous session-based version once.
if (!empty($_SESSION['produits']) && is_array($_SESSION['produits'])) {
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM produits WHERE id_utilisateur = :id_utilisateur');
    $countStmt->execute(['id_utilisateur' => $_SESSION['id_utilisateur']]);

    if ((int) $countStmt->fetchColumn() === 0) {
        $migrate = $pdo->prepare('INSERT INTO produits (id_utilisateur, reference, designation, prix_achat, prix_vente) VALUES (:id_utilisateur, :reference, :designation, :prix_achat, :prix_vente)');

        foreach ($_SESSION['produits'] as $ancienProduit) {
            if (!isset($ancienProduit['reference'], $ancienProduit['designation'], $ancienProduit['prix_achat'], $ancienProduit['prix_vente'])) {
                continue;
            }

            $migrate->execute([
                'id_utilisateur' => $_SESSION['id_utilisateur'],
                'reference' => $ancienProduit['reference'],
                'designation' => $ancienProduit['designation'],
                'prix_achat' => $ancienProduit['prix_achat'],
                'prix_vente' => $ancienProduit['prix_vente']
            ]);
        }
    }

    unset($_SESSION['produits']);
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Gestion des Produits</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

    <header>
        <nav>
            <?php if (isset($_SESSION['nom'])) { ?>
            Bonjour <?= htmlspecialchars($_SESSION['nom']) ?> | <a href="logout.php">Déconnexion</a>
            <?php } else { ?>
            <a href="inscription.php">Inscription</a> | <a href="login.php">Connexion</a>
            <?php } ?>
        </nav>
    </header>

    <h1>Gestion des Produits</h1>

    <div id="operationMessage" class="operation-message<?= $flashMessage !== null ? ' success is-visible' : '' ?>"
        role="status" aria-live="polite"><?= $flashMessage !== null ? htmlspecialchars($flashMessage) : '' ?></div>

    <!-- Formulaire d'ajout -->

    <div class="form-container">

        <h2>Ajouter un produit</h2>

        <?php
        $formErrors = $_SESSION['form_errors'] ?? null;
        $old = $_SESSION['old_add'] ?? [];
        ?>

        <?php if (!empty($formErrors)) { ?>
        <div class="errors">
            <ul>
                <?php foreach ($formErrors as $err) { ?>
                <li><?= htmlspecialchars($err) ?></li>
                <?php } ?>
            </ul>
        </div>
        <?php }
        // Clear errors after display
        unset($_SESSION['form_errors']);
        ?>

        <div id="addErrors" style="display:none;" class="errors"></div>

        <form id="formProduit" action="ajouter.php" method="POST">

            <label>Référence</label>
            <input type="text" name="reference" value="<?= htmlspecialchars($old['reference'] ?? '') ?>" required>

            <label>Désignation</label>
            <input type="text" name="designation" value="<?= htmlspecialchars($old['designation'] ?? '') ?>" required>

            <label>Prix d'achat</label>
            <input type="number" name="prix_achat" step="0.01" value="<?= htmlspecialchars($old['prix_achat'] ?? '') ?>"
                required>

            <label>Prix de vente</label>
            <input type="number" name="prix_vente" step="0.01" value="<?= htmlspecialchars($old['prix_vente'] ?? '') ?>"
                required>

            <button type="submit">
                Ajouter
            </button>

        </form>

    </div>


    <!-- Tableau -->

    <div class="table-container">

        <h2>Liste des produits</h2>

        <table>

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Référence</th>
                    <th>Désignation</th>
                    <th>Prix achat</th>
                    <th>Prix vente</th>
                    <th>Description</th>
                    <th>Garantie</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody id="produitTableBody"></tbody>

        </table>

    </div>

    <div id="modifierModal" class="product-modal" aria-hidden="true">
        <div class="product-modal-content" role="dialog" aria-modal="true" aria-labelledby="modifierTitle">
            <button type="button" class="product-modal-close" id="closeModifier">&times;</button>
            <h2 id="modifierTitle">Modifier le produit</h2>

            <form id="formModifierAjax" method="POST">
                <label for="idProduit">ID</label>
                <input type="text" id="idProduit" disabled>
                <input type="hidden" name="id" id="idProduitHidden">

                <label for="referenceProduit">Référence</label>
                <input type="text" name="reference" id="referenceProduit" required>

                <label for="designationProduit">Désignation</label>
                <input type="text" name="designation" id="designationProduit" required>

                <label for="prixAchatProduit">Prix d'achat</label>
                <input type="number" name="prix_achat" id="prixAchatProduit" step="0.01" required>

                <label for="prixVenteProduit">Prix de vente</label>
                <input type="number" name="prix_vente" id="prixVenteProduit" step="0.01" required>

                <button type="submit">Enregistrer</button>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    if (typeof window.jQuery === 'undefined') {
        var operationMessage = document.getElementById('operationMessage');
        operationMessage.textContent = 'Impossible de charger l\'interface AJAX.';
        operationMessage.className += ' error is-visible';
    } else {
        $(function() {
            function showMessage(message, type) {
                var $message = $('#operationMessage');
                $message.removeClass('success error is-visible')
                    .addClass(type + ' is-visible')
                    .text(message);
                window.clearTimeout($message.data('timer'));
                $message.data('timer', window.setTimeout(function() {
                    $message.removeClass('is-visible');
                }, 5000));
            }

            function getErrorMessage(xhr, textStatus) {
                if (textStatus === 'timeout') return 'Le serveur met trop de temps à répondre.';
                if (xhr.status === 0) return 'Impossible de contacter le serveur.';
                if (textStatus === 'parsererror') return 'Réponse JSON incorrecte du serveur.';
                if (xhr.status === 401) return 'Votre session a expiré. Veuillez vous reconnecter.';
                if (xhr.status >= 500) return 'Erreur interne du serveur.';
                if (xhr.responseJSON && xhr.responseJSON.message) return xhr.responseJSON.message;
                return 'Impossible de charger les produits.';
            }

            function renderProducts(products) {
                var $body = $('#produitTableBody').empty();

                $.each(products, function(index, produit) {
                    $body.append(createProductRow(produit));
                });
            }

            function createProductRow(produit) {
                var id = produit.id_produit || produit.id;
                var $row = $('<tr>').attr('data-product-id', id);
                $row.append($('<td>').text(id));
                $row.append($('<td>').text(produit.reference || ''));
                $row.append($('<td>').text(produit.designation || ''));
                $row.append($('<td>').text(produit.prix_achat || ''));
                $row.append($('<td>').text(produit.prix_vente || ''));
                $row.append($('<td>').text(produit.description || ''));
                $row.append($('<td>').text(produit.garantie || ''));
                $row.append($('<td>').append(
                    $('<a>', {
                        class: 'edit-link',
                        href: 'modifier.php?id=' + id,
                        text: 'Modifier'
                    }),
                    ' ',
                    $('<a>', {
                        class: 'delete-link',
                        href: 'supprimer.php?id=' + id,
                        text: 'Supprimer'
                    })
                ));
                return $row;
            }

            function openModifierForm($row) {
                var cells = $row.children('td');
                var id = $row.data('product-id');

                $('#idProduit').val(id);
                $('#idProduitHidden').val(id);
                $('#referenceProduit').val(cells.eq(1).text().trim());
                $('#designationProduit').val(cells.eq(2).text().trim());
                $('#prixAchatProduit').val(cells.eq(3).text().trim());
                $('#prixVenteProduit').val(cells.eq(4).text().trim());
                $('#modifierModal').addClass('is-open').attr('aria-hidden', 'false');
            }

            function closeModifierForm() {
                $('#modifierModal').removeClass('is-open').attr('aria-hidden', 'true');
            }

            $(document).on('click', '.edit-link', function(event) {
                event.preventDefault();
                openModifierForm($(this).closest('tr'));
            });

            $('#closeModifier').on('click', closeModifierForm);
            $('#modifierModal').on('click', function(event) {
                if (event.target === this) closeModifierForm();
            });

            $('#formModifierAjax').on('submit', function(event) {
                event.preventDefault();
                var form = this;
                var id = $('#idProduitHidden').val();
                var $submit = $(form).find('button[type="submit"]');

                $submit.prop('disabled', true);

                $.ajax({
                    url: 'modifier.php',
                    type: 'POST',
                    data: $(form).serialize(),
                    dataType: 'json',
                    timeout: 10000
                }).done(function(response) {
                    if (!response || response.success !== true || !response.data || !response
                        .data.id) {
                        showMessage('Réponse JSON incorrecte du serveur.', 'error');
                        return;
                    }

                    var $oldRow = $('#produitTableBody tr[data-product-id="' + id + '"]');
                    if (!$oldRow.length) {
                        showMessage('Erreur : le produit n\'existe pas dans le tableau.',
                            'error');
                        return;
                    }

                    $oldRow.replaceWith(createProductRow(response.data));
                    closeModifierForm();
                    showMessage(response.message || 'Produit modifié avec succès.', 'success');
                }).fail(function(xhr, textStatus) {
                    var message = 'Erreur lors de la modification du produit.';
                    if (textStatus === 'timeout') message =
                        'Le serveur met trop de temps à répondre.';
                    else if (xhr.status === 0) message = 'Impossible de contacter le serveur.';
                    else if (textStatus === 'parsererror') message =
                        'Réponse JSON incorrecte du serveur.';
                    else if (xhr.responseJSON && xhr.responseJSON.message) message = xhr
                        .responseJSON.message;
                    showMessage(message, 'error');
                }).always(function() {
                    $submit.prop('disabled', false);
                });
            });

            // Step 9: CREATE only, using POST AJAX. READ remains the GET request below.
            $('#formProduit').on('submit', function(event) {
                event.preventDefault();
                var form = this;
                var $submit = $(form).find('button[type="submit"]');

                $submit.prop('disabled', true);

                $.ajax({
                    url: 'ajouter.php',
                    type: 'POST',
                    data: $(form).serialize(),
                    dataType: 'json',
                    timeout: 10000
                }).done(function(response) {
                    if (!response || response.success !== true || !response.data) {
                        showMessage('Réponse JSON incorrecte du serveur.', 'error');
                        return;
                    }

                    $('#produitTableBody').prepend(createProductRow(response.data));
                    form.reset();
                    showMessage(response.message || 'Produit ajouté avec succès.', 'success');
                }).fail(function(xhr, textStatus) {
                    var message = 'Erreur lors de l\'ajout du produit.';

                    if (textStatus === 'timeout') {
                        message = 'Le serveur met trop de temps à répondre.';
                    } else if (xhr.status === 0) {
                        message = 'Impossible de contacter le serveur.';
                    } else if (textStatus === 'parsererror') {
                        message = 'Réponse JSON incorrecte du serveur.';
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                        if (xhr.responseJSON.data && xhr.responseJSON.data.errors) {
                            message += ' ' + xhr.responseJSON.data.errors.join(' ');
                        }
                    }

                    showMessage(message, 'error');
                }).always(function() {
                    $submit.prop('disabled', false);
                });
            });

            // Step 7: READ only, using GET and JSON.
            $.ajax({
                url: 'api/produits.php',
                type: 'GET',
                dataType: 'json',
                timeout: 10000
            }).done(function(response) {
                if (!response || response.success !== true || !Array.isArray(response.data)) {
                    showMessage('Réponse JSON incorrecte du serveur.', 'error');
                    return;
                }
                renderProducts(response.data);
            }).fail(function(xhr, textStatus) {
                showMessage(getErrorMessage(xhr, textStatus), 'error');
            });

            // Keep the existing delete operation as a normal link; only confirm it here.
            $(document).on('click', '.delete-link', function(event) {
                if (!confirm('Confirmer la suppression du produit ?')) {
                    event.preventDefault();
                }
            });
        });
    }
    </script>

</body>

</html>