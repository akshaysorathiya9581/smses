<?php

// Start the session
session_start();
require('sessioncheck.php');

ini_set('max_execution_time', 0);
$con = mysqli_connect("localhost", "root", "", "smses_send");
?>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>

<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>

<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

<div class="container">
    <?php if(isset($_SESSION['sucess'])) {  ?>
    <div class="alert alert-success" role="alert">
      <?php
            echo $_SESSION['sucess'];
            unset($_SESSION['sucess']);
      ?>
    </div>
    <?php } ?>
    <?php if(isset($_SESSION['error'])) {  ?>
    <div class="alert alert-danger" role="alert">
        <?php
            echo $_SESSION['error'];
            unset($_SESSION['error']);
        ?>
    </div>
    <?php } ?>
    <h2 class="mb-3 mt-3">City Map</h2>
    <div class="loading" style="display:none;">Loading&#8230;</div>
        <div class="row mt-3">
            <div class="col-md-6 customSelectionDiv">
              <label for="message">Select Location</label>
                <select class="form-control" id="location_new" name="location_new[]" multiple>
                  <option value="">Select</option>
                  <?php
                  $getData = "SELECT DISTINCT location FROM `fileuser` WHERE city_id = 0 ORDER BY location";
                  $data = mysqli_query($con, $getData);
                  if(mysqli_num_rows($data) > 0 ){
                      while($row = $data->fetch_assoc()) {
                          if($row['location'] != '') {
                  ?>
                          <option value="<?php echo $row['location'];?>"><?php echo $row['location']; ?></option>
                  <?php
                      } }
                  }
                  ?>
                </select>
            </div>
            
            <div class="col-md-6 customSelectionDiv" >
              <label for="message">Select City</label>
                <select class="form-control" id="location" name="location">
                  <option value="">Select</option>
                  <?php
                $getData = "SELECT * FROM `cities` ORDER BY city_name";
                  $data = mysqli_query($con, $getData);
                  if(mysqli_num_rows($data) > 0 ){
                      while($row = $data->fetch_assoc()) {
                          if($row['city_id'] != '') {
                  ?>
                    <option value="<?php echo $row['city_id'];?>"><?php echo $row['city_name']; ?></option>
                  <?php
                      } }
                  }
                  ?>
                </select>
            </div>
        </div>
        <div class="row col-md-12 justify-content-center mt-3">
            <button type="button" id="mapCity" class="btn btn-info">City Map</button>
        </div>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<style>
    .container{
        margin-top:50px;
    }
</style>
<script>
    setTimeout(function() { $('.alert').hide() }, 5000)
</script>
<style>
.loading {
  position: fixed;
  z-index: 999;
  height: 2em;
  width: 2em;
  overflow: show;
  margin: auto;
  top: 0;
  left: 0;
  bottom: 0;
  right: 0;
}

/* Transparent Overlay */
.loading:before {
  content: '';
  display: block;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
    background: radial-gradient(rgba(20, 20, 20,.8), rgba(0, 0, 0, .8));

  background: -webkit-radial-gradient(rgba(20, 20, 20,.8), rgba(0, 0, 0,.8));
}

/* :not(:required) hides these rules from IE9 and below */
.loading:not(:required) {
  /* hide "loading..." text */
  font: 0/0 a;
  color: transparent;
  text-shadow: none;
  background-color: transparent;
  border: 0;
}

