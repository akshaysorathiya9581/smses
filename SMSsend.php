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
    <h2 class="mb-3 mt-3">Send SMS</h2><a class="mb-3 btn btn-primary" href="http://localhost/send/EditSMS.php">Edit SMS</a>
    &nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/DeleteFileUser.php">Delete Fileuser</a>
    &nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/FileUpload.php">File Upload</a>
    &nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/sender-template.php">Add Sender Template</a>
    &nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/reports.php">Reports</a>&nbsp;&nbsp;
    &nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/logs.php">Logs across Pages</a>
    &nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/City_Map.php">City Map</a>
    <div class="loading" style="display:none;">Loading&#8230;</div>
    <form method="post" action="sms.php" enctype="multipart/form-data" onsubmit="return validateData()">
        <div class="row">
            <div class="col-md-6">
                <label for="sender">Select Sender</label>
                <select class="form-control" id="sender" name="sender">
                    <option value="">Select</option>
                    <?php
                    $getData = "SELECT `sender_id` FROM `sender-template` ORDER BY id DESC";
                    $result = mysqli_query($con, $getData);
                    
                    if (mysqli_num_rows($result) > 0) {
                      while ($row = mysqli_fetch_assoc($result)) {
                        ?>
                        <option value="<?php echo $row['sender_id']; ?>"><?php echo $row['sender_id']; ?></option>
                        <?php
                      }
                    } else {
                      ?>
                      <option disabled>No senders found</option>
                      <?php
                    }
                    ?>
                </select>
                <span id="sendererr" class="error"></span>
            </div>
            
            <div class="col-md-6">
                <div class="form-check">
                  <input class="form-check-input" type="radio" value="1"  id="select_file" name="select_option" checked onchange="updateSection()">
                  <label class="form-check-label" for="select_file">
                    Select File
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" value="0" id="select_file1" name="select_option" onchange="updateSection()">
                  <label class="form-check-label" for="select_file1">
                    Custom Selection
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" value="2" id="select_file2" name="select_option" onchange="updateSection()">
                  <label class="form-check-label" for="select_file2">
                    Custom Selection -Connect
                  </label>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-6 selectFileDiv" id="filechoose">
                <label for="message">Select File</label>
                <select class="form-control" id="filename" multiple name="filename[]">
                  <option value="">Select</option>
                  <?php
                  $getData = "SELECT * FROM `filedata` order by id desc";
                  $data = mysqli_query($con,$getData);
                  if(mysqli_num_rows($data) > 0 ){
                        while($row = $data->fetch_assoc()) { ?>
                        <option value="<?php echo $row['id'];?>" record-val="<?php echo $row['record']; ?>"><?php echo $row['filename'];if($row['sent'] == 1){ echo '(Sent)';} if($row['campaign'] !== NULL){ echo '('.$row['campaign'].')';} ?></option>
                        <?php }
                  }
                  ?>
                </select>
                <span id="filenameerr" class="error"></span>
            </div>
            <!--<div class="col-md-6 customSelectionDiv" style="display:none">-->
            <!--  <label for="message">Select Location</label>-->
            <!--    <select class="form-control" id="location_new" name="location_new[]" multiple>-->
            <!--      <option value="">Select</option>-->
                  <?php
                //   $getData = "SELECT DISTINCT location FROM `fileuser` ORDER BY location";
                //   $data = mysqli_query($con, $getData);
                //   if(mysqli_num_rows($data) > 0 ){
                //       while($row = $data->fetch_assoc()) {
                //           if($row['location'] != '') {
                  ?>
                          <!--<option value="<?php echo $row['location'];?>"><?php echo $row['location']; ?></option>-->
                  <?php
                //       } }
                //   }
                  ?>
            <!--    </select>-->
            <!--</div>-->
            
            <!--<div class="col-md-1 customSelectionDiv"  style="display:none;margin-top:60px">-->
            <!--    <button type="button" id="mapCity" class="btn btn-primary btn-sm">City Map</button>-->
            <!--</div>-->
            
            <div class="col-md-6 customSelectionDiv" style="display:none">
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
       <!-- <div class="row mt-2">
            <div class="col-md-6">
                <label class="radio-inline">
                  <input type="radio" name="filetype" checked value="uploadfile"> Upload File
                </label>
                <label class="radio-inline">
                  <input type="radio" name="filetype" value="selectfile"> Select File
                </label>
            </div>
        </div>-->
        <div class="row mt-3">
            <!--<div class="col-md-6" id="fileupload">
                <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text">Upload</span>
              </div>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" name="uploadfile" id="uploadfile">
                    <label class="custom-file-label" for="inputGroupFile01">Choose file</label>
                    <input type="hidden" name="type" value="2">
                </div>
            </div>
            <span id="imgerr" class="error"></span>
            </div>-->

           


            
            
            
            <!--<div class="col-md-6 customSelectionDiv" style="display:none">-->
            <!--  <label for="message">Select Experience</label>-->
            <!--    <select class="form-control" id="experience" multiple name="experience[]">-->
            <!--      <option value="">Select</option>-->
                  <?php
                //   for ($i = 0; $i <= 20; $i++) {
                //       echo "<option value='$i'>$i</option>";
                //   }
                  ?>
            <!--    </select>-->
            <!--</div>-->
            <!--<div class="col-md-6 customSelectionDiv"  style="display:none">-->
            <!--  <label for="message">Select Salary</label>-->
            <!--    <select class="form-control" id="salary" multiple name="salary[]">-->
            <!--      <option value="" >Select</option>-->
                  <?php
                //   for ($i = 2; $i <= 20; $i++) {
                //     $salary = $i * 100000; 
                //     echo "<option value='$salary'>$i Lacs</option>";
                // }
                  ?>
            <!--    </select>-->
            <!--</div>-->
            <div class="col-md-6 customSelectionDiv mb-3"  style="display:none">
              <label for="message">Select Min Age</label>
                <select class="form-control" id="min_age"  name="min_age">
                  <option value="" selected>Select Min Age</option>
                  <?php
                  for ($i = 1; $i <= 99; $i++) {
                    echo "<option value='$i'>$i</option>";
                  }
                  ?>
                </select>
            </div>

            <div class="col-md-6 customSelectionDiv mb-3"  style="display:none">
              <label for="message">Select Max Age</label>
                <select class="form-control" id="max_age"  name="max_age">
                  <option value="" selected>Select Max Age</option>
                  <?php
                  for ($i = 1; $i <= 99; $i++) {
                    echo "<option value='$i'>$i</option>";
                  }
                  ?>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 customSelectionDiv mb-3" style="display:none">
                <label for="daterange">Select Date Range</label>
                <input type="text" id="daterange" name="daterange" class="form-control">    
            </div>
            
            <div class="col-md-6 customSelectionDiv mb-3" style="display:none">
                <label for="message">SET SMS CAP</label>
                <select class="form-control" id="sms_cap"  name="sms_cap">
                  <option value="" selected>SET SMS CAP</option>
                  <option value="1">1</option>
                  <option value="500">500</option>
                  <option value="1000">1000</option>
                  <option value="1500">1500</option>
                  <option value="2000">2000</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <label for="message">Invited to / Selected For / Shortlisted For - VAR2</label>
                <input type="text" class="form-control" id="message" placeholder="Enter message" name="message" onkeyup="countCharacters();">
            </div>
            
            <div class="col-md-6">
                <label for="hyperlink">Hyperlink - VAR3</label>
                <input type="text" class="form-control" id="hyperlink" placeholder="Enter Hyperlink" name="hyperlink" onkeyup="countCharacters();">
                <span id="hyperlinkerr" class="error"></span>
                <input type="hidden" name="type" value="4">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mt-3">
                <input class="form-check-input"  type="checkbox" id="send_again" name="send_again" value="1" style="margin-left : 5px" checked>
                <label class="form-check-label" for="" style="margin-left : 30px">
                    Do Not Send Again to Same users
                </label>
            </div>
            
            <div class="col-md-6 mt-3" style="margin-top:10px;">
                <div class="form-check">
                  <input class="form-check-input" type="radio" value="0"  id="send_now" name="sent_option" checked onchange="checkVal(this)">
                  <label class="form-check-label" for="send_now">
                    Send Now
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" value="1" id="send_later" name="sent_option" onchange="checkVal(this)">
                  <label class="form-check-label" for="send_later">
                    Send Later
                  </label>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mt-3" id="date_section" style="display:none;" style="margin-top:10px;">
                <label for="datetime">Date / Time</label>
                <input class="form-control" type="datetime-local" name="sent_time" id="sent_time"/>  
            </div>
            
            <div class="col-md-12" style="text-align:right;">
                <h5 id="totalval"></h5>
                <h5 id="totalchars"></h5>
                
            </div>
            <input type="hidden" name="filetype" value="selectfile">
        </div>
        <div class="row col-md-12 justify-content-center mt-3">
            <div class="col-md-3" style="display:none;"  id="count_button" >
                <button type="button" class="btn btn-info" onclick="getCount()">
                Get Target Users Count
                </button>
                <br/>
                <span id="total_user_count"></span> 
            </div>
            
            <div class="col-md-3">
                <button type="submit" class="btn btn-info">
                Send
                </button>
            </div>
        </div>
    </form>
    </div>
