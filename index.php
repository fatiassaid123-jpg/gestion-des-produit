<?php
session_start();
require_once "connexion.php";

// Authentication: require login to access product pages
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit;
}

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

$stmt = $pdo->prepare('SELECT id, reference, designation, prix_achat, prix_vente FROM produits WHERE id_utilisateur = :id_utilisateur ORDER BY id DESC');
$stmt->execute(['id_utilisateur' => $_SESSION['id_utilisateur']]);
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($produits as $produit) { ?>

                <tr>

                    <td><?= $produit["id"] ?></td>

                    <td><?= $produit["reference"] ?></td>

                    <td><?= $produit["designation"] ?></td>

                    <td><?= $produit["prix_achat"] ?></td>

                    <td><?= $produit["prix_vente"] ?></td>

                    <td>

                        <a href="modifier.php?id=<?= $produit["id"] ?>">
                            Modifier
                        </a>

                        <a class="delete-link" href="supprimer.php?id=<?= $produit["id"] ?>">
                            Supprimer
                        </a>

                    </td>

                </tr>

                <?php } ?>

            </tbody>

        </table>

    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"
        integrity="sha256-/xUj+3OJ+Y3Qv0hZ+8aFQ5c5Y5Q5Y5G5jv2a8a2Y5b8=" crossorigin="anonymous"></script>
    <script>
    $(function() {
        function getExistingRefs() {
            var refs = [];
            $('table tbody tr').each(function() {
                var ref = $(this).find('td').eq(1).text().trim().toLowerCase();
                if (ref) refs.push(ref);
            });
            return refs;
        }

        $('#formProduit').on('submit', function(event) {
            event.preventDefault();
            var reference = $.trim($(this).find('[name="reference"]').val());
            var designation = $.trim($(this).find('[name="designation"]').val());
            var prixA = $(this).find('[name="prix_achat"]').val();
            var prixV = $(this).find('[name="prix_vente"]').val();
            var errors = [];

            if (!reference) errors.push('La référence est obligatoire.');
            if (!designation) errors.push('La désignation est obligatoire.');
            if (reference && (reference.length < 2 || reference.length > 10)) errors.push(
                'La référence doit contenir entre 2 et 10 caractères.');
            if (designation && (designation.length < 3 || designation.length > 100)) errors.push(
                'La désignation doit contenir entre 3 et 100 caractères.');
            if (!prixA || isNaN(prixA)) errors.push('Le prix d\'achat doit être un nombre.');
            else if (parseFloat(prixA) <= 0) errors.push('Le prix d\'achat doit être positif.');
            if (!prixV || isNaN(prixV)) errors.push('Le prix de vente doit être un nombre.');
            else if (parseFloat(prixV) <= 0) errors.push('Le prix de vente doit être positif.');
            if (!isNaN(prixA) && !isNaN(prixV) && parseFloat(prixV) < parseFloat(prixA)) errors.push(
                'Le prix de vente doit être supérieur ou égal au prix d\'achat.');

            var refs = getExistingRefs();
            if (reference && refs.indexOf(reference.toLowerCase()) !== -1) errors.push(
                'La référence existe déjà.');

            if (errors.length) {
                var $err = $('#addErrors');
                $err.html('<ul>' + errors.map(function(e) {
                    return '<li>' + e.replace(/</g, '&lt;') + '</li>';
                }).join('') + '</ul>').show();
                return;
            }

            // submit via AJAX, then reload
            $.post('ajouter.php', $(this).serialize()).done(function() {
                window.location.href = 'index.php';
            }).fail(function() {
                alert('Erreur serveur lors de l\'ajout.');
            });
        });

        // Delete confirmation using jQuery
        $(document).on('click', '.delete-link', function(e) {
            if (!confirm('Confirmer la suppression du produit ?')) {
                e.preventDefault();
            }
        });
    });
    </script>

</body>

</html>