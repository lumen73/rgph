<?php require "connexion.php"; $tot=NULL;
$q=$db->query("SELECT commune.iddepartement,SUM(ville.population) AS popTot,departement.nom AS nomdepartement from commune INNER JOIN  ville ON commune.idcommune=ville.idcommune INNER JOIN departement ON commune.iddepartement=departement.iddepartement GROUP BY commune.iddepartement ;");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Population general</title>
    <link rel="stylesheet" href="css/bootstrap.css">
    <?php include ("menu.php"); ?>
</head>
<body>
    <div class="container">
    <table class="table">
        <thead>
            <th>Département</th>
            <th>Population</th>
        </thead>
        <?php while ($d=$q->fetch()){ ?>
        <tbody>
            <td><?= $d['nomdepartement'] ?></td>
            <td><?= $d['popTot']; $tot=$tot+$d['popTot']; ?></td>
            <?php } ?>
        </tbody>
    </table>
        </div>
        <?php if(isset($tot)){ ?><h4 style="text-align: center; padding:5px; border:1px dashed black">POPULATION TOTALE : <strong><?= $tot ?></strong></h4><?php } ?>
</body>
</html>