<?php 
if (isset($_SESSION["errtext"]) && empty($_SESSION["errtext"])){
    ?>
    <p style="color:green"> Success! </p>      
    <?php
}else if (isset($_SESSION["errtext"])){        
    ?>    
    <p style="color:red"> Error: <?php echo $_SESSION["errtext"]?></p>  
    <?php    
}; 
unset($_SESSION["errtext"])
?>