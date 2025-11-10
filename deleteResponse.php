<?php 

$con = mysqli_connect("localhost", "root", "", "smses_send");

$delquery = "DELETE from msm_response";
mysqli_query($con,$delquery);

?>