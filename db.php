<?php
$conn = mysqli_connect("localhost", "root", "", "bvassist");

if(!$conn){
    http_response_code(500);
    echo "Something went wrong. Please try again later.";
    exit();
}
?>
