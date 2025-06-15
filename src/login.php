<?php 
session_start(); 
include "pdodb.php";

function validate($data){
   return trim(stripslashes($data));
}

if (isset($_POST['email']) && isset($_POST['password'])) {
    $email = validate($_POST['email']);
    $password = validate($_POST['password']);

    if (empty($email)) {
        header("Location: index.php?error=Le champ email est obligatoire");
        exit();

    } else if (empty($password)) {
        header("Location: index.php?error=Le champ mot de passe est obligatoire");
        exit();

    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE Email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($result) === 1) {
            $row = $result[0];

            if (password_verify($password, $row['MotDePasse'])) {
                $_SESSION['email'] = $row['Email'];
                $_SESSION['name'] = $row['NomComplet'];
                $_SESSION['iduser'] = $row['IdUser'];
                $_SESSION['role'] = $row['role'];

                header("Location: homehandler.php");
                exit();
            } else {
                header("Location: index.php?error=Adresse e-mail ou mot de passe incorrect.");
                exit();
            }
        } else {
            header("Location: index.php?error=Adresse e-mail ou mot de passe incorrect.");
            exit();
        }
    }
} else {
    header("Location: index.php");
    exit();
}
?>
