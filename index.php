<?php  require "connexion.php";

if(isset($_POST['seconnecter'])){
 $email=$_POST['email']; $mdp=addslashes($_POST['mdp']);
 $con=$db->query("SELECT * FROM utilisateur WHERE email='$email' AND mdp='$mdp' ");
  if($sucess=$con->fetch() )
  { 
   $_SESSION['email']=$email; $_SESSION['mdp']=$mdp;
    header('Location:ajoutpopulation.php');
  }else{$sms='<div class="alert alert-danger">IDENTIFIANTS INCORRECTS</div>';}
}

?> 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil</title>
    <link rel="stylesheet" href="css/bootstrap.css">
</head>
<body>
    <div class="container" style="display: grid; place-items:center; border:2px solid white;box-shadow:2px 2x 5px 5px gray; padding:15px">
    <form action="" method="POST">
        <?php if(isset($sms)){echo $sms;} ?>
        <fieldset>
            <center><legend><h1>RGPH5</h1><h4>5e Recensement général de la population et de l'habitat</h4></legend></center>
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control" placeholder="Saisissez l'email"><br>
            <label for="mdp">Mot de passe</label>
            <input type="password" name="mdp" class="form-control" id="mdp" placeholder="Saisissez le mot de passe"><br>
            <button type="submit" name="seconnecter" class="btn btn-success">Se connecter</button>
        </fieldset>
    </form>
    </div>
</body>
</html>