</div>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<style>
    .container{
        margin-top:50px;
    }
</style>

<script type="text/javascript">
    $(function() {
        $('#daterange').daterangepicker({
            opens: 'right',
            autoUpdateInput: false, // Prevents auto-filling the input field
            ranges: {
               'Today': [moment(), moment()],
               'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
               'Last 7 Days': [moment().subtract(6, 'days'), moment()],
               'Last 30 Days': [moment().subtract(29, 'days'), moment()],
               'This Month': [moment().startOf('month'), moment().endOf('month')],
               'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            locale: {
                format: 'YYYY-MM-DD'
            }
        });

        $('#daterange').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' to ' + picker.endDate.format('YYYY-MM-DD'));
        });

        $('#daterange').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });
    });
</script>

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
    function checkVal(th){
        if($(th).val() == 1){
            $('#date_section').css('display','block');
            if($("#sent_time").length != 0){
              $('#sent_time').attr("required", "true");
            }
            
            
        }else{
            $('#date_section').css('display','none');
            if($("#sent_time").length != 0){
              $('#sent_time').attr("required", "false");
            }
            
        }
    }
    
    
    function validateData(){
        var sender = $('#sender').val();
        var link = $('#hyperlink').val();
        var filetype =  $("input[name='filetype']:checked").val();
        var filename = $('#filename').val();
        var select_file = $("input[name='select_option']:checked").val();
        
        var flag = [];
        if(sender == ''){
            flag.push(0);
            $('#sendererr').html("This Field is required!!");
        }else{
            flag.push(1);
            $('#sendererr').html("");
        }
        
        if(link == ''){
            flag.push(0);
            $('#hyperlinkerr').html("This Field is required!!");
        }else{
            regexp =  /^(?:(?:https?|ftp):\/\/)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,})))(?::\d{2,5})?(?:\/\S*)?$/;
            if (!regexp.test(link)){
                flag.push(0);
                $('#hyperlinkerr').html("Enter Valid Url!!");
            }else{
                flag.push(1);
                $('#hyperlinkerr').html("");
            }
        }
        
        /*if(filetype == 'uploadfile'){
            if(file == 0){
                flag.push(0);
                $('#imgerr').html("This Field is required!!");
            }else{
                flag.push(1);
                $('#imgerr').html("");
            }
        }else{*/
        if(select_file == 1){
            if(filename == ''){
                flag.push(0);
                $('#filenameerr').html("This Field is required!!");
            }else{
                flag.push(1);
                $('#filenameerr').html("");
            }
        }
            
        //}
        if(!flag.includes(0)){
            return true;
            $('.loading').css('display','block');
        }else{
            return false;
        }
        
    }
    
    $('#filename').change(function(){
        var value = $('#filename option:selected').attr('record-val');
        $('#totalval').html('');
        $('#totalval').html('Total Record : '+value);
        
    });
    /*$('#uploadfile').change(function(){
        $('#filetype').val('uploadfile');
    })
    */
    /*$('input[name="filetype"]').change(function(){
        var value = $(this).val();
        if(value == 'uploadfile'){
            $('#fileupload').css('display','block');
            $('#filechoose').css('display','none');
        }else{
            $('#filechoose').css('display','block');
            $('#fileupload').css('display','none');
        }
    })*/
    
    
