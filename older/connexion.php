<?php

$host = "localhost";
$dbname = "gestion_produits";
$user = "root";
$password = "root123";

try {

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $user,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Products are stored in MySQL and linked to the authenticated user.
    $pdo->exec("CREATE TABLE IF NOT EXISTS produits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_utilisateur INT NOT NULL,
        reference VARCHAR(20) NOT NULL,
        designation VARCHAR(100) NOT NULL,
        prix_achat DECIMAL(10,2) NOT NULL,
        prix_vente DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_reference (id_utilisateur, reference),
        INDEX idx_produits_utilisateur (id_utilisateur)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

} catch (PDOException $e) {

    die("Erreur de connexion : " . $e->getMessage());

}
?>