<?php  require "connexion.php"; 

  if(isset($_GET['q']))
  {

                $iddepartement=$_GET['q'];
                $commune=$db->query("SELECT * FROM commune where iddepartement='$iddepartement'");
                while($c=$commune->fetch()){
                ?>
                <option value=""></option>
                <option value="<?= $c['idcommune'] ?>"><?= $c['nom']; ?></option>

<?php
                }
}
?>