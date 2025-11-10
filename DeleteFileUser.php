<?php 

// Start the session
session_start();
require('sessioncheck.php');

ini_set('max_execution_time', 0);
$con = mysqli_connect("localhost", "root", "", "smses_send");
$getsql = "SELECT * FROM `filedata` order by id DESC";
$smsdata = mysqli_query($con,$getsql);
//echo "<PRE>";print_R($smsdata);die;
?>
 
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.2/css/dataTables.bootstrap4.min.css">
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
    <h2 class="mb-3 mt-3">Delete Fileuser</h2><a class="mb-3 btn btn-primary" href="http://localhost/send/SMSsend.php">Send SMS</a>&nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/EditSMS.php">Edit SMS</a>&nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/FileUpload.php">File Upload</a>&nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/reports.php">Reports</a>&nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="https://www.smses.in/ct/login.php">Click Track</a>
    &nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/logs.php">Logs across Pages</a>
    &nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/import_urgent_vacancy.php">IMPORT UrgentVacancy Data</a>
    &nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/import_connect_users.php">Import from Connect DB</a>
    
    <div class="loading" style="display:none;">Loading&#8230;</div>
    <?php if(mysqli_num_rows($smsdata) > 0 ) { ?>
    <table class="table" id="example">
      <thead>
        <tr>
          <th scope="col">#</th>
          <th scope="col">File Name</th>
          <th scope="col">Record</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php $i =1; while($row = $smsdata->fetch_assoc()) { ?>
        <tr id="del<?php echo $row['id'];?>">
          <th scope="row"><?php echo $i;?></th>
          <td><?php echo $row['filename'];?></td>
          <td><?php echo $row['record'];?></td>
          <td><a href="javascript:void(0);" onclick="DeleteData(<?php echo $row['id'];?>)">Delete</a></td>
        </tr>
        <?php $i++; } ?>
      </tbody>
    </table>
    <?php } else { ?>
        <h5>No Data Found</h5>
    <?php } ?>
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
    
    function DeleteData(id){
        if (confirm('Are you sure you want to delete?')) {
            $('.loading').css('display','block');
        $.ajax({
            url:"http://localhost/send/sms.php",
            type: "post", 
            dataType: 'json',
            data: {type: "del",'id':id},
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
