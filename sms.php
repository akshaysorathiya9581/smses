<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
session_start();
$con = mysqli_connect("localhost", "root", "", "smses_send");
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
if($_POST['type'] == 1){
    if($_POST['username'] == '' && $_POST['password'] == ''){
    header("Location: http://localhost/send/login.php?status=0");
    }
    else {
        if($_POST['username'] == 'admin' && $_POST['password'] == '7892'){
            $_SESSION['username'] = 'admin';
            header("Location: http://localhost/send/FileUpload.php");
        }else{
            header("Location: http://localhost/send/login.php?status=1");
        }
    }
}else if($_POST['type'] == 2){
    
    $sql = "Update send_data set hyperlink = '".$_POST['hyperlink']."' where id = '".$_POST['id']."'";
    mysqli_query($con,$sql);
    echo json_encode("update");exit();
}else if($_POST['type'] == 'edit'){

    // echo "<PRE>";print_R($_POST);die;
    $new_hyper_camp = explode('/',$_POST['hyperlink']);
    $camp = end($new_hyper_camp);
    if(empty($camp)) {
        $camp = prev($new_hyper_camp);
    }
    $limit = isset($_POST['limit']) && !empty($_POST['limit']) ? $_POST['limit'] : 0;
    $sendAgain = isset($_POST['send_again']) ? $_POST['send_again'] : 0;
    $time = date('Y-m-d h:i:s');
    if(!empty($_POST['sent_time'])){
       $time = date('Y-m-d H:i:s',strtotime($_POST['sent_time'])); 
    }
    
    if(!empty($_POST['startDate'])){
        $startDate = date('Y-m-d', strtotime($_POST['startDate']));
    }
    
    if(!empty($_POST['endDate'])){
        $endDate = date('Y-m-d', strtotime($_POST['endDate']));
    }
    $location = '';
    if(isset($_POST['location'])){
        if(!empty($_POST['location'])){
            $location = $_POST['location'];
        }
        
    }


    $age = '';
    if(isset($_POST['min_age']) && isset($_POST['max_age'])){
        if(!empty($_POST['min_age']) && !empty($_POST['max_age'])){
            $age = $_POST['min_age'].', '.$_POST['max_age'];
        }
        
    }else if(isset($_POST['min_age'])){
        if(!empty($_POST['min_age'])){
            $age = $_POST['min_age'].',0';
        }
        
    }else if(isset($_POST['max_age'])){
        if(!empty($_POST['max_age'])){
            $age = '0,'.$_POST['max_age'];    
        }
        
    }
    //echo "<PRE>";print_R($_POST);die;
    if($_POST['select_option'] == 0 || $_POST['select_option'] == 2){
        if(trim($location) == '' && trim($experience) == '' && trim($salary) == '' && trim($age,',') == '' && $startDate == ''){
            $json_arr['sucess'] = 0;
            $json_arr['mess'] = 'Please select any one option from custom selections';
            
        }else{
            $where = '';
            if(!empty($_POST['location'])){
                // $locstr = '';
                // foreach($_POST['location'] as $loc){
                //     if($loc != ''){
                //         $locstr .= "'$loc',";
                //     } 
                // }
                // if (!empty($locstr)) {
                //     $where .= ' fileuser.city_id IN (' . trim($locstr, ',') . ') AND ';
                // }
                 $where .= ' fileuser.city_id = "'.$_POST['location'].'" AND ';
            }
            
            if(!empty($_POST['select_option'])){
                if($_POST['select_option'] == 2){
                      $where .= ' fileuser.reference = "connect" AND ';
                }
            }
            
            if(!empty($_POST['min_age']) && !empty($_POST['max_age'])){
                $where .= ' ( fileuser.age between '.$_POST['min_age'].' and '.$_POST['max_age'].') AND ';
            }else if(!empty($_POST['min_age'])){
                $where .= ' fileuser.age <= ( '.$_POST['min_age'].' ) AND fileuser.age != 0 AND';
            }else if(!empty($_POST['max_age'])){
                $where .= ' fileuser.age >= ( '.$_POST['max_age'].' ) AND fileuser.age != 0 AND';
            }
            // $userDataQuery = "SELECT id,campaign FROM `fileuser` where is_active = 1 AND sent_status = 0 AND ".trim($where,'AND ')." ORDER BY id";
            // $userDataQuery = "SELECT * FROM `fileuser` where 1 = 1 AND sent_status = 0 AND ".trim($where,'AND ')." ORDER BY id";
            $userDataQuery = "SELECT * FROM `fileuser` where 1 = 1 AND sent_status = 0 AND ".trim($where,'AND ')." ";
            if($sendAgain){
                $userDataQuery .= " AND mobile not in (select mobile from queue_data where sent_status = 0)";
            }
            $userDataQuery .= " ORDER BY id";
            if (isset($limit) && $limit > 0) {
                $userDataQuery .= " LIMIT $limit";
            }
            $filedata = $con->query($userDataQuery);
            if ($filedata->num_rows > 0) {
                while ($row = $filedata->fetch_assoc()) {
                    $id = $row['id'];
                    $campaign = $row['campaign'];
                    $mobile = $row['mobile'];
                    $name = $row['name'];
                    
                    $insQueData = "INSERT into queue_data (sent_option,hyperlink,message,mobile,sent_status,name,sent_time,send_id) values ('".$_POST['send_option']."','".$hyperlink."','".$_POST['message']."','".$mobile."',0,'".$name."','".$time."',".$_POST['id'].")";
                    // echo $insQueData;exit;
                    mysqli_query($con,$insQueData);
                    
                    if($sendAgain){
                        if ($campaign !== $camp) {
                            $updateQuery = "UPDATE `fileuser` SET campaign = '$camp', sent_status = 0 WHERE id = $id ";
                            $con->query($updateQuery);
                        }
                    }else{
                        $updateQuery = "UPDATE `fileuser` SET campaign = '$camp',sent_status = 0 WHERE id = $id ";
                        $con->query($updateQuery);
                    }
                }
            }
            
            $sql = "Update send_data set hyperlink = '".$_POST['hyperlink']."' ,  message = '".$_POST['message']."' , fileID = 0 ,sent_option = '".$_POST['send_option']."' , sent_time = '".$time."',city_id = '".$location."' , age = '".$age."', start_date = '".$startDate ."', end_date = '".$endDate."', send_limit = $limit, send_again = $sendAgain where id = '".$_POST['id']."'";
            // echo $sql;exit;
            mysqli_query($con,$sql);
            
            $json_arr['sucess'] = 1;
        }
        
    }else{
        
        $sql = "Update send_data set hyperlink = '".$_POST['hyperlink']."' , fileID = '".$_POST['fileid']."' , message = '".$_POST['message']."' , sent_option = '".$_POST['send_option']."' , sent_time = '".$time."' where id = '".$_POST['id']."'";
        mysqli_query($con,$sql);
        
        $userDataQuery = "SELECT * FROM `fileuser` where 1 = 1  AND fileid = '".$_POST['fileid']."' ORDER BY id";
        $filedata = $con->query($userDataQuery);
        if($filedata->num_rows > 0){
            while ($row1 = $filedata->fetch_assoc()) {
                $id = $row1['id'];
                $campaign = $row1['campaign'];
                $mobile = $row1['mobile'];
                $name = $row1['name'];
                
                $insQueData = "INSERT into queue_data (sent_option,hyperlink,message,mobile,sent_status,name,sent_time,send_id) values (".$_POST['sent_option'].",'".$hyperlink."','".$_POST['message']."','".$mobile."',0,'".$name."','".$time."',".$_POST['id'].")";
                mysqli_query($con,$insQueData);
                
                if($sendAgain){
                    if ($campaign !== $camp) {
                        $updateQuery = "UPDATE `fileuser` SET campaign = '$camp', sent_status = 0 WHERE id = $id ";
                        $con->query($updateQuery);
                    }
                }else{
                    $updateQuery = "UPDATE `fileuser` SET campaign = '$camp',sent_status = 0 WHERE id = $id ";
                    $con->query($updateQuery);
                }
            }  
        }
        
        $json_arr['sucess'] = 1;
        
    }   
    
        
    echo json_encode($json_arr);exit();

    
    
    //echo $sql;die;
    
}
else if($_POST['type'] == 'del'){
    $sql = "DELETE FROM `fileuser` WHERE `fileid` = ".$_POST['id'];
    mysqli_query($con,$sql);
    $sqql2 = "DELETE FROM `filedata` WHERE `id` = ".$_POST['id'];
    mysqli_query($con,$sqql2);
    echo json_encode("delete");exit();
}else if($_POST['type'] == 'delsend'){
    $sql = "DELETE FROM `send_data` WHERE `id` = ".$_POST['id'];
    mysqli_query($con,$sql);
    
    echo json_encode("delete");exit();
}else if($_POST['type'] == 'getcount'){
    //echo "<PRE>";print_R($_POST);exit;
    $sendAgain = $sendAgain = isset($_POST['send_again']) ? $_POST['send_again'] : 0;
    $new_hyper_camp = explode('/',$_POST['link']);
    $camp = end($new_hyper_camp);
    if(empty($camp)) {
        $camp = prev($new_hyper_camp);
    }
    $where = '';
    if(!empty($_POST['location'])){
        // $locstr = '';
        // foreach($_POST['location'] as $loc){
        //     if($loc != ''){
        //         $locstr .= "'$loc',";
        //     }
            
        // }
        // $where .= ' fileuser.location IN ( '.trim($locstr,',').' ) AND ';
        // if (!empty($locstr)) {
        //     $where .= ' fileuser.city_id IN (' . trim($locstr, ',') . ') AND ';
        // }
        $where .= ' fileuser.city_id = "'.$_POST['location'].'" AND ';
    }
    
    if(!empty($_POST['custome_connect'])){
        if($_POST['custome_connect'] == 2){
            $where .= ' fileuser.reference = "connect" AND ';
        }
    }
    // if(!empty($_POST['exp'])){
    //     $experience = implode(',',$_POST['exp']);
    //     if (!empty($experience)) {
    //         $where .= 'fileuser.experience >= ( '.$experience.' ) AND ';
    //     }
    // }
   
    // if(!empty($_POST['salary'])){
    //     $salary = implode(',',$_POST['salary']);
    //     if (!empty($salary)) {
    //         $where .= ' fileuser.salary in ( '.$salary.' ) AND ';    
    //     }
    // }
    
    if(!empty($_POST['fromage']) && !empty($_POST['toage'])){
        $where .= ' ( fileuser.age between '.$_POST['fromage'].' and '.$_POST['toage'].') AND ';
    }else if(!empty($_POST['fromage'])){
        $where .= ' fileuser.age <= ( '.$_POST['fromage'].' ) AND fileuser.age != 0 AND';
    }else if(!empty($_POST['toage'])){
        $where .= ' fileuser.age >= ( '.$_POST['toage'].' ) AND fileuser.age != 0 AND';
    }
    
    if(!empty($_POST['startDate'])){
        $where .= ' (DATE(fileuser.dob) between "'.$_POST['startDate'].'" and "'.$_POST['endDate'].'") AND ';
    }
    
    // $userDataQuery = "SELECT * FROM `fileuser` where 1 AND ".trim($where,'AND ');
    // $filedata = $con->query($userDataQuery);
    // echo mysqli_num_rows($filedata);exit;
    
    if(!empty($where)){
        // $userDataQuery = "SELECT * FROM `fileuser` where is_active = 1  AND ".trim($where,'AND ');
        $userDataQuery = "SELECT * FROM `fileuser` where 1 = 1 AND ".trim($where,'AND ');
    }else{
        // $userDataQuery = "SELECT * FROM `fileuser` where is_active = 1  ";
        $userDataQuery = "SELECT * FROM `fileuser` where 1 = 1 ";
    }
    if($sendAgain){
        $userDataQuery .= " AND mobile not in (select mobile from queue_data where sent_status = 0)";
    }
    
    if($sendAgain){
        $camp_Data = $userDataQuery." AND campaign = '".$camp."'";
        $filedata = $con->query($camp_Data);
        if(mysqli_num_rows($filedata) == 0){
            $filedata = $con->query($userDataQuery);
        }
    }else{
        $filedata = $con->query($userDataQuery);
    }
    

    // echo $userDataQuery;exit;
    //$filedata = $con->query($userDataQuery);
    $count = 0;
    if($sendAgain){
        while($row = $filedata->fetch_assoc()) {
            $db_camp = $row['campaign'];
            $db_sent_status = $row['sent_status'];
            // echo "camp => ".$camp."db_camp => ".$db_camp;exit;
            if($camp == $db_camp){
                if($db_sent_status == 0){
                    $count++;
                }
            }else{
                $count++;
            }
        }
        echo $count;exit;
    }else{
        echo mysqli_num_rows($filedata);exit;
    }
}
else{
        /*while($row = $filedata->fetch_assoc()) {
            $sendmobile = $row['mobile'];
            //echo $sendmobile;die;
            $name = $row['name'];
            $url = 'https://2factor.in/API/R1/?module=TRANS_SMS&apikey=41979c62-20ee-11ec-a13b-0200cd936042&to='.$sendmobile.'&from='.$sender.'&templatename='.$sender.'&var1='.$name.'&var2='.$hyperlink;
            $curl = curl_init();
            
            curl_setopt_array($curl, array(
              CURLOPT_URL => str_replace (' ', '%20' ,$url),
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => "",
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 30,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => "GET",
              CURLOPT_POSTFIELDS => "{}",
            ));
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl , CURLINFO_HTTP_CODE);
            //echo $httpCode;die;
            $result = json_decode($response);
            //echo "<PRE>";print_R($result);die;
            $err = curl_error($curl);
            
            curl_close($curl);
            
            if ($err) {
              echo "cURL Error #:" . $err;
            } else {
              //echo $response;
            }
            
            $insqueryData = "INSERT into msm_response (mobile,response) values ('".$sendmobile."','".json_encode($response)."')";
            //echo $insqueryData;die;
            mysqli_query($con,$insqueryData);
            curl_close($ch);
            $counter++;
            if($counter == 30){
                sleep(3);
                $counter = 0;
            }
        }*/
         if($_POST['filetype'] == 'uploadfile'){
        $uploadDirectory = "excel/";
        $newfilename= time().str_replace(" ", "", basename($_FILES["uploadfile"]["name"]));
    
        $createUploadPath = $uploadDirectory.$newfilename;
    
        $csvFileType = pathinfo($createUploadPath, PATHINFO_EXTENSION);
        //echo $csvFileType;die;
        if ($csvFileType == 'xlsx' || $csvFileType == 'xls') {
            if (move_uploaded_file($_FILES['uploadfile']['tmp_name'], $createUploadPath)) {
                
                //$sender = $_POST['sender'];
                //$hyperlink = $_POST['hyperlink'];
                if($csvFileType == 'xlsx'){
                    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                }else{
                    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
                }
                
                $spreadsheet = $reader->load("excel/".$newfilename);
                $worksheet = $spreadsheet->getActiveSheet();
                //echo "<PRE>";print_R($worksheet);die;
                $divide = $_POST['number'];
                $date = date('d');
                $month = date('M');
                /*$d=$spreadsheet->getSheet(0)->toArray();
                $divide = $_POST['number'];
                
                $totalfilemake = count($d) / $divide;
                
                $parts = ceil($totalfilemake);*/
                //echo $parts;die;
                //echo "<PRE>";print_R(count($worksheet->getRowIterator));die;
                $i1 = 0; $k1 = 0;
                $parts = 0;$filecount = 1;
                $fileInfo = pathinfo($_FILES["uploadfile"]["name"]);
                $fileNameWithoutExtension = $fileInfo['filename'];
                $insflename = $fileNameWithoutExtension.$filecount;//$date.$month.'-'.$filecount;
                $insquery = "INSERT into filedata (filename,record) values ('".$insflename."','0') ";
                mysqli_query($con,$insquery);
                $last_id = $con->insert_id;
                foreach ($worksheet->getRowIterator() as $row) {
                  
                  $cellIterator = $row->getCellIterator();
                  $cellIterator->setIterateOnlyExistingCells(false);
                  
                  $data = [];
                 
                  foreach ($cellIterator as $cell) {
                    $data[] = $cell->getValue(); 
                }
                // echo "<PRE>";print_r($data);exit;
                if (isset($data[2]) && is_numeric($data[2])) {
                    $numericDate = intval($data[2]);
                    $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($numericDate);
                    $formattedDate = date('d-M-y', $excelDate);
                    $data[2] = $formattedDate;
                }
                
                    // echo "<PRE>";print_r($data);exit;
                    
                  
                  if($i1 != 0){
                    $mobile = explode(',',$data[1]);
                    
                    // echo "<PRE>";print_R($mobile);exit;
                    $mobarray = array();
                    foreach($mobile as $mob){
                        if(strlen($mob) == 10){
                            // echo "<PRE>";print_r($data);exit;
                            if(!in_array($mob,$mobarray)){
                                $sendmobile = $mob;
                                array_push($mobarray,$mob);
                               
                                if($parts == $divide){
                                    $updatequery = "UPDATE filedata set record = '".$parts."' where filename = '".$insflename."'";
                                    mysqli_query($con,$updatequery);
                                    $filecount = $filecount + 1;
                                    $insflename = $fileNameWithoutExtension.$filecount;//$date.$month.'-'.$filecount;
                                    $insquery = "INSERT into filedata (filename,record) values ('".$insflename."','0') ";
                                    mysqli_query($con,$insquery);
                                    $last_id = $con->insert_id;
                                    $parts = 0;
                                    
                                }
                                
                                //$k1 = $k1+1;
                                // $fnameArr = explode(' ',$data[0]);
                                // $fname = $fnameArr[0];
                                if(isset($data[0])){
                                    $fnameArr = explode(' ',$data[0]);
                                    $fname_old = $fnameArr[0];
                                    $fname = mysqli_real_escape_string($con, $fname_old);
                                }else{
                                   $fname = '';
                                }
                                
                                // $location = mysqli_real_escape_string($con, $data[3]);
                                $location = isset($data[3]) && !empty($data[3]) ? mysqli_real_escape_string($con, $data[3]) : '';

                                //$experience = $data[3];
                               // $salary = $data[4];

                                // $experienceValue = 0; 
                                // if ($experience != "Fresher" && $experience != "NA") {
                                //     $expParts = explode(" ", $experience);if (count($expParts) >= 3) {
                                //         $years = intval($expParts[0]);
                                //         $months = intval($expParts[2]);
                                //         $experienceValue = $years . '.' . $months;
                                //     } else {
                                //         $years = intval($expParts[0]);
                                //         $experienceValue = $years;
                                //     }
                                // }

                                // $salaryValue = 0; 
                                // if ($salary != "NA") {
                                //     $salaryValue = parseSalary($salary);
                                // }
                                $dob = $data[2];
                                $age = 0; 
                                if (isset($data[2]) && $data[2] !== 'NA') {
                                    $dob = DateTime::createFromFormat('d-M-y', $data[2]);
                                    $currentDate = new DateTime();
                                    $age = $currentDate->diff($dob)->y;
                                }
                                // echo $age;exit;
                                $query = "SELECT * FROM fileuser WHERE mobile = '$sendmobile'";
                                // echo $query;exit;
                                $result = $con->query($query);
                                if (mysqli_num_rows($result) == 0) {
                                    // $insqueryData = "INSERT into fileuser (fileid,name,mobile,location,experience,salary,age) values ('".$last_id."','".$fname."','".$sendmobile."','".$location."','".$experienceValue."','".$salaryValue."','".$age."')";
                                    $insqueryData = "INSERT into fileuser (fileid,name,mobile,location,age,city_id,is_active) values ('".$last_id."','".$fname."','".$sendmobile."','".$location."','".$age."','0','1')";
                                    // echo $insqueryData;die;
                                    mysqli_query($con,$insqueryData);
                                }
                                $parts = $parts + 1;
                                break;
                            }
                            
                        }
                        
                    }
                    
                    //echo "<PRE>";print_R($response);die;
                    
                  }
                  
                  $i1++;
                  
                }
                
                $updatequery = "UPDATE filedata set record = '".$parts."' where filename = '".$insflename."'";
                mysqli_query($con,$updatequery);
                //echo $k1;die;
                $path = glob('excel/*');
                foreach($path as $filename){
                    unlink($filename);
                }
                $_SESSION['sucess'] = 'Upload Sucessfully';
    
                header("Location: http://localhost/send/FileUpload.php"); 
            }
        }
        else{
           $_SESSION['error'] = 'Select Valid file';
           header("Location: http://localhost/send/FileUpload.php");
    
        }
    }
    else{
        // echo "<PRE>";print_r($_POST);exit;
        $sender = explode('/',$_POST['sender']);
        $hyperlink = $_POST['hyperlink'];
        $limit = isset($_POST['sms_cap']) && !empty($_POST['sms_cap']) ? $_POST['sms_cap'] : 0;
        $sendAgain = isset($_POST['send_again']) ? $_POST['send_again'] : 0;
        $custom_connect = isset($_POST['select_option']) ? $_POST['select_option'] : 0;
        
        /*$userData = "SELECT * FROM `fileuser`  where fileid = ".$_POST['filename'];
        $filedata = mysqli_query($con,$userData);*/
        $counter = 0;
        $new_hyper_camp = explode('/',$_POST['hyperlink']);
        $camp = end($new_hyper_camp);
        if(empty($camp)) {
            $camp = prev($new_hyper_camp);
        }
        $template = '';
        $daterange = explode(' - ',$_POST['daterange']);
        if(isset($daterange)){
            $startDate = date('Y-m-d', strtotime($daterange[0]));
            $endDate = date('Y-m-d', strtotime($daterange[1]));    
        }
        
        if(isset($sender[1])){
            $template = $sender[1];
        }
        
        if($_POST['select_option'] == 0 || $_POST['select_option'] == 2){
            $time = date('Y-m-d h:i:s');
            if(!empty($_POST['sent_time'])){
                $time = date('Y-m-d H:i:s',strtotime($_POST['sent_time'])); 
            }
            
            $location = '';
            if(isset($_POST['location'])){
                // if(!empty($_POST['location'])){
                //     $location = implode(", ", $_POST['location']);
                // }
                $location = $_POST['location'];
                
            }
            // $experience = '';
            // if(isset($_POST['experience'])){
            //     if(!empty($_POST['experience'])){
            //         $experience = implode(", ", $_POST['experience']);
            //     }
                
            // }
            // $salary = '';
            // if(isset($_POST['salary'])){
            //     if(!empty($_POST['salary'])){
            //         $salary = implode(", ", $_POST['salary']);
            //     }
                
            // }
            $age = '';
            if(isset($_POST['min_age']) && isset($_POST['max_age'])){
                if(!empty($_POST['min_age']) && !empty($_POST['max_age'])){
                    $age = $_POST['min_age'].', '.$_POST['max_age'];
                }
                
            }else if(isset($_POST['min_age'])){
                if(!empty($_POST['min_age'])){
                    $age = $_POST['min_age'].',0';
                }
                
            }else if(isset($_POST['max_age'])){
                if(!empty($_POST['max_age'])){
                    $age = '0,'.$_POST['max_age'];    
                }
                
            }
            
            if($location == '' && $age == '' && $startDate == ''){
                $_SESSION['error'] = 'Please select any one option from custom selections';
            }else{
                $where = '';
                if(!empty($_POST['location'])){
                    // $locstr = '';
                    // foreach($_POST['location'] as $loc){
                    //     if($loc != ''){
                    //         $locstr .= "'$loc',";
                    //     }
                        
                    // }
                    // if (!empty($locstr)) {
                    //     $where .= ' fileuser.city_id IN (' . trim($locstr, ',') . ') AND ';
                    // }
                    $where .= ' fileuser.city_id = "'.$_POST['location'].'" AND ';
                }
                
                if($custom_connect == 2){
                     $where .= ' fileuser.reference = "connect" AND ';
                }
                
                if(!empty($_POST['min_age']) && !empty($_POST['max_age'])){
                    $where .= ' ( fileuser.age between '.$_POST['min_age'].' and '.$_POST['max_age'].') AND ';
                }else if(!empty($_POST['min_age'])){
                    $where .= ' fileuser.age <= ( '.$_POST['min_age'].' ) AND fileuser.age != 0 AND';
                }else if(!empty($_POST['max_age'])){
                    $where .= ' fileuser.age >= ( '.$_POST['max_age'].' ) AND fileuser.age != 0 AND';
                }
                // $userDataQuery = "SELECT * FROM `fileuser` where is_active = 1  AND ".trim($where,'AND ')." ORDER BY id";
                $userDataQuery = "SELECT * FROM `fileuser` where 1 = 1 AND ".trim($where,'AND ')." ";
                if($sendAgain){
                    $userDataQuery .= " AND mobile not in (select mobile from queue_data where sent_status = 0)";
                }
                $userDataQuery .= " ORDER BY id";
                if (isset($limit) && $limit > 0) {
                    $userDataQuery .= " LIMIT $limit";
                }
                $filedata = $con->query($userDataQuery);
                
                $insqueryData = "INSERT into send_data (sent_option,sender,template,hyperlink,message,fileID,sent_time,city_id,age,start_date,end_date,send_limit,send_again) values ('".$_POST['sent_option']."','".$sender[0]."','".$template."','".$hyperlink."','".$_POST['message']."',0,'".$time."','".$location."','".$age."','".$startDate."','".$endDate."',$limit,$sendAgain)";
                // echo $insqueryData;die;
                mysqli_query($con,$insqueryData);
                
                $lastInsertId = mysqli_insert_id($con);
                
                while ($row1 = $filedata->fetch_assoc()) {
                    $id = $row1['id'];
                    $campaign = $row1['campaign'];
                    $mobile = $row1['mobile'];
                    $name = $row1['name'];
                    
                    $insQueData = "INSERT into queue_data (sent_option,sender,template,hyperlink,message,mobile,sent_status,name,sent_time,send_id) values (".$_POST['sent_option'].",'".$sender[0]."','".$template."','".$hyperlink."','".$_POST['message']."','".$mobile."',0,'".$name."','".$time."',$lastInsertId)";
                    mysqli_query($con,$insQueData);
                    
                    if($sendAgain){
                        if ($campaign !== $camp) {
                            $updateQuery = "UPDATE `fileuser` SET campaign = '$camp', sent_status = 0 WHERE id = $id ";
                            $con->query($updateQuery);
                        }
                    }else{
                        $updateQuery = "UPDATE `fileuser` SET campaign = '$camp',sent_status = 0 WHERE id = $id ";
                        $con->query($updateQuery);
                    }
                }
                
                $_SESSION['sucess'] = 'Send Sucessfully';
                
            }
            header("Location: http://localhost/send/SMSsend.php");
            
        }else{
            for($i=0;$i<count($_POST['filename']);$i++){
                $time = date('Y-m-d h:i:s');
                if(!empty($_POST['sent_time'])){
                   $time = date('Y-m-d H:i:s',strtotime($_POST['sent_time'])); 
                }
                
                // echo "<PRE>";print_R($_POST);die;
                
                $insqueryData = "INSERT into send_data (sent_option,sender,template,hyperlink,message,fileID,sent_time) values ('".$_POST['sent_option']."','".$sender[0]."','".$template."','".$hyperlink."','".$_POST['message']."','".$_POST['filename'][$i]."','".$time."')";
                //echo $insqueryData;die;
                mysqli_query($con,$insqueryData);
                $lastInsertId = mysqli_insert_id($con);
                
                $updateQuery = "UPDATE filedata set sent = 1 where id = ".$_POST['filename'][$i];
                
                mysqli_query($con,$updateQuery);
                
                //update for filedata to set campaign value
                
                $filename = mysqli_real_escape_string($con, $_POST['filename'][$i]);
                $selectQuery = "SELECT campaign FROM filedata WHERE id = $filename";
                $result = mysqli_query($con, $selectQuery);
                $row = mysqli_fetch_assoc($result);
                $currentCampaign = $row['campaign'];
                $newCampaign = $currentCampaign ? $currentCampaign . ', ' . $camp : $camp;
                $updateCamp = "UPDATE filedata SET campaign = '$newCampaign' WHERE id = $filename";
                mysqli_query($con, $updateCamp);
                
                
                $userDataQuery = "SELECT * FROM `fileuser` where 1 = 1  AND fileid = '".$_POST['filename'][$i]."' ";
                if($sendAgain){
                    $userDataQuery .= " AND mobile not in (select mobile from queue_data where sent_status = 0)";
                }
                $userDataQuery .= " ORDER BY id";
                
                $filedata = $con->query($userDataQuery);
                if($filedata->num_rows > 0){
                    while ($row1 = $filedata->fetch_assoc()) {
                        $id = $row1['id'];
                        $campaign = $row1['campaign'];
                        $mobile = $row1['mobile'];
                        $name = $row1['name'];
                        
                        $insQueData = "INSERT into queue_data (sent_option,sender,template,hyperlink,message,mobile,sent_status,name,sent_time,send_id) values (".$_POST['sent_option'].",'".$sender[0]."','".$template."','".$hyperlink."','".$_POST['message']."','".$mobile."',0,'".$name."','".$time."',$lastInsertId)";
                        mysqli_query($con,$insQueData);
                        
                        if($sendAgain){
                            if ($campaign !== $camp) {
                                $updateQuery = "UPDATE `fileuser` SET campaign = '$camp', sent_status = 0 WHERE id = $id ";
                                $con->query($updateQuery);
                            }
                        }else{
                            $updateQuery = "UPDATE `fileuser` SET campaign = '$camp',sent_status = 0 WHERE id = $id ";
                            $con->query($updateQuery);
                        }
                    }  
                }
                
            }
            
            $_SESSION['sucess'] = 'Send Sucessfully';

            header("Location: http://localhost/send/SMSsend.php");
        }
        
        
        
    }
    
}
 
function parseSalary($salary) {
    $salary = str_replace(["Rs", " "], "", $salary);
    preg_match('/(\d+(\.\d+)?)/', $salary, $matches);
    $numericPart = $matches[1];
    if (stripos($salary, "Lakhs") !== false) {
        return floatval($numericPart) * 100000;
    } elseif (stripos($salary, "Crores") !== false) {
        return floatval($numericPart) * 10000000;
    } elseif (stripos($salary, "Thousand") !== false) {
        return floatval($numericPart) * 1000;
    } else {
        return floatval($numericPart);
    }
}
?>

