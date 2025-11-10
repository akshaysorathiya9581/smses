<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$con = new mysqli("localhost", "smses_senduser", "user007", "smses_send");
date_default_timezone_set('Asia/Kolkata');
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}
$current_time = time();//strtotime(date('Y-m-d H:i:s'));
//echo $current_time;

$getsql = "SELECT * FROM `queue_data` WHERE sent_status = 0";
$getsenData = $con->query($getsql);
//echo "<PRE>";print_R($getsenData);die;
if ($getsenData->num_rows > 0) {
    while ($row1 = $getsenData->fetch_assoc()) {
        //echo "<PRE>";print_R($row1);die;
        $sender = $row1['sender'];
        $template = $row1['template'];
        $hyperlink = $row1['hyperlink'];
        $message = $row1['message'];
        //$fileid = $row1['fileID'];
        
        $time = strtotime($row1['sent_time']);
        //echo '<br/>'.$time.'<br/>';
        // Fetch template details from sender-template table
        $templateQuery = "SELECT * FROM `sender-template` WHERE sender_id = '$sender'";
        $templateResult = $con->query($templateQuery);
        $templateRow = $templateResult->fetch_assoc();
        @$templatetext = $templateRow['template'];
        @$templateid = $templateRow['template_id'];
        //echo "<PRE>";print_R($row1);die;
        $sent = 0;
        if($row1['sent_option'] == 0){
            $sent = 1;
        }else{
            if($time <= $current_time){
                $sent = 1;
            }
        }
        
        
        $url = '';
        //if($current_time < $time){
        if($sent == 1){
            
            $updatequery = "UPDATE queue_data SET sent_status = 1 WHERE id = ".$row1['id']; //change
            $con->query($updatequery);
            
            $updatequery = "UPDATE send_data SET sent_status = 1 WHERE id = ".$row1['send_id']; //change
            $con->query($updatequery);
            
            // if($row1['fileID'] == 0){
                
                
            //     $userDataQuery = "SELECT * FROM `fileuser` where 1 and sent_status = 0 and ".trim($where,'AND ').$limit_val;
            //     //echo $userDataQuery;die;
            //     $filedata = $con->query($userDataQuery);
                
            //     $updatequery = "UPDATE send_data SET sent_status = 1 WHERE id = ".$row1['id'];
            //     $con->query($updatequery);
                
            //     $updatequery = "UPDATE senddata SET sent_status = 1 WHERE id = ".$row1['id'];
            //     $con->query($updatequery);
            // }else{
            //     //echo "esleeee";die;
            //     // Fetch user data
            //     $userDataQuery = "SELECT * FROM `fileuser` where fileid = ".$fileid;
            //     $filedata = $con->query($userDataQuery);
        
            //     $updatequery = "UPDATE send_data SET sent_status = 1 WHERE fileid = $fileid AND sender = '$sender' AND hyperlink = '$hyperlink'";
            //     $con->query($updatequery);
                
            //     $updatequery = "UPDATE senddata SET sent_status = 1 WHERE id = ".$row1['id'];
            //     $con->query($updatequery);
                
            // }
            
    
            //while ($row = $filedata->fetch_assoc()) {
                $sendmobile = $row1['mobile'];
                $name = $row1['name'];
                
                // Get template text, if not available use message directly
                if (isset($templateRow['template']) && !empty($templateRow['template'])) {
                    $templatetext = $templateRow['template'];
                } else {
                    // If no template found, use the message as template
                    $templatetext = $message;
                }
                
                // Replace placeholders in the template text with variables
                // str_replace will simply not replace if placeholder is not found
                $templatetext = str_replace('#VAR1#', urlencode(trim($name)), $templatetext);
                $templatetext = str_replace('#VAR2#', urlencode(trim($message)), $templatetext);
                $templatetext = str_replace('#VAR3#', urlencode(trim($hyperlink)), $templatetext);
                
                // Encode spaces as %20
                $templatetext = str_replace(' ', '%20', $templatetext);
                
                //$sendmobile = 7048401307;
                
                // Construct the URL with replaced variables
                $url = 'https://test1bulksms.mytoday.com/BulkSms/SingleMsgApi';
                $url .= '?feedid=393258';
                $url .= '&username=9884196886';
                $url .= '&password=SuX@2egALigzEKZ';
                $url .= '&To=' . $sendmobile;
                //$url .= '&Text=' . urlencode($templatetext ?? '');
                $url .= '&Text=' . $templatetext;
                $url .= '&templateid=' .$templateid;
                $url .= '&entityid=1201160765852941646';
                $url .= '&senderid=' .$sender;
                
                // Send the request
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_POSTFIELDS => "{}",
                ));
                $response = curl_exec($curl);
                
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);
    
                // Log response
                $insqueryData = "INSERT INTO msm_response (mobile, response) VALUES ('$sendmobile', '" . $con->real_escape_string($response) . "')";
                $con->query($insqueryData);
                
                
    
                // Sleep for API rate limiting
                usleep(200); // Microseconds
            //}
            
            echo "Url:" .$url;
            
        }else{
            echo "NO Record Found!!";
        }

       
    }
    
} else {
    echo "NO Record Found!!";
}

$con->close();
?>

