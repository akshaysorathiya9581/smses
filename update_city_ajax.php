<?php
$con = mysqli_connect("localhost", "root", "", "smses_send");
if(isset($_POST['location_new']) && isset($_POST['location'])) {
    $locationNew = $_POST['location_new']; 
    $city_id = $_POST['location']; 
    $locationNewStr = implode("','", $locationNew); 
    $query = "UPDATE fileuser SET city_id=$city_id WHERE location IN ('$locationNewStr')";
    if(mysqli_query($con, $query)) {
        echo "Success";
    } else {
        echo "Error: " . mysqli_error($con);
    }
} else {
    echo "Invalid Input";
}

?>