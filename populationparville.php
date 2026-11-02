<?php require "connexion.php"; $popu=NULL; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche par ville</title>
    <link rel="stylesheet" href="css/bootstrap.css">
    <?php include ("menu.php"); ?>
</head>
<body>
    <div class="container">
        <h1>Recherche par commune</h1>
        <form action=""method="POST">
            <fieldset>
                <label for="commune">Commune</label>
                <input type="text" name="commune" id="commune" class="form-control"><br>
                <button type="submit" name="rechercher" class="btn btn-success">RECHERCHER</button>
            </fieldset>
        </form>
        <br>
        <hr>
        <br>
        <?php 
        if(isset($_POST['rechercher']))
        {
         $commune=$_POST['commune'];
         ?>
        <h3><strong>Commune: <?= $commune ?></strong></h3>
        <table class="table">
            <thead>
                <th>Ville</th> <th>Population</th>
            </thead>
        <?php
 $search=$db-> query("SELECT commune.idcommune AS idcom,ville.nom AS villenom,ville.population AS pop FROM commune
  INNER JOIN ville ON commune.idcommune=ville.idcommune  WHERE commune.nom='$commune' ");
 while($result=$search->fetch()){
?>
<tbody>
    <td><?= $result['villenom'] ?></td>
    <td><?= $result['pop'] ?></td> 
    <?php $popu=$popu+$result['pop'] ;?>
    <?php } ?>
    <tbody>
    <td>Total</td>
        <td>
        <?php if($popu){ ?>
        <strong><?= $popu ?></strong></hp>
        <?php } ?>
        </td>
    </tbody>
</tbody>

        <?php } ?>
        </table>
    </div>
</body>
</html>