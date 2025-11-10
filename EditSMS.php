<?php 

// Start the session
session_start();
require('sessioncheck.php');

ini_set('max_execution_time', 0);
$con = mysqli_connect("localhost", "root", "", "smses_send");
$getsql = "SELECT * FROM `send_data` order by id DESC";
$smsdata = mysqli_query($con,$getsql);
// echo "<PRE>";print_r($smsdata);exit;
?>
 
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.2/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.11.2/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.2/js/dataTables.bootstrap4.min.js"></script>
<div class="container">
    
    <div class="alert alert-success" role="alert" id="success" style="display:none;">
      
    </div>
    
    <?php if(isset($_SESSION['error'])) {  ?>
    <div class="alert alert-danger" role="alert">
        <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
        ?>
    </div>
    <?php } ?>
       <h2 class="mb-3 mt-3">Edit SMS</h2><a class="mb-3 btn btn-primary" href="http://localhost/send/EditSMS.php">Edit SMS</a>&nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/DeleteFileUser.php">Delete Fileuser</a>&nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/">File Upload</a>&nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/sender-template.php">Add Sender Template</a>&nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/reports.php">Reports</a>&nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="https://www.smses.in/ct/login.php">Click Track</a>
&nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/logs.php">Logs across Pages</a>
&nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/import_urgent_vacancy.php">IMPORT UrgentVacancy Data</a>
&nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/import_connect_users.php">Import from Connect DB</a>
    <div class="loading" style="display:none;">Loading&#8230;</div>
    <?php if(mysqli_num_rows($smsdata) > 0 ) { ?>
    <table class="table" id="example">
      <thead>
        <tr>
          <th scope="col">#</th>
          <th scope="col">Sender</th>
          <th scope="col">Hyperlink</th>
          <th scope="col">File</th>
          <th scope="col">Total Data</th>
          <th scope="col">Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php $i =1; while($row = $smsdata->fetch_assoc()) {
            
            //echo "<PRE>";print_r($row);exit;
        ?>
        <tr id="del<?php echo $row['id'];?>">
          <th scope="row"><?php echo $i;?></th>
          <td><?php echo $row['sender'];?></td>
          <td id="hyper<?php echo $row['id']?>"><?php echo $row['hyperlink'];?></td>
          <td><?php 
          
          if($row['fileID'] > 0 ){
              
              $filename = "SELECT * FROM `filedata` where id=".$row['fileID'];
          
              $records = mysqli_query($con,$filename);
              while($data = mysqli_fetch_array($records))
                {
                    $record = $data['record'];
                    echo $data['filename'];
                }
              
          }else{
              
                $location = $row['city_id'];
                $experience = $row['experience'];
                $salary = $row['salary'];
                $age = $row['age'];
                
                $where = '';
                if($location != ''){
                    $explode_location = explode(',',$location);
                    $locstr = '';
                    foreach($explode_location as $loc){
                        if($loc != ''){
                            $locstr .= "'$loc',";
                        }
                        
                    }
                    $where .= ' fileuser.city_id IN ( '.trim($locstr,',').' ) AND ';
                }
                if($experience != ''){
                    
                    $where .= 'fileuser.experience >= ( '.$experience.' ) AND ';
                }
                
                if($salary != ''){
                    
                    $where .= ' fileuser.salary in ( '.$salary.' ) AND ';
                    
                }
                if($age != ''){
                    
                    $explode_age = explode(',',$age);
                    //echo count($explode_age);die
                    //echo "<PRE>";print_R($explode_age);die;
                    if(count($explode_age) > 0){
                        //echo $explode_age[0];die;
                        if(count($explode_age) == 2){
                            if($explode_age[0] != 0 && $explode_age[1] != 0){
                                $where .= ' ( fileuser.age between '.$explode_age[0].' and '.$explode_age[1].') AND ';
                            }else if($explode_age[1] != 0){
                                $where .= ' fileuser.age >= ( '.$explode_age[1].' ) AND fileuser.age != 0 AND';
                            }else if($explode_age[0] != 0){
                                $where .= ' fileuser.age <= ( '.$explode_age[0].' ) AND fileuser.age != 0 AND';
                            }
                            
                        }
                    }
                    
                }
                
                $userDataQuery = "SELECT * FROM `fileuser` where 1 and ".trim($where,'AND ');
                // echo $userDataQuery;die;
                $filedata = $con->query($userDataQuery);
                $record = mysqli_num_rows($filedata);
              
              echo 'Custom Selection';
          }
          ?>
            </td>
            <td><?php echo $record;?></td>
            <td><?php echo $row['sent_status'];?></td>
            
            <td><?php if($row['sent_status'] == 0) { ?><a href="javascript:void(0);" onclick="editData(<?php echo $row['id'];?>,'<?php echo $row['hyperlink'];?>','<?php echo $row['fileID']?>','<?php echo $row['message'];?>','<?php echo $row['sent_option'];?>','<?php echo $row['sent_time']?>','<?php echo $row['city_id']?>','<?php echo $row['age']?>','<?php echo $row['start_date']?>','<?php echo $row['end_date']?>', <?php echo $row['send_limit'];?>, <?php echo $row['send_again'];?>)">Edit</a> &nbsp;&nbsp;&nbsp; <a href="javascript:void(0);" onclick="deleteData(<?php echo $row['id'];?>)">Delete</a><?php } else { echo '-'; } ?></td>
        </tr>
        <?php $i++; } ?>
      </tbody>
    </table>
    <?php } else { ?>
        <h5>No Data Found</h5>
    <?php } ?>
    </div>
