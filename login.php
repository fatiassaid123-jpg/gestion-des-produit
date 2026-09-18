<?php
session_start();
require_once "connexion.php";

$errors = [];
$old = ['email'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $old['email'] = $email;

    if ($email === '') $errors[] = 'L\'email est obligatoire.';
    if ($password === '') $errors[] = 'Le mot de passe est obligatoire.';

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id, nom, email, mot_de_passe FROM utilisateurs WHERE email = :email');
        $stmt->execute(['email'=>$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || !password_verify($password, $user['mot_de_passe'])) {
            $errors[] = 'Email ou mot de passe incorrect.';
        } else {
            // login OK
            $_SESSION['id_utilisateur'] = $user['id'];
            $_SESSION['nom'] = $user['nom'];
            $_SESSION['email'] = $user['email'];
            header('Location: index.php');
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>GESTION PRODUITS — Connexion</title>
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
    /* keep auth pages isolated if style.css missing */
    </style>
</head>

<body class="auth-page">

    <main class="auth-card" role="main">
        <div class="auth-logo">GESTION PRODUITS</div>
        <div class="auth-subtitle">Se connecter à votre compte</div>

        <?php if (!empty($errors)) { ?>
        <div class="auth-errors" id="serverErrors">
            <ul><?php foreach ($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?></ul>
        </div>
        <?php } ?>

        <div id="clientErrors" style="display:none" class="auth-errors"></div>

        <form id="formLogin" class="auth-form" action="login.php" method="POST">
            <input class="auth-input" type="email" name="email" placeholder="Email"
                value="<?= htmlspecialchars($old['email']) ?>" required>
            <input class="auth-input" type="password" name="password" placeholder="Mot de passe" required>

            <div class="auth-buttons">
                <button class="auth-button" type="submit">Se connecter</button>
            </div>
        </form>

        <div class="auth-footer">
            <p>Vous n'avez pas de compte ? <a class="auth-link" href="inscription.php">Inscrivez-vous</a></p>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(function() {
        $('#formLogin').on('submit', function(e) {
            e.preventDefault();
            $('#clientErrors').hide().empty();
            var email = $.trim($(this).find('[name="email"]').val());
            var pass = $(this).find('[name="password"]').val();
            var errs = [];
            if (!email) errs.push('L\'email est obligatoire.');
            else if (!/^\S+@\S+\.\S+$/.test(email)) errs.push('Le format de l\'email est invalide.');
            if (!pass) errs.push('Le mot de passe est obligatoire.');

            if (errs.length) {
                $('#clientErrors').html('<ul>' + errs.map(function(e) {
                    return '<li>' + e.replace(/</g, '&lt;') + '</li>';
                }).join('') + '</ul>').show();
                return;
            }

            // no AJAX: submit normally after client-side validation
            this.submit();
        });
    });
    </script>

</body>

</html>