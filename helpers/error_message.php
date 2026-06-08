<?php
if (isset($_SESSION["errtext"])) {
    $class = empty($_SESSION["errtext"]) ? 'alert alert-success' : 'alert alert-danger';
    $text = empty($_SESSION["errtext"]) ? 'Operation completed successfully.' : htmlspecialchars($_SESSION["errtext"]);
    echo '<div class="' . $class . '">' . $text . '</div>';
    unset($_SESSION["errtext"]);
}