function countCharacters() {
  const hyperlink = $("#hyperlink").val();
  const message = $("#message").val();
  let totalCount = 0;

  if (hyperlink || message) {
    const hyperlinkCount = hyperlink ? hyperlink.length + 25 : 0;
    const messageCount = message ? message.length + 25 : 0;
    totalCount = hyperlinkCount + messageCount;
  }
  
  $("#totalchars").html("Total character count: " + totalCount);
}

function updateSection(){
  var selectFileRadio = document.getElementById("select_file");
  $('#count_button').css('display','none');
  if (selectFileRadio.checked) {
      $('.selectFileDiv').css('display','block');
      $('.customSelectionDiv').css('display','none');
  } else {
      $('.selectFileDiv').css('display','none');
      $('.customSelectionDiv').css('display','block');
      $('#count_button').css('display','block');
  }
} 

function getCount(){
    var location = $('#location').val();
    // var exp = $('#experience').val();
    // var salary = $('#salary').val();
    var fromage = $('#min_age').val();
    var toage = $('#max_age').val();
    var daterange = $('#daterange').val();
    var send_again = $("#send_again").is(':checked') ? 1 : 0;
    var link = $('#hyperlink').val();
    var custome_connect = $("input[name='select_option']:checked").val();
    var startDate = '';
    var endDate = '';
    if(daterange != '') {
        var dates = daterange.split(' - ');
        startDate = dates[0];
        endDate = dates[1];
    }

    
    var flag = 1;
    if(location == '' && fromage == '' && toage == '' && daterange == ''){
        flag = 0 ;
    }
    // alert(flag)
    if(flag == 1){
        //$('.loading').css('display','block');
        $.ajax({
            url:"http://localhost/send/sms.php",
            type: "post", 
            dataType: 'json',
            data: {type: "getcount",'location':location,'fromage':fromage,'toage':toage,'startDate': startDate,'endDate': endDate,'send_again':send_again,'link':link,'custome_connect':custome_connect},
            success:function(result){
                $('.loading').css('display','none');
                $('#total_user_count').html('User Count : '+result);
            }
        });
    }else{
        $('#total_user_count').html('User Count : 0');
    }
    
}
</script>

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
                url: 'update_city.php', 
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