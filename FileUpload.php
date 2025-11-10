<?php 

// Start the session
session_start();
require('sessioncheck.php');

ini_set('max_execution_time', 0);
$con = mysqli_connect("localhost", "root", "", "smses_send");
?>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
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
    <h2 class="mb-3 mt-3">File Upload</h2><a class="mb-3 btn btn-primary" href="http://localhost/send/EditSMS.php">Edit SMS</a>
    &nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/DeleteFileUser.php">Delete Fileuser</a>
    &nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/SMSsend.php">Send SMS</a>
    &nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/reports.php">Reports</a>
    &nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="https://www.smses.in/ct/login.php">Click Track</a>
    &nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/logs.php">Logs across Pages</a>
    &nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/City_Map.php">City Map</a>
    
    
    <div class="loading" style="display:none;">Loading&#8230;</div>
    <form method="post" action="sms.php" enctype="multipart/form-data" onsubmit="return validateData()">
        
        <div class="row mt-3">
            <div class="col-md-6" id="fileupload">
                <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text">Upload</span>
              </div>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" name="uploadfile" id="uploadfile">
                    <label class="custom-file-label" for="inputGroupFile01">Choose file</label>
                    <input type="hidden" name="type" value="3">
                </div>
            </div>
            <span id="imgerr" class="error"></span>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-6">
                <label for="number">Split Count</label>
                <input type="text" onkeypress="return ((event.charCode > 47 && event.charCode < 58));" class="form-control" id="number" placeholder="Enter number" name="number" value="500">  
                <span id="numerr" class="error"></span>
            </div>
        </div>
        <input type="hidden" name="filetype" value="uploadfile">
        <div class="row mt-3">
            <div class="col-md-6">
                <button type="submit" class="btn btn-info">
                Upload
                </button>
            </div>
        </div>
    </form>
    </div>
</div>
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
    
    function validateData(){
        var flag = [];
        var file = $('#uploadfile').get(0).files.length;
        if(file == 0){
            flag.push(0);
            $('#imgerr').html("This Field is required!!");
        }else{
            flag.push(1);
            $('#imgerr').html("");
        }
        
        var number = $('#number').val();
        if(number == ''){
            flag.push(0);
            $('#numerr').html("This Field is required!!");
        }else{
            flag.push(1);
            $('#numerr').html("");
        }
        
        if(!flag.includes(0)){
            $('.loading').css('display','block');
            return true;
        }else{
            return false;
        }
        
    }


            /*$('#uploadfile').on('change',function(){
                //get the file name
                var fileName = $(this).val();
                //replace the "Choose a file" label
                $(this).next('.custom-file-label').html(fileName);
            })*/

    $('input[type="file"]').change(function(e){
        var fileName = e.target.files[0].name;
        $('.custom-file-label').html(fileName);
    });
        
    
   
    
</script>
