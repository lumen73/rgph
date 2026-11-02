<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechercher une ville</title>
    <link rel="stylesheet" href="css/bootstrap.css">
    <?php include ("menu.php"); ?>
</head>
<body>
    <form action=""method="POST" class="container">
     <fieldset>
        <legend><strong>Rechercher une ville</strong></legend>
        <label for="ville">Ville</label>
        <input type="text" name="ville" id="ville" class="form-control" onkeyup="suggest(this.value)"><br>
     </fieldset>
    </form><br><hr><br>
    <h4><strong>Resultat de la recherche</strong></h4>
    <div id="result">
     
    </div>

    <script>
        function suggest(saisie){
         const eze= new XMLHttpRequest(); //1
         eze.onload= function(){
         document.getElementById('result').innerHTML=this.responseText; //2
        }
        eze.open("GET", "getville.php?q="+saisie);
        eze.send();
        }
    </script>
</body>
</html>