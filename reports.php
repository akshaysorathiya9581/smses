<?php 

// Start the session
session_start();
require('sessioncheck.php');

ini_set('max_execution_time', 0);
$con = mysqli_connect("localhost", "root", "", "smses_send");

if($_GET['date'] == ''){
    $today = date('Y-m-d');
}else{
    $today = date('Y-m-d',strtotime($_GET['date']));
}

$getsql = "SELECT send_data.* , filedata.filename as fname
FROM `send_data` 
LEFT JOIN filedata on send_data.fileID = filedata.id
where date(created_at) = '".$today."' order by id DESC";
// echo $getsql;die;
$smsdata = mysqli_query($con,$getsql);
?>
 
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.2/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" />
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.11.2/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.2/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
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
        <h2 class="mb-3 mt-3">Reports</h2><a class="mb-3 btn btn-primary" href="http://localhost/send/SMSsend.php">Send SMS</a>&nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/EditSMS.php">Edit SMS</a>&nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/DeleteFileUser.php">Delete Fileuser</a>&nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/FileUpload.php">File Upload</a>&nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="https://www.smses.in/ct/login.php">Click Track</a>
        &nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/logs.php">Logs across Pages</a>
        &nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/import_urgent_vacancy.php">IMPORT UrgentVacancy Data</a>
        &nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/import_connect_users.php">Import from Connect DB</a>
    <div class="loading" style="display:none;">Loading&#8230;</div>
    
    <form>
        <div class="row">
            <div class="col-md-6">
                <label for="date">Select Date</label>
                <input type="text" class="form-control" id="datepicker" name="datepicker">  
            </div>
        </div>
    </form>
    <?php if(mysqli_num_rows($smsdata) > 0 ) { ?>
    <table class="table" id="example">
      <thead>
        <tr>
          <th scope="col">#</th>
          <th>Hyperlink</th>
          <th>Sender</th>
          <th>Message</th>
          <th scope="col">Total SMS Sent</th>
          <th>File Name</th>
          <th>Sent Time</th>
          <th>Sms cap</th>
          <th>Age selection</th>
          <th>City selection</th>
          <th>Created Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php $i =1; while($row = $smsdata->fetch_assoc()) {
        
        //echo "<PRE>";print_R($row);die;
        ?>
        <tr id="del<?php echo $row['id'];?>">
          <th scope="row"><?php echo $i;?></th>
          <td id="hyper<?php echo $row['id']?>"><?php echo $row['hyperlink'];?></td>
          <td><?php echo $row['sender'];?></td>
          <td id="mess<?php echo $row['id']?>"><?php echo $row['message'];?></td>
          <?php 
          if($row['fileID'] > 0){
              
              $filename = "SELECT * FROM `filedata` where id=".$row['fileID'];
          
              $records = mysqli_query($con,$filename);
              while($data = mysqli_fetch_array($records))
                {
                    $record = $data['record'];
                    //echo $data['filename'];
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
                            
                        }else{
                            $where .= ' fileuser.age in ( '.$explode_age[0].' ) AND ';
                        }
                    }
                    
                }
                
                $userDataQuery = "SELECT * FROM `fileuser` where 1 and ".trim($where,'AND ');
                // echo $userDataQuery;die;
                $filedata = $con->query($userDataQuery);
                $record = mysqli_num_rows($filedata);
            }
          ?>
            
            <td><?php echo $record;?></td>
            <td><?php if($row['fileID'] > 0) { echo $row['fname']; } else { echo 'Custom Selection';} ?></td>
            <td><?php echo $row['sent_time'];?></td>
            <td><?php echo $row['send_limit'];?></td>
            <td><?php if($row['age']){
                echo $row['age']; 
            }else{
                echo '-';
            }?></td>
            <td><?php if($row['city_id']){
                $city_arr = explode(',',$row['city_id']);
                $city_name = '';
                foreach($city_arr as $city){
                    $c_query = "SELECT city_name FROM cities WHERE city_id=".$city;
                    $c_res = mysqli_query($con,$c_query);
                    if (mysqli_num_rows($c_res) > 0) {
                        $c_name = mysqli_fetch_row($c_res);
                        $city_name .= $c_name[0].',';
                    }
                }
                echo trim($city_name,',');
            }else{
                echo '-';
            }
            ?></td>
            <td><?php echo date("Y-m-d H:i:s",strtotime($row['created_at']));?></td>
            <td><?php if($row['sent_status'] == 0) { ?><a href="javascript:void(0);" onclick="editData(<?php echo $row['id'];?>,'<?php echo $row['hyperlink'];?>','<?php echo $row['fileID']?>','<?php echo $row['message'];?>')">Edit</a> &nbsp;&nbsp;&nbsp; <a href="javascript:void(0);" onclick="deleteData(<?php echo $row['id'];?>)">Delete</a><?php } else { echo '-'; } ?></td>
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
                <div class="col-md-12" id="filechoose">
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
            <div class="row mt-3">
                <div class="col-md-6">
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
</style>

<script>
    setTimeout(function() { $('.alert').hide() }, 5000)
    var getdate = '<?php echo $_GET['date']; ?>';
    if(getdate === ''){
        var date = new Date();
        var today = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    }else{
        var today = getdate;
    }
    
    
    $( '#datepicker' ).datepicker( 'setDate', today );
    $('#datepicker').datepicker({
        format: 'Y-m-d',
        todayHighlight: true,
    }).on('changeDate',function(e){
         var value = $(this).val();
         window.location.href = 'http://localhost/send/reports.php?date='+value;
    });
    
    function editData(id,hyperlink,fileid,message){
        $('#editmodel').modal('toggle');
        $('#hyperlink').val(hyperlink);
        $('#filename').val(fileid).attr("selected", "selected");
        $('#emessage').val(message);
        $('#updateId').val(id);
    }
    
    
    
    $('#updateData').on('submit',function(e){
        e.preventDefault();
        var link = $('#hyperlink').val();
        var filename = $('#filename').val();
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
        
        if(filename == ''){
            flag.push(0);
            $('#filenameerr').html("This Field is required!!");
        }else{
            flag.push(1);
            $('#filenameerr').html("");
        }
        
        if(!flag.includes(0)){
            $('.loading').css('display','block');
            $.ajax({
            url:"http://localhost/send/sms.php",
            type: "post", 
            dataType: 'json',
            data: {'hyperlink': $('#hyperlink').val(), 'type': "edit",'id':$('#updateId').val(),'fileid':$('#filename').val(),'message':$('#emessage').val()},
            success:function(result){
                $('.loading').css('display','none');
                var edid = $('#updateId').val();
                var newval = $('#hyperlink').val()
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
        var table = $('#example').DataTable({
          "stateSave" :true
        });

    });
    
    
    
    

</script>
