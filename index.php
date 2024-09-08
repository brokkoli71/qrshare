<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <script src="http://crypto-js.googlecode.com/svn/tags/3.1.2/build/rollups/aes.js"></script>

    <?php
        include 'config.php';
        require 'db.php';

        if(isset($_POST['pwd'])) $pwd=$_POST['pwd'];
        if(isset($_GET['pwd'])) $pwd=$_GET['pwd'];
        if ($pwd) $locked="🔒";

        if(isset($_POST['session'])) $session=$_POST['session'];
        if(isset($_GET['session'])) $session=$_GET['session'];
        if ($session=="") $session=random_int(0, pow(2,16));

        $cipher = "aes-128-gcm";
    ?>
    <script>
        document.addEventListener("DOMContentLoaded", function(){
            url = window.location.href + "?session=" + "<?php echo $session; ?>";
            document.getElementById("qrcode").setAttribute("src","https://api.mimfa.net/qrcode?value="+encodeURIComponent(url)+"&as=value");
        });
        
        
    </script>
</head>
<body>
    <div class="header">
        <h1>Quick Share Text<?php echo " $locked";?></h1>
        <div class="row">
            <p>Paste some text and press "Send" to share it with your devices. Press "Receive" to reload all shared texts in this room</p>
            <form action="index.php" method="post">
                <input type="text" id="shared" name="shared" required>
                <input type="submit" id="shared" value="Send<?php echo " $locked";?>" class=".button-13">
                <input type="hidden" id="session" name="session" value='<?php echo "$session";?>'>
                <input type="hidden" id="pwd" name="pwd" value='<?php echo "$pwd";?>'>
            </form>
            <form action="index.php" method="post">
                <input type="submit" id="shared" value="Receive" class=".button-13">
                <input type="hidden" id="session" name="session" value='<?php echo "$session";?>'>
                <input type="hidden" id="pwd" name="pwd" value='<?php echo "$pwd";?>'>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-5">

            <?php
                /* check connection */
                if (mysqli_connect_errno()) {
                    printf("Connect failed: %s<br>", mysqli_connect_error());
                    exit();
                }
    
                if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['shared']) {
                    // Collect form data
                    $content = $_POST['shared'];
    
                    //encrypt
                    if (in_array($cipher, openssl_get_cipher_methods())){
                        $ivlen = openssl_cipher_iv_length($cipher);
                        $iv = openssl_random_pseudo_bytes($ivlen);
                        $ciphertext = openssl_encrypt($content, $cipher, $pwd, $options=0, $iv, $tag);
                        // speichere $cipher, $iv und $tag für spätere Entschlüsselung
                        $original_plaintext = openssl_decrypt($ciphertext, $cipher, $pwd, $options=0, $iv, $tag);
                        echo $original_plaintext."\n";
                        echo $ciphertext;
                        echo $ciphertext, $cipher, $key, $options=0, $iv, $tag;

                        // Prepare the SQL query
                        $stmt2 = $db->prepare("INSERT INTO QRshared(content, session, enc_iv, enc_tag) VALUES (?,?,?,?)");
                        
                        // Bind the parameter
                        $stmt2->bind_param('siss', $ciphertext, $session, base64_encode($iv), base64_encode($tag));
                        // Execute the query
                        if (!$stmt2->execute()) {
                            echo "Error sending<br>";
                        }
                    }else{
                        echo $cipher + "is no valid encryption algo";
                    }
                }
            ?>
            <div class="menu">
                
                <?php
                    //collect all contents from session
                    $stmt = $db->prepare("SELECT content, enc_iv, enc_tag FROM QRshared WHERE session = ?");
                    $stmt->bind_param('i', $session);
                    if (!$stmt->execute()) {
                        echo "error reading shared data<br>";
                    }
                    $stmt->store_result();
    
                    $stmt->bind_result($read_content, $iv_b64, $tag_b64);
                    echo "<ul>";
                    if($stmt->num_rows > 0) {
                        while ($stmt->fetch()) {
                            $original_plaintext = openssl_decrypt($read_content, $cipher, $pwd, $options=0, base64_decode($iv_b64), base64_decode($tag_b64));
                            if ($original_plaintext) printf("<li>%s</li>", $original_plaintext); 
                            else printf("<li>could not decrypt using current password %s</li>", $read_content); 
                        }
                    }
                    echo "</ul>";
                ?>
    
            </div>
        
            <p>Check if you have the same room id on your devices: <b> <?php echo $session; ?></b></p>
            <div style="overflow: hidden; width: min(400px, 100%)">
                <div id='slider' class='collapsed'>
                    <input type='button' id='toggle' value='Change room' class=".button-13">
                    <form action="index.php" method="post">
                        <input class="input" style="width:70%" type='number' id='session'  name="session" tabindex='-1' placeholder='session' size=10 value='<?php echo "$session";?>'>
                        <input type='submit' style="width:25%" id="shared" value="change" tabindex='-1' value='ok'>
                        <input type="hidden" id="pwd" name="pwd" value='<?php echo "$pwd";?>'>
                    </form>
                </div>
            </div>
            <div style="overflow: hidden; width: min(400px, 100%)">
                <form action="index.php" method="post">
                    <input class="password" style="width:70%" type='text' id='pwd'  name="pwd" tabindex='-1' placeholder='optional encryption key' size=10 value='<?php echo "$pwd";?>'>
                    <input type='submit' style="width:25%" id="shared" value="change" tabindex='-1' value='ok'>
                    <input type="hidden" id="session" name="session" value='<?php echo "$session";?>'>
                </form>
            </div>
        </div>
        <div class="col-3 right">
            <div class="aside">
                <p>scan this QR code to join this room</p>
                <iframe id="qrcode" src="" width="200" height="200" frameBorder="0"></iframe>
            </div>
        </div>
    </div>

        
    <div class="footer">
        <p>The data will be encrypted using your password and stored in the database. Choose a strong password to secure your data.</p>
    </div>
    <script>
        //change sessionID button
        function expand() {
          slider.className = 'expanded';
          setTimeout(function() {
            input.focus();
          }, 200);
        }
        
        function collapse() {
          slider.className = 'collapsed';
          input.blur();
        }
        
        toggle.onclick = expand;
        input.onblur = function() {
          setTimeout(collapse, 100);
        }
            </script>

</body>
