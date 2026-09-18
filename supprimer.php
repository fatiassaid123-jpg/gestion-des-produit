<?php
session_start();
// Protection: require authenticated user
if (!isset($_SESSION['id_utilisateur'])) {
	header('Location: login.php');
	exit;
}
require_once "connexion.php";

$id = $_GET['id'] ?? null;

if ($id === null || $id === '') {
	header('Location: index.php');
	exit;
}

$delete = $pdo->prepare('DELETE FROM produits WHERE id = :id AND id_utilisateur = :id_utilisateur');
$delete->execute([
	'id' => $id,
	'id_utilisateur' => $_SESSION['id_utilisateur']
]);

header('Location: index.php');
exit;

?>