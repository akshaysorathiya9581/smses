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

$getsql = "SELECT *
FROM `msm_response` order by id desc
";
//echo $getsql;die;
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
    
    
    
    <h2 class="mb-3 mt-3">Logs</h2><a class="mb-3 btn btn-primary" href="http://localhost/send/SMSsend.php">Send SMS</a>&nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/EditSMS.php">Edit SMS</a>&nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/DeleteFileUser.php">Delete Fileuser</a>&nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/FileUpload.php">File Upload</a>&nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="https://www.smses.in/ct/login.php">Click Track</a>
    &nbsp;&nbsp;
    <a class="mb-3 btn btn-primary" href="http://localhost/send/reports.php">Reports</a>
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
          <th>Mobile</th>
          <th>Response</th>
          <th>Created Date</th>
        </tr>
      </thead>
      <tbody>
        <?php $i =1; while($row = $smsdata->fetch_assoc()) { ?>
        <tr>
          <th scope="row"><?php echo $i;?></th>
          <td><?php echo $row['mobile'];?></td>
          <td><?php echo $row['response'];?></td>
          <td><?php echo $row['created_at'];?></td>
          
        </tr>
        <?php $i++; } ?>
      </tbody>
    </table>
    <?php } else { ?>
        <h5>No Data Found</h5>
    <?php } ?>
    </div>
</div>



<style>
    .container{
        margin-top:50px;
    }
</style>

<script>
    
    
    $(document).ready(function() {
        var table = $('#example').DataTable({
          "lengthMenu": [[10,25,50,100,500], [10,25,50,100,500]],
          "pageLength": 500,
        });

    });

</script>