</div>
<div class="modal" tabindex="-1" id="editmodel" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form method="post"  id="updateData" >
            <div class="row">
                <div class="col-md-12">
                    <label for="hyperlink">Hyperlink</label>
                    <input type="text" class="form-control" id="hyperlink" placeholder="Enter Hyperlink" name="hyperlink">  
                    <span id="hyperlinkerr" class="error"></span>
                    <input type="hidden" name="updateId" value="" id="updateId">
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <label for="message">Message</label>
                    <input type="text" class="form-control" id="emessage" placeholder="Enter Message" name="emessage">  
                    
                </div>
            </div>
            <div class="row mt-3">
              <div class="col-md-12">
                  <div class="form-check">
                      <div style="margin-bottom: 10px;">
                          <input class="form-check-input" type="radio" value="1"  id="select_file" name="select_option" checked onchange="updateSection()">
                          <label class="form-check-label" for="select_file">
                              Select File
                          </label>
                      </div>
                      <div class="row mt-3">
                        <div class="col-md-12 selectFileDiv" id="filechoose" style="display:none">
                        <label for="message">Select File</label>
                        <select class="form-control" id="filename" name="filename">
                          <option value="">Select</option>
                          <?php
                          $getData = "SELECT * FROM `filedata`";
                          $data = mysqli_query($con,$getData);
                          if(mysqli_num_rows($data) > 0 ){
                                while($row = $data->fetch_assoc()) { ?>
                                <option value="<?php echo $row['id'];?>" record-val="<?php echo $row['record']; ?>"><?php echo $row['filename'];?></option>   
                                <?php }
                          }
                          ?>
                        </select>
                        <span id="filenameerr" class="error"></span>
                      </div>
                    </div>
                    <div style="margin-bottom: 10px;" class="mt-3">
                        <input class="form-check-input" type="radio" value="0" id="select_file1" name="select_option" onchange="updateSection()">
                        <label class="form-check-label" for="select_file1">
                             Custom Selection
                        </label>
                    </div>
                    <div style="margin-bottom: 10px;" class="mt-3">
                        <input class="form-check-input" type="radio" value="2" id="select_file2" name="select_option" onchange="updateSection()">
                        <label class="form-check-label" for="select_file2">
                         Custom Selection -Connect
                        </label>
                    </div>
                  </div>
              </div>
          </div>
            <div class="col-md-12 customSelectionDiv" style="display:none">
              <label for="message">Select Location</label>
                <select class="form-control" id="location"  name="location">
                  <option value="">Select</option>
                  <?php
                //   $getData = "SELECT DISTINCT location FROM `fileuser` ORDER BY location";
                //   $data = mysqli_query($con, $getData);
                //   if(mysqli_num_rows($data) > 0 ){
                //       while($row = $data->fetch_assoc()) { 
                 $getData = "SELECT * FROM `cities` ORDER BY city_name";
                  $data = mysqli_query($con, $getData);
                  if(mysqli_num_rows($data) > 0 ){
                      while($row = $data->fetch_assoc()) {
                  ?>
                          <option value="<?php echo $row['city_id'];?>"><?php echo $row['city_name']; ?></option>
                  <?php
                      }
                  }
                  ?>
                </select>
            </div>
            <!--<div class="row mt-3">-->
            <!--<div class="col-md-12 customSelectionDiv" style="display:none">-->
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
            <!--</div>-->
            <!--<div class="row mt-3">-->
            <!--<div class="col-md-12 customSelectionDiv"  style="display:none">-->
            <!--  <label for="message">Select Salary</label>-->
            <!--    <select class="form-control" id="salary" multiple name="salary[]">-->
            <!--      <option value="">Select</option>-->
                  <?php
                //   for ($i = 2; $i <= 20; $i++) {
                //     $salary = $i * 100000; 
                //     echo "<option value='$salary'>$i Lacs</option>";
                // }
                  ?>
            <!--    </select>-->
            <!--</div>-->
            <!--</div>-->
            <div class="row mt-3">
              <div class="col-md-12 customSelectionDiv"  style="display:none">
                <label for="message">Select Min Age</label>
                  <select class="form-control" id="min_age" name="min_age">
                    <option value="" selected>Select Min Age</option>
                    <?php
                    for ($i = 1; $i <= 40; $i++) {
                      echo "<option value='$i'>$i</option>";
                    }
                    ?>
                  </select>
              </div>
            </div>
            <div class="row mt-3">
            <div class="col-md-12 customSelectionDiv"  style="display:none">
              <label for="message">Select Max Age</label>
                <select class="form-control" id="max_age" name="max_age">
                  <option value="" selected>Select Max Age</option>
                  <?php
                  for ($i = 1; $i <= 40; $i++) {
                    echo "<option value='$i'>$i</option>";
                  }
                  ?>
                </select>
            </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12 customSelectionDiv"  style="display:none">
                    <label for="daterange">Select Date Range</label>
                    <input type="text" id="daterange" name="daterange" class="form-control">   
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12 customSelectionDiv" style="display:none">
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
            <div class="row mt-3" style="margin-top:10px;">
                <div class="col-md-12">
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

            <div class="row mt-3" id="date_section" style="display:none;">
                <div class="col-md-12" >
                    <label for="datetime">Date / Time</label>
                    <input class="form-control" type="datetime-local" name="sent_time" id="sent_time"/>  
                </div>
            </div>
            
            
            <!--<div class="row mt-3">-->
            <!--    <div class="col-md-5" style="margin-left:10px">-->
            <!--      <input class="form-check-input" type="checkbox" id="send_again" name="send_again">-->
            <!--      <label class="form-check-label" for="send_again">-->
            <!--          Do Not Send Again to Same Users-->
            <!--      </label>-->
            <!--    </div>-->
              
            <!--    <div class="col-md-6" style="display:none;"  id="count_button" >-->
            <!--        <button type="button" class="btn btn-info" onclick="getCount()">-->
            <!--        Get Target Users Count-->
            <!--        </button>-->
            <!--        <br/>-->
            <!--        <span id="total_user_count"></span> -->
            <!--    </div>-->
                
            <!--    <div class="col-md-6">-->
            <!--        <button type="submit" class="btn btn-info">-->
            <!--        Update-->
            <!--        </button>-->
            <!--    </div>-->
            <!--</div>-->
            <div class="row mt-3 align-items-center">
                <div class="col-md-4 d-flex align-items-center">
                    <input class="form-check-input" type="checkbox" id="send_again" name="send_again" style="margin-left: 1px;">
                    <label class="form-check-label" for="send_again" style="margin-left: 23px;">
                        Do Not Send Again to Same Users
                    </label>
                </div>
            
                <div class="col-md-4 d-flex flex-column align-items-center" id="count_button">
                    <button type="button" class="btn btn-info mb-2" onclick="getCount()">
                        Get Target Users Count
                    </button>
                    <span id="total_user_count"><span id="user_count_value"></span></span> 
                </div>
                
                <div class="col-md-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-info">
                        Update
                    </button>
                </div>
            </div>

        </form>
        </div>
      </div>
    </div>
  </div>
