<?php
session_start();
// Protection: require authenticated user
if (!isset($_SESSION['id_utilisateur'])) {
	header('Location: login.php');
	exit;
}
require_once "connexion.php";

$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

function deleteResponse(bool $success, string $message, int $status = 200): void
{
	global $isAjax;

	if ($isAjax) {
		http_response_code($status);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(['success' => $success, 'message' => $message]);
		exit;
	}
}

$id = $_GET['id'] ?? null;

if ($id === null || $id === '') {
	deleteResponse(false, 'Erreur : le produit n\'existe pas.', 404);
	$_SESSION['flash_message'] = 'Erreur : le produit n\'existe pas.';
	header('Location: index.php');
	exit;
}

try {
	$delete = $pdo->prepare('DELETE FROM produits WHERE id = :id AND id_utilisateur = :id_utilisateur');
	$delete->execute([
		'id' => $id,
		'id_utilisateur' => $_SESSION['id_utilisateur']
	]);
} catch (PDOException $e) {
	$_SESSION['flash_message'] = 'Erreur serveur : impossible de supprimer le produit.';
	deleteResponse(false, 'Erreur serveur : impossible de supprimer le produit.', 500);
	header('Location: index.php');
	exit;
}

if ($delete->rowCount() === 0) {
	deleteResponse(false, 'Erreur : le produit n\'existe pas.', 404);
	$_SESSION['flash_message'] = 'Erreur : le produit n\'existe pas.';
}

deleteResponse(true, 'Produit supprime avec succes.');

$_SESSION['flash_message'] = 'Produit supprime avec succes.';

header('Location: index.php');
exit;

?>