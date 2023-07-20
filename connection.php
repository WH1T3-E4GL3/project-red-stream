<?php
$con=mysqli_connect("localhost","root","","redstream_db");
if(mysqli_connect_error()){
    echo"<script>Cannot connect to database</script>";
    exit();
}
?>