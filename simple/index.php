<!DOCTYPE html>
<html lang="en">
<meta charset="UTF-8">
<title>QR Share</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<link rel="stylesheet" href="../styles.css">
<style>
    .header{
        background-color: #56820377;
    }
    .footer{
        background-color: #17820377;
    }
</style>
<?php
    if(isset($_GET['share'])) printf("<div class='header'> <h2>Dein geteilter content ist:</h2><h1> %s <br></h1></div>",$_GET['share']);
?>
<script>
    function UpdateQRCode(val){
        url = location.protocol + '//' + location.host + location.pathname;
        if(val){ 
            url+= "?share=" + val;
            
        }
        document.getElementById("qrcode").setAttribute("src","https://api.mimfa.net/qrcode?value="+encodeURIComponent(url)+"&as=value");
    }
    document.addEventListener("DOMContentLoaded", function(){
        UpdateQRCode(document.getElementById("qrcode").value);
    });
</script>

<body>
   <p>Gib text ein, dann wird ein QR code mit diesem Text generiert und du kannst den auf deinem Smartphone kopieren (klick irgendwo auserhalb des textfeldes um den qr code zu aktualisieren)</p>
   <input class="input" onblur="UpdateQRCode(this.value)" value=""/><br>
   <br>
   <iframe id="qrcode" src="" width="250" height="250"></iframe>
   <br>
   <br>
    <div class="footer">
        <p>die datenübertragung ist serverlos nur über den qr code</p>
    </div>

</body>
</html>