</div>


<style>
    .container{
        margin-top:50px;
    }
    .modal-dialog{
        overflow-y: initial !important
    }
    .modal-body{
        height: 70vh;
        overflow-y: auto;
    }
</style>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
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
<!--<script type="text/javascript">-->
<!--    $(function() {-->
<!--        $('#daterange').daterangepicker({-->
<!--            opens: 'right',-->
            // startDate: '03/14/2018',
            // endDate: '04/15/2018',
<!--            ranges: {-->
<!--               'Today': [moment(), moment()],-->
<!--               'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],-->
<!--               'Last 7 Days': [moment().subtract(6, 'days'), moment()],-->
<!--               'Last 30 Days': [moment().subtract(29, 'days'), moment()],-->
<!--               'This Month': [moment().startOf('month'), moment().endOf('month')],-->
<!--               'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]-->
<!--            },-->
<!--            locale: {-->
<!--                format: 'YYYY-MM-DD'-->
<!--            }-->
<!--        });-->
<!--    });-->
<!--</script>-->
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
            //   $('#sent_time').attr("required", "false");
            $('#sent_time').removeAttr("required");
            }
        }
    }
    
    function editData(id,hyperlink,fileid,message,sent_status,sent_time,location,age,start_date,end_date,limit,send_again){
     console.log("age => "+age);
     console.log("limit => "+limit);
        $('#editmodel').modal('toggle');
        // $('#daterange').daterangepicker({
        //     opens: 'right',
        //     startDate: start_date,
        //     endDate: end_date,
        //     ranges: {
        //       'Today': [moment(), moment()],
        //       'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
        //       'Last 7 Days': [moment().subtract(6, 'days'), moment()],
        //       'Last 30 Days': [moment().subtract(29, 'days'), moment()],
        //       'This Month': [moment().startOf('month'), moment().endOf('month')],
        //       'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        //     },
        //     locale: {
        //         format: 'YYYY-MM-DD'
        //     }
        // });
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
        $('#hyperlink').val(hyperlink);
        $('#emessage').val(message);
        $('#updateId').val(id);
        if(limit == 0){
            $('#sms_cap').val('');
        }else{
            $('#sms_cap').val(limit);
        }
        
        if (send_again == 1) {
            $('#send_again').prop('checked', true);
        } else {
            $('#send_again').prop('checked', false);
        }
        if(sent_status == 1){
            $('#date_section').css('display','block');

            if($("#sent_time").length != 0){
              $('#sent_time').attr("required", "true");
              $('#sent_time').val(sent_time);
            }
            
            $('#send_later').attr('checked', true);
            
        }else{
            $('#date_section').css('display','none');
            if($("#sent_time").length != 0){
            //   $('#sent_time').attr("required", "false");
            $('#sent_time').removeAttr("required");
            } 
            $('#send_now').attr('checked', true);
        }
        if(fileid == 0){
          $('#filechoose').css('display','none');
          $("#select_file1").attr('checked', true);
          $('.customSelectionDiv').css('display','block');
          $('#count_button').css('display','block');
          if(location != ''){
            var locArr = location.split(",");
            //var locArr = ["Andhra Pradesh", "Bengaluru"];

            for(var l=0;l<locArr.length;l++){
              $('#location option[value="' + locArr[l].trim() + '"]').prop("selected", true);

            }
          }
          
        //   if(salary != ''){

        //     var salArr = salary.split(",");
        //     //var locArr = ["Andhra Pradesh", "Bengaluru"];

        //     for(var l=0;l<salArr.length;l++){
        //       $('#salary option[value="' + salArr[l] + '"]').prop("selected", true);

        //     }
        //   }

        //   if(experience != ''){

        //     var expArr = experience.split(",");
        //     for(var l=0;l<expArr.length;l++){
        //       $('#experience option[value="' + expArr[l] + '"]').prop("selected", true);

        //     }
        //   }
          if(age != ''){

            var ageArr = age.split(",");
            if(ageArr[0] != undefined){
              $('#min_age option[value="' + ageArr[0] + '"]').prop("selected", true);
            }
            
            if(ageArr[1] != undefined){
              $('#max_age option[value="' + ageArr[1].trim() + '"]').prop("selected", true);
            }
          }
        }else{
          $('.customSelectionDiv').css('display','none');
          $('#filechoose').css('display','block');
          $("#select_file").attr('checked', true);
          //$('#filename').css('display','block');
          $('#filename').val(fileid).attr("selected", "selected");
          $('.selectFileDiv').css('display','block');
        }
    }
    $(document).ready(function() {
        var table = $('#example').DataTable({
          "stateSave" :true
        });

    });
    
    
    $('#updateData').on('submit',function(e){
        e.preventDefault();
        var link = $('#hyperlink').val();
        var filename = $('#filename').val();
        var send_option = $("input[name='sent_option']:checked").val();
        var sent_time = '';
        var location = $("#location").val();
        // var salary = $("#salary").val();
        // var experience = $("#experience").val();
        var min_age = $("#min_age").val();
        var max_age = $("#max_age").val();
        var select_option = $("input[name='select_option']:checked").val();
        var daterange = $('#daterange').val();
        var limit = $("#sms_cap").val() || 0;
        var send_again = $("#send_again").is(':checked') ? 1 : 0;
        
        var startDate = '';
        var endDate = '';
        if(daterange != '') {
            var dates = daterange.split(' - ');
            startDate = dates[0];
            endDate = dates[1];
        }

        if(send_option == 1){
          if($("#sent_time").length != 0){
            sent_time = $('#sent_time').val();
          }
        }
        
        var flag = [];
        
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
        
        if(select_option == 1){

            if(filename == ''){
                flag.push(0);
                $('#filenameerr').html("This Field is required!!");
            }else{
                flag.push(1);
                $('#filenameerr').html("");
            }

        }
        
        if(!flag.includes(0)){
            $('.loading').css('display','block');
            $.ajax({
            url:"http://localhost/send/sms.php",
            type: "post", 
            dataType: 'json',
            data: {'hyperlink': $('#hyperlink').val(), 'type': "edit",'id':$('#updateId').val(),'fileid':$('#filename').val(),'message':$('#emessage').val(),'send_option':send_option,'sent_time':sent_time,'location':location,'min_age':min_age,'max_age':max_age,'select_option':select_option,'startDate':startDate,'endDate':endDate,'limit':limit,'send_again':send_again},
            success:function(result){
                $('.loading').css('display','none');
                var edid = $('#updateId').val();
                var newval = $('#hyperlink').val();
                // if($("#sent_time").length != 0){
                //   $('#sent_time').val(sent_time);
                // }
                $('#filename').val(filename).attr("selected", "selected");
                $('#hyper'+edid).html(newval);
                $('#mess'+edid).html($('#emessage').val());
                $('#hyperlink').val(newval);
                $('#editmodel').modal('hide');
                $('#success').html('');
                $('#success').html('Update sucessfully!!');
                $('#success').css('display','block');
            }
        });
        }
       
        

    });
    
    function deleteData(id){
            if (confirm('Are you sure you want to delete?')) {
            $('.loading').css('display','block');
            $.ajax({
                url:"http://localhost/send/sms.php",
                type: "post", 
                dataType: 'json',
                data: {type: "delsend",'id':id},
                success:function(result){
                    $('.loading').css('display','none');
                    $('#del'+id).hide();
                    $('#success').html('');
                    $('#success').html('Delete sucessfully!!');
                    $('#success').css('display','block');
                }
            });
            }
        } 

