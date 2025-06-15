<?php
session_start();
if (!isset($_SESSION['email'])) {
    // If they're not logged in, send them back to login
    header("Location: index.php");
    exit(); 
}

// Redirect based on role
switch ($_SESSION['role']) {
    case 'client':
        header("Location: client/homepage.php");
        break;
    
    case 'fabricant':
        header("Location: fabricant/homepage.php");
        break;
    
    case 'fournisseur':
        header("Location: fournisseur/homepage.php");
        break;
    
    default:
        // If role is not recognized, redirect to login
        header("Location: index.php");
        break;
}
exit();