const API_URL = "produitconroler.php";

/* ==========================================
   READ - Afficher les produits
========================================== */

function loadProduits() {

    $.ajax({

        url: API_URL,
        method: "GET",
        dataType: "json",

        success: function (produits) {

            $("#produitsTable").empty();

            produits.forEach(function (produit) {

                $("#produitsTable").append(`
                    <tr>

                        <td>${produit.id}</td>

                        <td>${produit.reference}</td>

                        <td>${produit.designation}</td>

                        <td>${produit.prix_achat}</td>

                        <td>${produit.prix_vente}</td>

                        <td>

                            <button
                                class="edit"
                                onclick="editProduit(${produit.id})">
                                Modifier
                            </button>

                            <button
                                class="delete"
                                onclick="deleteProduit(${produit.id})">
                                Supprimer
                            </button>

                        </td>

                    </tr>
                `);
            });
        },

        error: function () {
            alert("Erreur lors du chargement des produits");
        }
    });
}


/* ==========================================
   CREATE / UPDATE
========================================== */

$("#produitForm").submit(function (event) {

    event.preventDefault();

    const id = $("#id").val();

    const produit = {

        id_utilisateur: $("#id_utilisateur").val(),
        reference: $("#reference").val(),
        designation: $("#designation").val(),
        prix_achat: $("#prix_achat").val(),
        prix_vente: $("#prix_vente").val()
    };

    let method = "POST";

    /* UPDATE */

    if (id !== "") {

        method = "PUT";

        produit.id = id;
    }


    $.ajax({

        url: API_URL,

        method: method,

        contentType: "application/json",

        data: JSON.stringify(produit),

        success: function (response) {

            alert(response.message);

            resetForm();

            loadProduits();
        },

        error: function (xhr) {

            let message = "Une erreur est survenue";

            if (xhr.responseJSON) {

                message = xhr.responseJSON.error;
            }

            alert(message);
        }
    });
});


/* ==========================================
   EDIT
========================================== */

function editProduit(id) {

    $.ajax({

        url: API_URL,

        method: "GET",

        dataType: "json",

        success: function (produits) {

            const produit = produits.find(
                element => element.id == id
            );

            if (!produit) {
                return;
            }

            $("#id").val(produit.id);

            $("#id_utilisateur").val(produit.id_utilisateur);

            $("#reference").val(produit.reference);

            $("#designation").val(produit.designation);

            $("#prix_achat").val(produit.prix_achat);

            $("#prix_vente").val(produit.prix_vente);
        }
    });
}


/* ==========================================
   DELETE
========================================== */

function deleteProduit(id) {

    if (!confirm("Voulez-vous supprimer ce produit ?")) {
        return;
    }


    $.ajax({

        url: API_URL,

        method: "DELETE",

        contentType: "application/json",

        data: JSON.stringify({
            id: id
        }),

        success: function (response) {

            alert(response.message);

            loadProduits();
        },

        error: function () {

            alert("Erreur lors de la suppression");
        }
    });
}


/* ==========================================
   RESET
========================================== */

function resetForm() {

    $("#id").val("");

    $("#id_utilisateur").val("");

    $("#reference").val("");

    $("#designation").val("");

    $("#prix_achat").val("");

    $("#prix_vente").val("");
}


/* ==========================================
   CANCEL
========================================== */

$("#cancel").click(function () {

    resetForm();
});


/* ==========================================
   INITIALISATION
========================================== */

$(document).ready(function () {
    console.log("page loaded")
   

loadProduits();
});