function updateSection(){
  var selectFileRadio = document.getElementById("select_file");
  $('#count_button').css('display','none');
  if (selectFileRadio.checked) {
      $('.selectFileDiv').css('display','block');
      $('.customSelectionDiv').css('display','none');
      $('#get_count').css('display','none');
  } else {
      $('.selectFileDiv').css('display','none');
      $('.customSelectionDiv').css('display','block');
      $('#get_count').css('display','block');
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
    var link = $('#hyperlink').val();
    var send_again = $("#send_again").is(':checked') ? 1 : 0; 
    var custome_connect = $("input[name='select_option']:checked").val();
    
    var startDate = '';
    var endDate = '';
    if(daterange != '') {
        var dates = daterange.split(' - ');
        startDate = dates[0];
        endDate = dates[1];
    }
    
    var flag = 1;
    if(location == '' && fromage == '' && toage == ''  && daterange == ''){
        flag = 0 ;
    }
    // alert(flag)
    if(flag == 1){
        //$('.loading').css('display','block');
        $.ajax({
            url:"http://localhost/send/sms.php",
            type: "post", 
            dataType: 'json',
            data: {type: "getcount",'location':location,'fromage':fromage,'toage':toage,'startDate': startDate,'endDate': endDate, 'send_again':send_again, 'link':link,'custome_connect':custome_connect},
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
