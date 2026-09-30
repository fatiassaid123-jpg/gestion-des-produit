<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestion des produits</title>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 40px;
    }

    h1 {
        margin-bottom: 30px;
    }

    form {
        margin-bottom: 30px;
    }

    input {
        padding: 10px;
        margin-right: 5px;
    }

    button {
        padding: 10px 15px;
        cursor: pointer;
    }

    table {
        border-collapse: collapse;
        width: 100%;
    }

    th,
    td {
        border: 1px solid #ccc;
        padding: 10px;
        text-align: left;
    }

    th {
        background: #eee;
    }

    .edit {
        background: #ddd;
        border: 1px solid #aaa;
    }

    .delete {
        background: #ddd;
        border: 1px solid #aaa;
    }
    </style>

</head>


<body>

    <h1>Gestion des produits</h1>


    <!-- ========================= -->
    <!-- FORMULAIRE -->
    <!-- ========================= -->

    <form id="produitForm">

        <!-- ID caché : utilisé seulement pour la modification -->
        <input type="hidden" id="id">


        <input type="number" id="id_utilisateur" placeholder="ID utilisateur" required>


        <input type="text" id="reference" placeholder="Référence" required>


        <input type="text" id="designation" placeholder="Désignation" required>


        <input type="number" step="0.01" id="prix_achat" placeholder="Prix achat" required>


        <input type="number" step="0.01" id="prix_vente" placeholder="Prix vente" required>


        <button type="submit">
            Enregistrer
        </button>


        <button type="button" id="cancel">
            Annuler
        </button>

    </form>


    <!-- ========================= -->
    <!-- TABLE -->
    <!-- ========================= -->

    <table>

        <thead>

            <tr>

                <th>ID</th>

                <th>ID Utilisateur</th>

                <th>Référence</th>

                <th>Désignation</th>

                <th>Prix achat</th>

                <th>Prix vente</th>

                <th>Actions</th>

            </tr>

        </thead>


        <tbody id="produitsTable">
        </tbody>

    </table>


    <!-- JavaScript -->
    <script src="produitAPI.js"> </script>

</body>

</html>