.loading:not(:required):after {
  content: '';
  display: block;
  font-size: 10px;
  width: 1em;
  height: 1em;
  margin-top: -0.5em;
  -webkit-animation: spinner 150ms infinite linear;
  -moz-animation: spinner 150ms infinite linear;
  -ms-animation: spinner 150ms infinite linear;
  -o-animation: spinner 150ms infinite linear;
  animation: spinner 150ms infinite linear;
  border-radius: 0.5em;
  -webkit-box-shadow: rgba(255,255,255, 0.75) 1.5em 0 0 0, rgba(255,255,255, 0.75) 1.1em 1.1em 0 0, rgba(255,255,255, 0.75) 0 1.5em 0 0, rgba(255,255,255, 0.75) -1.1em 1.1em 0 0, rgba(255,255,255, 0.75) -1.5em 0 0 0, rgba(255,255,255, 0.75) -1.1em -1.1em 0 0, rgba(255,255,255, 0.75) 0 -1.5em 0 0, rgba(255,255,255, 0.75) 1.1em -1.1em 0 0;
box-shadow: rgba(255,255,255, 0.75) 1.5em 0 0 0, rgba(255,255,255, 0.75) 1.1em 1.1em 0 0, rgba(255,255,255, 0.75) 0 1.5em 0 0, rgba(255,255,255, 0.75) -1.1em 1.1em 0 0, rgba(255,255,255, 0.75) -1.5em 0 0 0, rgba(255,255,255, 0.75) -1.1em -1.1em 0 0, rgba(255,255,255, 0.75) 0 -1.5em 0 0, rgba(255,255,255, 0.75) 1.1em -1.1em 0 0;
}

/* Animation */

@-webkit-keyframes spinner {
  0% {
    -webkit-transform: rotate(0deg);
    -moz-transform: rotate(0deg);
    -ms-transform: rotate(0deg);
    -o-transform: rotate(0deg);
    transform: rotate(0deg);
  }
  100% {
    -webkit-transform: rotate(360deg);
    -moz-transform: rotate(360deg);
    -ms-transform: rotate(360deg);
    -o-transform: rotate(360deg);
    transform: rotate(360deg);
  }
}
@-moz-keyframes spinner {
  0% {
    -webkit-transform: rotate(0deg);
    -moz-transform: rotate(0deg);
    -ms-transform: rotate(0deg);
    -o-transform: rotate(0deg);
    transform: rotate(0deg);
  }
  100% {
    -webkit-transform: rotate(360deg);
    -moz-transform: rotate(360deg);
    -ms-transform: rotate(360deg);
    -o-transform: rotate(360deg);
    transform: rotate(360deg);
  }
}
@-o-keyframes spinner {
  0% {
    -webkit-transform: rotate(0deg);
    -moz-transform: rotate(0deg);
    -ms-transform: rotate(0deg);
    -o-transform: rotate(0deg);
    transform: rotate(0deg);
  }
  100% {
    -webkit-transform: rotate(360deg);
    -moz-transform: rotate(360deg);
    -ms-transform: rotate(360deg);
    -o-transform: rotate(360deg);
    transform: rotate(360deg);
  }
}
@keyframes spinner {
  0% {
    -webkit-transform: rotate(0deg);
    -moz-transform: rotate(0deg);
    -ms-transform: rotate(0deg);
    -o-transform: rotate(0deg);
    transform: rotate(0deg);
  }
  100% {
    -webkit-transform: rotate(360deg);
    -moz-transform: rotate(360deg);
    -ms-transform: rotate(360deg);
    -o-transform: rotate(360deg);
    transform: rotate(360deg);
  }
}
.error{
    color:red;
}
</style>
<script>
    $(document).ready(function() {
        $('#mapCity').click(function() {
            var selectedLocation = $('#location_new').val(); 
            var selectedCity = $('#location').val(); 
            if(selectedLocation.length == 0 || selectedCity == "") {
                alert("Please select both a location and a city.");
                return;
            }

            $.ajax({
                url: 'update_city_ajax.php', 
                type: 'POST',
                data: {
                    location_new: selectedLocation,
                    location: selectedCity
                },
                beforeSend: function(){
                    $(".loading").show();
                },
                success: function(response) {
                     $(".loading").hide();
                    alert('Data updated successfully!');
                    console.log(response); 
                },
                error: function(xhr, status, error) {
                    alert('Error updating data.');
                    console.log(error);
                }
            });
        });
    });
</script>