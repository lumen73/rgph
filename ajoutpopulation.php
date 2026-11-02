<?php
  require "connexion.php"; 
  
  if(isset($_POST['ajouter'])){
    $dep=$_POST['departement']; $com=$_POST['commune']; $ville=$_POST['ville']; $pop=$_POST['population'];
    $insertion=$db->query("INSERT into ville (idcommune,nom,population) VALUES ('$com','$ville','$pop')");
     if($insertion){$sms='<div class="alert alert-success">INSERTION REUISSI</div>';}
  }
  ?>

<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AJOUT POPULATION</title>
    <link rel="stylesheet" href="css/bootstrap.css">
    <?php include ("menu.php"); ?>
</head>
<body>
    <div class="container">
        <form action="" method="POST">
            <?php if(isset($sms)){echo $sms;} ?>
            <fieldset>
                <legend><h2>Ajouter une population</h2></legend>
                <label for="departement">Departement</label>
                <select name="departement" id="departement" class="form-control" onchange="suggestion(this.value)">
                    <option value="">-- Choisir un département</option>
                    <?php
$departement=$db->query("SELECT * FROM departement");
while($d=$departement->fetch()){
                ?>
                    <option value="<?= $d['iddepartement'] ?>"><?= $d['nom'] ?></option>
                    <?php } ?>  
                </select><br>
                 <label for="commune">Commune</label>
                 <select name="commune" id="commune" class="form-control">
                
                 </select><br>
                 <label for="ville">Ville</label>
                 <input type="text" name="ville" placeholder="Entrer le nom de la ville" id="ville" class="form-control"><br>
                 <label for="population">Population</label>
                 <input type="population" name="population" id="population" class="form-control"><br>
                 <div class="row">
                    <div class="col-md-11">
                    <button type="submit" name="ajouter" class="btn btn-success">Ajouter</button>
                    </div>
                    <div class="col-md-1">
                    <button type="reset" class="btn btn-danger">Annuler </button>
                    </div>
                 </div>
            </fieldset>
        </form>
    </div>

    <script>
        function suggestion(saisie){
         const eze= new XMLHttpRequest(); //1
         eze.onload= function(){
         document.getElementById('commune').innerHTML=this.responseText; //2
        }
        eze.open("GET", "getcommune.php?q="+saisie);
        eze.send();
        }
    </script>
</body>
</html>