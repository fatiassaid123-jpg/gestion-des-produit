<?php
session_start();
// Protection: require authenticated user
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit;
}
require_once "connexion.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $reference = trim($_POST['reference'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $prix_achat = $_POST['prix_achat'] ?? '';
    $prix_vente = $_POST['prix_vente'] ?? '';

    $errors = [];

    if ($id === null || $id === '') {
        header('Location: index.php');
        exit;
    }

    // Required
    if ($reference === '') $errors[] = 'La référence est obligatoire.';
    if ($designation === '') $errors[] = 'La désignation est obligatoire.';

    // Lengths
    if ($reference !== '' && (strlen($reference) < 2 || strlen($reference) > 10)) $errors[] = 'La référence doit contenir entre 2 et 10 caractères.';
    if ($designation !== '' && (strlen($designation) < 3 || strlen($designation) > 100)) $errors[] = 'La désignation doit contenir entre 3 et 100 caractères.';

    // Numeric and positive
    if ($prix_achat === '' || !is_numeric($prix_achat)) $errors[] = 'Le prix d\'achat doit être un nombre.';
    elseif (floatval($prix_achat) <= 0) $errors[] = 'Le prix d\'achat doit être positif.';

    if ($prix_vente === '' || !is_numeric($prix_vente)) $errors[] = 'Le prix de vente doit être un nombre.';
    elseif (floatval($prix_vente) <= 0) $errors[] = 'Le prix de vente doit être positif.';

    if (is_numeric($prix_achat) && is_numeric($prix_vente) && floatval($prix_vente) < floatval($prix_achat)) {
        $errors[] = 'Le prix de vente doit être supérieur ou égal au prix d\'achat.';
    }

    // Uniqueness: reference excluding current product
    $check = $pdo->prepare('SELECT id FROM produits WHERE id_utilisateur = :id_utilisateur AND LOWER(reference) = LOWER(:reference) AND id <> :id');
    $check->execute([
        'id_utilisateur' => $_SESSION['id_utilisateur'],
        'reference' => $reference,
        'id' => $id
    ]);
    if ($check->fetch()) {
        $errors[] = 'La référence existe déjà.';
    }

    if (!empty($errors)) {
        $_SESSION['form_errors_modify'] = $errors;
        $_SESSION['old_modify'] = [
            'id' => $id,
            'reference' => $reference,
            'designation' => $designation,
            'prix_achat' => $prix_achat,
            'prix_vente' => $prix_vente
        ];

        header('Location: modifier.php?id=' . urlencode($id));
        exit;
    }

    $update = $pdo->prepare('UPDATE produits SET reference = :reference, designation = :designation, prix_achat = :prix_achat, prix_vente = :prix_vente WHERE id = :id AND id_utilisateur = :id_utilisateur');
    $update->execute([
        'reference' => $reference,
        'designation' => $designation,
        'prix_achat' => floatval($prix_achat),
        'prix_vente' => floatval($prix_vente),
        'id' => $id,
        'id_utilisateur' => $_SESSION['id_utilisateur']
    ]);

    // Clear errors/old
    unset($_SESSION['form_errors_modify'], $_SESSION['old_modify']);

    header('Location: index.php');
    exit;
}

$id = $_GET['id'] ?? null;

if ($id === null || $id === '') {
    header('Location: index.php');
    exit;
}

$select = $pdo->prepare('SELECT id, reference, designation, prix_achat, prix_vente FROM produits WHERE id = :id AND id_utilisateur = :id_utilisateur');
$select->execute([
    'id' => $id,
    'id_utilisateur' => $_SESSION['id_utilisateur']
]);
$produit = $select->fetch(PDO::FETCH_ASSOC);

if ($produit === null) {
    header('Location: index.php');
    exit;
}

