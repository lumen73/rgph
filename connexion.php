<?php
    session_start();

    try {
        $db= new pdo ('mysql:host=localhost;dbname=rgph1','root','');
    }catch(PDOException $e){
        die('Erreur de connexion à la base de données :' .$e->getMessage());
    }

?>
