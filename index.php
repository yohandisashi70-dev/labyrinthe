<?php
session_start();
if (!isset($_SESSION['inventaire'])) {
    $_SESSION['inventaire'] = [];
}
if (!isset($_SESSION['grilles_ouvertes'])) {
    $_SESSION['grilles_ouvertes'] = [];
}

// Handler pour nouvelle partie (réinitialise l'inventaire et les grilles)
if (isset($_GET['nouvelle']) && $_GET['nouvelle'] == '1') {
    $_SESSION['inventaire'] = [];
    $_SESSION['grilles_ouvertes'] = [];
    header('Location: index.php');
    exit;
}

// Connexion à la base de données
$bdd_fichier = 'labyrinthe.db';
$sqlite = new SQLite3($bdd_fichier);

// Couloir actuel (par défaut 13)
$current_couloir_id = isset($_GET['couloir']) ? intval($_GET['couloir']) : 13;

// Vérifier si le couloir est bloqué par une grille
if (($current_couloir_id == 11 || $current_couloir_id == 20) && !in_array($current_couloir_id, $_SESSION['grilles_ouvertes'])) {
    if (!in_array('cle', $_SESSION['inventaire']) && !in_array('cle16', $_SESSION['inventaire'])) {
        // Pas de clé, on le refuse d'entrer
        echo "<!DOCTYPE html>";
        echo "<html lang='fr'><head><meta charset='UTF-8'><title>Labyrinthe</title></head><body>";
        echo "<h3> Nombre d'objets dans l'inventaire : " . count($_SESSION['inventaire']) . "</h3>";
        echo "<style> body { font-family: Arial, sans-serif; background-color: #f0f0f0; color: #333; } h1 { color: #2c3e50; } a { text-decoration: none; color: #2980b9; } a:hover { text-decoration: underline; } </style>";
        echo "<h1>ACCÈS REFUSÉ !</h1>";
        echo "<h2 style='color:red;'>Il y a une grille ! Tu as besoin d'une clé pour passer !</h2>";
        echo "<p><a href='index.php'>Retourner au labyrinthe</a></p>";
        echo "</body></html>";
        $sqlite->close();
        exit;
    } else {
        // A une clé, on ouvre la grille
        if (in_array('cle', $_SESSION['inventaire'])) {
            $_SESSION['inventaire'] = array_diff($_SESSION['inventaire'], ['cle']);
        } else {
            $_SESSION['inventaire'] = array_diff($_SESSION['inventaire'], ['cle16']);
        }
        $_SESSION['grilles_ouvertes'][] = $current_couloir_id;
    }
}

// Requête pour obtenir les couloirs accessibles
$sql = "SELECT CASE WHEN couloir1 = :current_couloir_id THEN couloir2 ELSE couloir1 END AS couloir_accessible FROM passage WHERE couloir1 = :current_couloir_id OR couloir2 = :current_couloir_id";
$requete = $sqlite->prepare($sql);
$requete->bindValue(':current_couloir_id', $current_couloir_id, SQLITE3_INTEGER);
$result = $requete->execute();

echo "<!DOCTYPE html>";
echo "<html lang='fr'><head><meta charset='UTF-8'><title>Labyrinthe</title>";
echo "<link rel='stylesheet' href='/*Styles geénéral*/'>";
echo "</head><body>";
echo "<style> body { font-family: Arial, sans-serif; background-color: #f0f0f0; color: #333; } h1 { color: #2c3e50; } a { text-decoration: none; color: #2980b9; } a:hover { text-decoration: underline; } ul { list-style-type: none; padding: 0; } li { margin: 5px 0; } </style>";
echo "<h1>Tu es dans le couloir de  $current_couloir_id</h1>";
echo "<p><a href='?nouvelle=1'>Nouvelle partie</a></p>";
echo "<hr>";
echo "<h3> Nombre d'objets dans l'inventaire : " . count($_SESSION['inventaire']) . "</h3>";

if ($current_couloir_id == 26) {
    echo "<h2 style='color:green;'>Vous êtes sorti du labyrinthe !</h2>";
} else {
    echo "<h2>Couloirs accessibles :</h2><ul>";
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        echo "<li><a href='?couloir=" . $row['couloir_accessible'] . "'>Couloir " . $row['couloir_accessible'] . "</a></li>";
    }
    echo "</ul>";
    echo "<hr>";

    // Ramasser la clé dans le couloir 3
    if ($current_couloir_id == 3 && !in_array('cle', $_SESSION['inventaire'])) {
        $_SESSION['inventaire'][] = 'cle';
        echo "<h2 style='color:red;'>Tu as ramassé une clé !</h2>";
    }

    // Ramasser une clé dans le couloir 16
    if ($current_couloir_id == 16 && !in_array('cle16', $_SESSION['inventaire'])) {
        $_SESSION['inventaire'][] = 'cle16';
        echo "<h2 style='color:orange;'>Tu as ramassé une clé dans le couloir 16 !</h2>";
    }

    // Grille dans le couloir 11 - utiliser une clé pour passer
    if ($current_couloir_id == 11 && !in_array(11, $_SESSION['grilles_ouvertes'])) {
        if (!in_array('cle', $_SESSION['inventaire']) && !in_array('cle16', $_SESSION['inventaire'])) {
            echo "<h2 style='color:red;'>Il y a une grille ! Tu as besoin d'une clé pour passer !</h2>";
        } else {
            if (in_array('cle', $_SESSION['inventaire'])) {
                $_SESSION['inventaire'] = array_diff($_SESSION['inventaire'], ['cle']);
            } else {
                $_SESSION['inventaire'] = array_diff($_SESSION['inventaire'], ['cle16']);
            }
            $_SESSION['grilles_ouvertes'][] = 11;
            echo "<h2 style='color:green;'>Tu as utilisé une clé pour ouvrir la grille !</h2>";
        }
    }

    // Grille dans le couloir 20 - utiliser une clé pour passer
    if ($current_couloir_id == 20 && !in_array(20, $_SESSION['grilles_ouvertes'])) {
        if (!in_array('cle', $_SESSION['inventaire']) && !in_array('cle16', $_SESSION['inventaire'])) {
            echo "<h2 style='color:red;'>Il y a une grille ! Tu as besoin d'une clé pour passer !</h2>";
        } else {
            if (in_array('cle', $_SESSION['inventaire'])) {
                $_SESSION['inventaire'] = array_diff($_SESSION['inventaire'], ['cle']);
            } else {
                $_SESSION['inventaire'] = array_diff($_SESSION['inventaire'], ['cle16']);
            }
            $_SESSION['grilles_ouvertes'][] = 20;
            echo "<h2 style='color:green;'>Tu as utilisé une clé pour ouvrir la grille !</h2>";
        }
    }

    if ($current_couloir_id == 3) {
        echo "<h2 style='color:orange;'>Tu a ramasser une clé  !</h2>";
    } else if ($current_couloir_id == 10) {
        echo "<h3 style='color:green;'>Il a un secret dans le couloir 14 (chuuuut)!</h3>";
    } else if ($current_couloir_id == 14) {
        echo "<h2 style='color:green;'>Le secret c'est que c'est pas la sorti :) !</h2>";
    }
}

echo "</body></html>";

$sqlite->close();
?>