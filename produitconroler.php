<?php
 
    header("Content-Type: application/json; charset=UTF-8");
 
    require_once "database.php";
 
    $method = $_SERVER["REQUEST_METHOD"];
 
    switch ($method) {
 
    // =========================
    // GET
    // =========================
    case "GET":
 
        $stmt = $pdo->query(
            "SELECT * FROM produits ORDER BY id DESC"
        );
 
        $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
        echo json_encode($produits);
 
        break;
 
 
    // =========================
    // POST
    // =========================
case "POST":

    $data = json_decode(
        file_get_contents("php://input"),
        true
    );

    if (
        empty($data["id_utilisateur"]) ||
        empty($data["reference"]) ||
        empty($data["designation"]) ||
        empty($data["prix_achat"]) ||
        empty($data["prix_vente"])
    ) {
        http_response_code(400);

        echo json_encode([
            "error" => "Tous les champs sont obligatoires"
        ]);

        exit;
    }

    $sql = "
        INSERT INTO produits
        (reference, designation, id_utilisateur, prix_achat, prix_vente)
        VALUES (?, ?, ?, ?, ?)
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $data["reference"],
        $data["designation"],
        $data["id_utilisateur"],
        $data["prix_achat"],
        $data["prix_vente"]
    ]);

    echo json_encode([
        "message" => "Produit ajouté avec succès",
        "id" => $pdo->lastInsertId(),
        "data"=>$data
    ]);

    break;
 
    // =========================
    // PUT
    // =========================
 case "PUT":

    $data = json_decode(
        file_get_contents("php://input"),
        true
    );

    if (empty($data["id"])) {

        http_response_code(400);

        echo json_encode([
            "error" => "ID obligatoire"
        ]);

        exit;
    }

    $sql = "
        UPDATE produits
        SET reference = ?,
            designation = ?,
            id_utilisateur = ?,
            prix_achat = ?,
            prix_vente = ?
        WHERE id = ?
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $data["reference"],
        $data["designation"],
        $data["id_utilisateur"],
        $data["prix_achat"],
        $data["prix_vente"],
        $data["id"]
    ]);

    echo json_encode([
        "message" => "Produit modifié avec succès"
    ]);

    break;
 
 
    // =========================
    // DELETE
    // =========================
    case "DELETE":
 
        $data = json_decode(
            file_get_contents("php://input"),
            true
        );
 
        if (empty($data["id"])) {
 
            http_response_code(400);
 
            echo json_encode([
                "error" => "ID obligatoire"
            ]);
 
            exit;
        }
 
        $stmt = $pdo->prepare(
            "DELETE FROM produits WHERE id = ?"
        );
 
        $stmt->execute([
            $data["id"]
        ]);
 
        echo json_encode([
            "message" => "produit supprimé avec succès"
        ]);
 
        break;
 
 
    // =========================
    // METHOD NOT ALLOWED
    // =========================
    default:
 
        http_response_code(405);
 
        echo json_encode([
            "error" => "Méthode HTTP non autorisée"
        ]);
 
        break;
}