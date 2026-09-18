<?php
session_start();
require_once "connexion.php";

$errors = [];
$old = ['nom'=>'','email'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    $old['nom'] = $nom;
    $old['email'] = $email;

    // validations
    if ($nom === '') $errors[] = 'Le nom est obligatoire.';
    if ($email === '') $errors[] = 'L\'email est obligatoire.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format d\'email invalide.';
    if ($password === '') $errors[] = 'Le mot de passe est obligatoire.';
    if ($password2 === '') $errors[] = 'La confirmation du mot de passe est obligatoire.';
    if ($password !== '' && $password2 !== '' && $password !== $password2) $errors[] = 'Les mots de passe ne correspondent pas.';

    if (empty($errors)) {
        // ensure users table exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS utilisateurs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            mot_de_passe VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // check existing email
        $stmt = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = :email');
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Cet email est déjà utilisé.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare('INSERT INTO utilisateurs (nom, email, mot_de_passe) VALUES (:nom, :email, :mp)');
            $ins->execute(['nom'=>$nom, 'email'=>$email, 'mp'=>$hash]);
            header('Location: login.php');
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>GESTION PRODUITS — Inscription</title>
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body class="auth-page">

    <main class="auth-card" role="main">
        <div class="auth-logo">GESTION PRODUITS</div>
        <div class="auth-subtitle">Créer un compte</div>

        <?php if (!empty($errors)) { ?>
            <div class="auth-errors" id="serverErrors">
                <ul><?php foreach ($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?></ul>
            </div>
        <?php } ?>

        <div id="clientErrors" style="display:none" class="auth-errors"></div>

        <form id="formInscription" class="auth-form" action="inscription.php" method="POST">
            <input class="auth-input" type="text" name="nom" placeholder="Nom" value="<?= htmlspecialchars($old['nom']) ?>" required>
            <input class="auth-input" type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($old['email']) ?>" required>
            <input class="auth-input" type="password" name="password" placeholder="Mot de passe" required>
            <input class="auth-input" type="password" name="password2" placeholder="Confirmer le mot de passe" required>

            <button class="auth-button" type="submit">S'inscrire</button>
        </form>

        <div class="auth-footer">
            <p>Vous avez déjà un compte ? <a class="auth-link" href="login.php">Connectez-vous</a></p>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(function(){
        $('#formInscription').on('submit', function(e){
            e.preventDefault();
            $('#clientErrors').hide().empty();
            var nom = $.trim($(this).find('[name="nom"]').val());
            var email = $.trim($(this).find('[name="email"]').val());
            var p1 = $(this).find('[name="password"]').val();
            var p2 = $(this).find('[name="password2"]').val();
            var errs = [];
            if (!nom) errs.push('Le nom est obligatoire.');
            if (!email) errs.push('L\'email est obligatoire.');
            else if (!/^\S+@\S+\.\S+$/.test(email)) errs.push('Le format de l\'email est invalide.');
            if (!p1) errs.push('Le mot de passe est obligatoire.');
            if (!p2) errs.push('La confirmation du mot de passe est obligatoire.');
            if (p1 && p2 && p1 !== p2) errs.push('Les mots de passe ne correspondent pas.');

            if (errs.length) {
                $('#clientErrors').html('<ul>' + errs.map(function(e){ return '<li>'+e.replace(/</g,'&lt;')+'</li>'; }).join('') + '</ul>').show();
                return;
            }

            this.submit();
        });
    });
    </script>

</body>
</html>
