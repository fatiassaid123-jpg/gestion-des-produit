<?php
session_start();
// Protection: require authenticated user
if (!isset($_SESSION['id_utilisateur'])) {
	header('Location: login.php');
	exit;
}

$id = $_GET['id'] ?? null;

if ($id === null || $id === '') {
	header('Location: index.php');
	exit;
}

if (!isset($_SESSION['produits'])) $_SESSION['produits'] = [];

foreach ($_SESSION['produits'] as $idx => $p) {
	if ($p['id'] == $id) {
		array_splice($_SESSION['produits'], $idx, 1);
		break;
	}
}

header('Location: index.php');
exit;

?>