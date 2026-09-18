<?php
session_start();
// Protection: require authenticated user
if (!isset($_SESSION['id_utilisateur'])) {
	header('Location: login.php');
	exit;
}

$reference = trim($_POST['reference'] ?? '');
$designation = trim($_POST['designation'] ?? '');
$prix_achat = $_POST['prix_achat'] ?? '';
$prix_vente = $_POST['prix_vente'] ?? '';

$errors = [];

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

// Ensure session products exist
if (!isset($_SESSION['produits'])) $_SESSION['produits'] = [];

// Uniqueness: reference
foreach ($_SESSION['produits'] as $p) {
	if (strtolower($p['reference']) === strtolower($reference)) {
		$errors[] = 'La référence existe déjà.';
		break;
	}
}

// If errors, save and redirect back
if (!empty($errors)) {
	$_SESSION['form_errors'] = $errors;
	$_SESSION['old_add'] = [
		'reference' => $reference,
		'designation' => $designation,
		'prix_achat' => $prix_achat,
		'prix_vente' => $prix_vente
	];

	header('Location: index.php');
	exit;
}

// Create new product
$maxId = 0;
foreach ($_SESSION['produits'] as $p) {
	if ($p['id'] > $maxId) $maxId = $p['id'];
}

$newProduit = [
	'id' => $maxId + 1,
	'reference' => $reference,
	'designation' => $designation,
	'prix_achat' => floatval($prix_achat),
	'prix_vente' => floatval($prix_vente)
];

$_SESSION['produits'][] = $newProduit;

// Clear any old form data/errors
unset($_SESSION['form_errors'], $_SESSION['old_add']);

header('Location: index.php');
exit;

?>