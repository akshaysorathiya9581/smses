<?php 
$con = mysqli_connect("localhost", "root", "", "smses_send");
$sel_sql = "select id,location from fileuser";
$result = $con->query($sel_sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $location = $row['location'];
        $id = $row['id'];
        $sql = "SELECT city_id FROM cities WHERE city_name='$location' limit 1";
        $result_city = mysqli_query($con, $sql);
        $row1 = mysqli_fetch_assoc($result_city);
        if ($row1 !== null) {
            $city_id = $row1['city_id'];
            $update_sql= 'UPDATE fileuser set city_id =  "'.$city_id.'" WHERE id='.$id;
            mysqli_query($con, $update_sql);
        } 
    }
    // $_SESSION[''] = '';
    echo "Update Done";
}else{
    echo "No Data Found!";
}

?>