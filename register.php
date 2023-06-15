<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

function msg($success,$status,$message,$extra = []){
    return array_merge([
        'success' => $success,
        'status' => $status,
        'message' => $message
    ],$extra);
}

// INCLUDING DATABASE AND MAKING OBJECT
require __DIR__.'/classes/Database.php';
$db_connection = new Database();
$conn = $db_connection->dbConnection();

// GET DATA FORM REQUEST
$data = json_decode(file_get_contents("php://input"));
$returnData = [];

// IF REQUEST METHOD IS NOT POST
if($_SERVER["REQUEST_METHOD"] != "POST"):
    $returnData = msg(0,404,'Page Not Found!');

// CHECKING EMPTY FIELDS
elseif(!isset($data->first_name) 
    || !isset($data->last_name) 
    || !isset($data->company_name)
    || empty(trim($data->email))
    || empty(trim($data->phone))
    || empty(trim($data->password))
    || empty(trim($data->retype_password))
    ):

    $fields = ['fields' => ['First Name', 'Last Name', 'Company Name', 'Email', 'Phone', 'Password']];
    $returnData = msg(0,422,'Please Fill in all Required Fields! ',$fields);

// IF THERE ARE NO EMPTY FIELDS THEN-
else:
    
    $first_name = $data->first_name;
    $last_name = $data->last_name;
    $user_company_name = $data->company_name;
    $email = $data->email;
    $user_phone = $data->phone;
    $password = trim($data->password);
    $retype_password = trim($data->retype_password);
    $user_role = 'Member';

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)):
        $returnData = msg(0,422,'Invalid Email Address!');
    
    elseif($password!=$retype_password):
        $returnData = msg(0,422,'Confirmed password does not match');

    elseif(strlen($password) < 8):
        $returnData = msg(0,422,'Your password must be at least 8 characters long!');

    else:
        try{

            $check_email = "SELECT `user_email` FROM `user` WHERE `user_email`=:email";
            $check_email_stmt = $conn->prepare($check_email);
            $check_email_stmt->bindValue(':email', $email,PDO::PARAM_STR);
            $check_email_stmt->execute();

            if($check_email_stmt->rowCount()):
                $returnData = msg(0,422, 'This E-mail already in use!');
            
            else:
                
                $insert_query = "INSERT INTO `user`(`user_first_name`,`user_last_name`,`user_company_name`, `user_email`,`user_password`,`user_phone`, `user_role`) 
                VALUES(:first_name,:last_name,:user_company_name, :email, :password, :user_phone, :role)";

                $insert_stmt = $conn->prepare($insert_query);

                // DATA BINDING
                // $insert_stmt->bindValue(':name', htmlspecialchars(strip_tags($name)),PDO::PARAM_STR);
                $insert_stmt->bindValue(':first_name', $first_name,PDO::PARAM_STR);
                $insert_stmt->bindValue(':last_name', $last_name,PDO::PARAM_STR);
                $insert_stmt->bindValue(':user_company_name', $user_company_name,PDO::PARAM_STR);
                $insert_stmt->bindValue(':email', $email,PDO::PARAM_STR);
                $insert_stmt->bindValue(':password', sha1($password),PDO::PARAM_STR);
                $insert_stmt->bindValue(':user_phone', $user_phone,PDO::PARAM_STR);
                $insert_stmt->bindValue(':role', $user_role,PDO::PARAM_STR);

                $insert_stmt->execute();

                $returnData = msg(1,201,'You have successfully registered.');

            endif;

        }
        catch(PDOException $e){
            $returnData = msg(0,500,$e->getMessage());
        }
    endif;
    
endif;

echo json_encode($returnData);