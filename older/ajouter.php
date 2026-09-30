<?php
session_start();
// Protection: require authenticated user
if (!isset($_SESSION['id_utilisateur'])) {
	header('Location: login.php');
	exit;
}
require_once "connexion.php";

$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

function addResponse(bool $success, string $message, array $data = [], int $status = 200): void
{
	global $isAjax;

	if ($isAjax) {
		http_response_code($status);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode([
			'success' => $success,
			'message' => $message,
			'data' => $data
		]);
		exit;
	}
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

try {
	// Uniqueness: reference for the current user
	$check = $pdo->prepare('SELECT id FROM produits WHERE id_utilisateur = :id_utilisateur AND LOWER(reference) = LOWER(:reference)');
	$check->execute([
		'id_utilisateur' => $_SESSION['id_utilisateur'],
		'reference' => $reference
	]);
	if ($check->fetch()) $errors[] = 'La référence existe déjà.';
} catch (PDOException $e) {
	$_SESSION['flash_message'] = 'Erreur serveur : impossible de vérifier la référence.';
	addResponse(false, 'Erreur serveur : impossible de vérifier la référence.', [], 500);
	header('Location: index.php');
	exit;
}

// If errors, save and redirect back
if (!empty($errors)) {
	addResponse(false, 'Erreur : donnees invalides.', ['errors' => $errors], 422);
	$_SESSION['flash_message'] = 'Erreur : donnees invalides.';
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

try {
	// Create the product in MySQL
	$insert = $pdo->prepare('INSERT INTO produits (id_utilisateur, reference, designation, prix_achat, prix_vente) VALUES (:id_utilisateur, :reference, :designation, :prix_achat, :prix_vente)');
	$insert->execute([
		'id_utilisateur' => $_SESSION['id_utilisateur'],
		'reference' => $reference,
		'designation' => $designation,
		'prix_achat' => floatval($prix_achat),
		'prix_vente' => floatval($prix_vente)
	]);
} catch (PDOException $e) {
	$_SESSION['flash_message'] = 'Erreur serveur : impossible d\'ajouter le produit.';
	addResponse(false, 'Erreur serveur : impossible d\'ajouter le produit.', [], 500);
	header('Location: index.php');
	exit;
}

$productId = (int) $pdo->lastInsertId();
addResponse(true, 'Produit ajoute avec succes.', [
	'id' => $productId,
	'reference' => $reference,
	'designation' => $designation,
	'prix_achat' => number_format((float) $prix_achat, 2, '.', ''),
	'prix_vente' => number_format((float) $prix_vente, 2, '.', '')
]);

$_SESSION['flash_message'] = 'Produit ajoute avec succes.';

// Clear any old form data/errors
unset($_SESSION['form_errors'], $_SESSION['old_add']);

header('Location: index.php');
exit;

?>