$otherRefs = [];
$refStmt = $pdo->prepare('SELECT reference FROM produits WHERE id_utilisateur = :id_utilisateur AND id <> :id');
$refStmt->execute([
    'id_utilisateur' => $_SESSION['id_utilisateur'],
    'id' => $id
]);
$otherRefs = array_map('strtolower', $refStmt->fetchAll(PDO::FETCH_COLUMN));

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <title>Modifier produit</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

    <h1>Modifier le produit</h1>

    <div class="form-container">

        <form id="formModifier" action="modifier.php" method="POST">

            <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">

            <?php
            $errors = $_SESSION['form_errors_modify'] ?? [];
            $old = $_SESSION['old_modify'] ?? null;
            if (!empty($errors)) {
                echo '<div class="errors"><ul>';
                foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>';
                echo '</ul></div>';
                unset($_SESSION['form_errors_modify']);
            }

            $refVal = $old['reference'] ?? $produit['reference'];
            $desVal = $old['designation'] ?? $produit['designation'];
            $paVal = $old['prix_achat'] ?? $produit['prix_achat'];
            $pvVal = $old['prix_vente'] ?? $produit['prix_vente'];
            ?>

            <label>Référence</label>

            <input type="text" name="reference" value="<?= htmlspecialchars($refVal) ?>" required>


            <label>Désignation</label>

            <input type="text" name="designation" value="<?= htmlspecialchars($desVal) ?>" required>


            <label>Prix d'achat</label>

            <input type="number" name="prix_achat" value="<?= htmlspecialchars($paVal) ?>" required>


            <label>Prix de vente</label>

            <input type="number" name="prix_vente" value="<?= htmlspecialchars($pvVal) ?>" required>


            <button type="submit">
                Enregistrer
            </button>

        </form>

    </div>

    <a href="index.php">
        Retour
    </a>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJ+Y3Qv0hZ+8aFQ5c5Y5Q5Y5G5jv2a8a2Y5b8=" crossorigin="anonymous"></script>
    <script>
    $(function(){
        var otherRefs = <?= json_encode($otherRefs) ?>;

        $('#formModifier').on('submit', function(e){
            e.preventDefault();
            var reference = $.trim($(this).find('[name="reference"]').val());
            var designation = $.trim($(this).find('[name="designation"]').val());
            var prixA = $(this).find('[name="prix_achat"]').val();
            var prixV = $(this).find('[name="prix_vente"]').val();
            var errors = [];

            if (!reference) errors.push('La référence est obligatoire.');
            if (!designation) errors.push('La désignation est obligatoire.');
            if (reference && (reference.length < 2 || reference.length > 10)) errors.push('La référence doit contenir entre 2 et 10 caractères.');
            if (designation && (designation.length < 3 || designation.length > 100)) errors.push('La désignation doit contenir entre 3 et 100 caractères.');
            if (!prixA || isNaN(prixA)) errors.push('Le prix d\'achat doit être un nombre.');
            else if (parseFloat(prixA) <= 0) errors.push('Le prix d\'achat doit être positif.');
            if (!prixV || isNaN(prixV)) errors.push('Le prix de vente doit être un nombre.');
            else if (parseFloat(prixV) <= 0) errors.push('Le prix de vente doit être positif.');
            if (!isNaN(prixA) && !isNaN(prixV) && parseFloat(prixV) < parseFloat(prixA)) errors.push('Le prix de vente doit être supérieur ou égal au prix d\'achat.');

            if (reference && otherRefs.indexOf(reference.toLowerCase()) !== -1) errors.push('La référence existe déjà.');

            if (errors.length){
                var html = '<div class="errors"><ul>' + errors.map(function(e){ return '<li>' + e.replace(/</g,'&lt;') + '</li>'; }).join('') + '</ul></div>';
                // insert before the form
                $('.form-container').prepend(html);
                return;
            }

            $.post('modifier.php', $(this).serialize()).done(function(){
                window.location.href = 'index.php';
            }).fail(function(){
                alert('Erreur serveur lors de la modification.');
            });
        });
    });
    </script>

</body>

</html>