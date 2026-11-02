<?php  require "connexion.php"; 

  if(isset($_GET['q']))
  { 
    $nomville=$_GET['q'];
    $vl=$db->query("SELECT commune.nom AS nomcomm, departement.nom AS nomdepartement, population FROM ville INNER JOIN commune ON ville.idcommune=commune.idcommune INNER JOIN departement ON commune.iddepartement=departement.iddepartement WHERE ville.nom='$nomville'");
    while($v=$vl->fetch()){
?>

<table class="table">
<tr>
    <td>DEPARTEMENT</td> <td><?= $v['nomdepartement'] ?></td>
</tr>
<tr>
<td>Commune</td> <td><?=$v['nomcomm']?></td>
</tr>
<tr>
    <td>Population</td> <td><?= $v['population'] ?></td>
</tr>

</table>
 

<?php
  }}
  ?>