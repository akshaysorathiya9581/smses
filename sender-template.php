<?php
session_start();
require('sessioncheck.php');

// Database connection
$con = mysqli_connect("localhost", "root", "", "smses_send");

// Check if form is submitted for adding or editing template
//echo "<PRE>";print_R($_POST);die;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // If "edit" button is clicked
    if(isset($_POST['edit'])) {
        $id = mysqli_real_escape_string($con, $_POST['id']);
        $senderID = mysqli_real_escape_string($con, $_POST['senderID']);
        $templateID = mysqli_real_escape_string($con, $_POST['templateID']);
        $template = mysqli_real_escape_string($con, $_POST['template']);

        // Validate Sender ID (should be exactly six characters long)
        if (strlen($senderID) !== 6) {
            $_SESSION['error'] = "Sender ID should be exactly six characters long.";
            header("Location: sender-template.php");
            exit;
        }

        // Validate Template ID (should contain only digits)
        if (!ctype_digit($templateID)) {
            $_SESSION['error'] = "Template ID should contain only digits.";
            header("Location: sender-template.php");
            exit;
        }

        // Update template
        $update_query = "UPDATE `sender-template` SET sender_id='$senderID', template_id='$templateID', template='$template' WHERE id='$id'";
        if (mysqli_query($con, $update_query)) {
            $_SESSION['success'] = "Template updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating template: " . mysqli_error($con);
        }
        header("Location: sender-template.php");
        exit;
    }else{
        $senderID = mysqli_real_escape_string($con, $_POST['senderID']);
        $templateID = mysqli_real_escape_string($con, $_POST['templateID']);
        $template = mysqli_real_escape_string($con, $_POST['template']);

        // Validate Sender ID (should be exactly six characters long)
        if (strlen($senderID) !== 6) {
            $_SESSION['error'] = "Sender ID should be exactly six characters long.";
            header("Location: sender-template.php");
            exit;
        }

        // Validate Template ID (should contain only digits)
        if (!ctype_digit($templateID)) {
            $_SESSION['error'] = "Template ID should contain only digits.";
            header("Location: sender-template.php");
            exit;
        }

        // Update template
        $update_query = "INSERT into  `sender-template` (sender_id,template_id,template) VALUES ('$senderID','$templateID','$template')";
        //echo $update_query;die;
        if (mysqli_query($con, $update_query)) {
            $_SESSION['success'] = "Template insert successfully!";
        } else {
            $_SESSION['error'] = "Error updating template: " . mysqli_error($con);
        }
        header("Location: sender-template.php");
        exit;
    }
}

// Handle Delete Operation
if(isset($_GET['delete'])) {
    $delete_id = mysqli_real_escape_string($con, $_GET['delete']);
    
    // Delete query
    $delete_query = "DELETE FROM `sender-template` WHERE `id` = $delete_id";
    
    // Perform deletion
    if(mysqli_query($con, $delete_query)) {
        $_SESSION['success'] = "Template deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting template: " . mysqli_error($con);
    }
    header("Location: sender-template.php");
    exit;
}


// Fetch all available templates
$templateQuery = "SELECT * FROM `sender-template`";
$templateResult = mysqli_query($con, $templateQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Sender Template</title>
    <!-- Include Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h2 class="my-4">Add Sender Template</h2>
        <!-- Navigation links -->
        <a class="mb-3 btn btn-primary" href="http://localhost/send/EditSMS.php">Edit SMS</a>&nbsp;&nbsp;
        <a class="mb-3 btn btn-primary" href="http://localhost/send/DeleteFileUser.php">Delete Fileuser</a>&nbsp;&nbsp;
        <a class="mb-3 btn btn-primary" href="http://localhost/send/">File Upload</a>&nbsp;&nbsp;
        <a class="mb-3 btn btn-primary" href="http://localhost/send/sender-template.php">Add Sender Template</a>&nbsp;&nbsp;
        <a class="mb-3 btn btn-primary" href="http://localhost/send/reports.php">Reports</a>&nbsp;&nbsp;
        <a class="mb-3 btn btn-primary" href="https://www.smses.in/ct/login.php">Click Track</a>
        &nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/logs.php">Logs across Pages</a>
        &nbsp;&nbsp;<a class="mb-3 btn btn-primary" href="http://localhost/send/import_connect_users.php">Import from Connect DB</a>
        <!-- Display success or error messages -->
        <?php if(isset($_SESSION['success'])) { ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php } ?>
        <?php if(isset($_SESSION['error'])) { ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php } ?>
        <!-- Form to add new template -->
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="senderID">Sender ID:</label>
                <input type="text" class="form-control" id="senderID" name="senderID" required>
            </div>
            <div class="form-group">
                <label for="templateID">Template ID:</label>
                <input type="text" class="form-control" id="templateID" name="templateID" required>
            </div>
            <div class="form-group">
                <label for="template">Template:</label>
                <!-- Here, you can use #VAR1#, #VAR2#, and #VAR3# as placeholders for dynamic content -->
                <textarea class="form-control" id="template" name="template" rows="5" required><?php echo isset($_POST['template']) ? $_POST['template'] : 'Dear #VAR1# Your eligibility for #VAR2# is ACCEPTED based on your resume. For more details and to apply click here #VAR3# - TechSlash'; ?></textarea>
            </div>
            <!-- Add Template Button -->
            <button type="submit" class="btn btn-primary">Add Template</button>
        </form>
<!-- Display Available Templates -->
        <div class="mt-4">
            <h3>Available Templates:</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Sender ID</th>
                        <th>Template ID</th>
                        <th>Template</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($templateResult)) { ?>
                        <tr>
                            <td><?php echo $row['sender_id']; ?></td>
                            <td><?php echo $row['template_id']; ?></td>
                            <td><?php echo $row['template']; ?></td>
                            <td>
                                <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?delete=' . $row['id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

<?php mysqli_close($con);
