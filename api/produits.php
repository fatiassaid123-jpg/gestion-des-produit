<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_utilisateur'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Utilisateur non authentifie.'
    ]);
    exit;
}

try {
    require_once __DIR__ . '/../connexion.php';

    $stmt = $pdo->prepare(
        "SELECT
            id AS id_produit,
            reference,
            designation,
            prix_achat,
            prix_vente,
            '' AS description,
            '' AS garantie
        FROM produits
        WHERE id_utilisateur = :id_utilisateur
        ORDER BY id DESC"
    );
    $stmt->execute([
        'id_utilisateur' => $_SESSION['id_utilisateur']
    ]);

    echo json_encode([
        'success' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur lors du chargement des produits.'
    ]);
}