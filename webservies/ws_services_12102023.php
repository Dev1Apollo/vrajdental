<?php
ob_start();
header('Content-Type: application/json');
include_once '../common.php';

$connect = new connect();

include_once 'password_hash.php';
//require '../PHPMailer-master/PHPMailerAutoload.php';
//include_once '../FCM.php';
//$fcm = new Firebase();
//include('json.php');
//$json = new Services_JSON();
$actions = isset($_REQUEST['action']) ? strtolower(trim($_REQUEST['action'])) : '';
extract($_REQUEST);

$output = array();

if ($actions == 'adminlogin') {
    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt,type,contactPerson,deviceToken from user where mobile = '$obj->MobileNo' and isDelete=0";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

         

                $data = array(

                    "deviceToken" => $obj->deviceToken,

                );

                // if ($row['deviceToken'] != $obj->deviceToken) {
                //     $output['contactPerson'] =  $row['contactPerson'];
                //     $output['type'] =  $row['type'];
                //     $output['message'] = 'Already Login This Mobile No In Other Device';
                //     $output['success'] = '1';
                // } else {
                //     $output['message'] = 'DeviceId Not Matched';
                //     $output["success"] = '0';
                // }

                $where = " where userId = " . $row['userId'];
                $dealer = $connect->updaterecord($dbconn, 'user', $data, $where);

                $output['contactPerson'] = $row['contactPerson'];
                $output['type'] = $row['type'];
                $output['message'] = 'Login Successfully Done';
                $output['success'] = '1';

           

        } else {
            $output['message'] = 'Password not match';
            $output["success"] = '0';
        }

    } else {

        $output['message'] = 'User or Password not match';
        $output['success'] = '0';
    }
} else if ($actions == 'addproduct') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'  and type='1'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {
            $data = array(
                "productName" => $obj->productName,
                "lpr" => $obj->lpr,
                "cr" => 0,
                "dr" => 0,
                "minStockQty" => $obj->minStockQty,
                "openingStock" => $obj->openingStock,
                "closing" => 0,
                "strEntryDate" => date('d-m-Y H:i:s'),
                "strIP" => $_SERVER['REMOTE_ADDR'],

            );
            $dealer = $connect->insertrecord($dbconn, 'product', $data);

            $output['message'] = 'Added Successfully !';
            $output['success'] = '1';
        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == 'updateproduct') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo' and type='1'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $product = "select productId,productName,lpr,openingStock from product where istatus= '1' and isDelete= '0'";
            $product = mysqli_fetch_assoc(mysqli_query($dbconn, $product));

            $data = array(
                "productName" => $obj->productName,
                "lpr" => $obj->lpr,
                "cr" => 0,
                "dr" => 0,
                "minStockQty" => $obj->minStockQty,
                "openingStock" => $obj->openingStock,
                "closing" => 0,
                "strEntryDate" => date('d-m-Y H:i:s'),
                "strIP" => $_SERVER['REMOTE_ADDR'],

            );
            $where = " where productId = " . $obj->productId;
            $dealer = $connect->updaterecord($dbconn, 'product', $data, $where);

            $output['message'] = 'Update Successfully !';
            $output['success'] = '1';
        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == "listofproduct") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt,type from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);

        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $where = " where 1=1 ";

            if (isset($obj->productName) && $obj->productName != '0' && $obj->productName != '') {
                $where .= " and productName like '%" . $obj->productName . "%'";
            }
            $arrayVal = array('productId' => '', 'productName' => '', 'lpr' => '', 'openingStock' => '', 'minStockQty' => '');

            $product = "SELECT `productId`,`productName`,`lpr`,`openingStock`,minStockQty,closing as CurrentStock FROM `product` " . $where . " and isDelete= '0' and istatus= '1' order by productName asc";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {

                if ($row['type'] == "2" || $row['type'] == "3") {
                    $output['ProductList'][] = array('productId' => '0', 'productName' => 'Select Product', 'lpr' => '0', 'openingStock' => '0', 'minStockQty' => '');
                }
                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $output['ProductList'][] = $rowproduct;
                }

                $output['message'] = '';
                $output['success'] = '1';
            } else {
                // $output['ProductList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            //  $output['ProductList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == 'addvender') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo' and type='1'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {
            $data = array(
                "venderName" => $obj->venderName,
                "mobile" => $obj->mobile,
                "contactPerson" => $obj->contactPerson,
                "strEntryDate" => date('d-m-Y H:i:s'),
                "strIP" => $_SERVER['REMOTE_ADDR'],

            );
            $dealer_res = $connect->insertrecord($dbconn, 'vender', $data);
            $output['message'] = 'Added Successfully !';
            $output['success'] = '1';
        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == 'updatevender') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo' and type='1'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $vender = "select venderId,venderName,mobile,contactPerson from vender where venderId = '" . $obj->venderId . "' and  istatus= '1' and isDelete= '0'";
            $vender = mysqli_fetch_assoc(mysqli_query($dbconn, $vender));

            $data = array(
                "venderName" => $obj->venderName,
                "mobile" => $obj->mobile,
                "contactPerson" => $obj->contactPerson,
                "strEntryDate" => date('d-m-Y H:i:s'),
                "strIP" => $_SERVER['REMOTE_ADDR'],

            );
            $where = " where venderId = " . $vender['venderId'];
            $dealer = $connect->updaterecord($dbconn, 'vender', $data, $where);

            $output['message'] = 'Update Successfully !';
            $output['success'] = '1';
        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == "listofvender") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt,type from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {
            $arrayVal = array('venderId' => '', 'venderName' => '', 'mobile' => '', 'contactPerson' => '');
            $product = "SELECT `venderId`,`venderName`,`mobile`,`contactPerson` FROM `vender` where isDelete= '0' and istatus= '1'";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {

                if ($row['type'] == 1) {
                    $output['VenderList'][] = array('venderId' => '0', 'venderName' => 'Select Vender', 'mobile' => '', 'contactPerson' => '');
                    while ($rowproduct = mysqli_fetch_assoc($product)) {
                        $output['VenderList'][] = $rowproduct;
                    }
                } else {
                    $output['VenderList'][] = array('venderId' => '0', 'venderName' => 'Select Vender', 'mobile' => '', 'contactPerson' => '');
                    while ($rowproduct = mysqli_fetch_assoc($product)) {
                        $output['VenderList'][] = $rowproduct;
                    }
                }
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                //  $output['VenderList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['VenderList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == 'createcentraladmin') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo' and type='1'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {
            $hash_result = create_hash($obj->password);
            $hash_params = explode(":", $hash_result);
            $salt = $hash_params[HASH_SALT_INDEX];
            $hash = $hash_params[HASH_PBKDF2_INDEX];

            $data = array(
                //"cleaningName" => $obj->cleaningName,
                "contactPerson" => $obj->cleaningName,
                "mobile" => $obj->mobile,
                "password" => $hash,
                "type" => 2,
                "salt" => $salt,
                "strEntryDate" => date('d-m-Y H:i:s'),
                "strIP" => $_SERVER['REMOTE_ADDR'],

            );
            $dealer_res = $connect->insertrecord($dbconn, 'user', $data);
            $output['message'] = 'Added Successfully !';
            $output['success'] = '1';
        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == 'updatcentraladmin') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo' and type='1'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $data = array(
                "contactPerson" => $obj->cleaningName,
                "mobile" => $obj->mobile,
                "type" => 2,
                "strEntryDate" => date('d-m-Y H:i:s'),
                "strIP" => $_SERVER['REMOTE_ADDR'],

            );
            $where = " where userId = " . $obj->userId;
            $dealer = $connect->updaterecord($dbconn, 'user', $data, $where);

            $output['message'] = 'Update Successfully !';
            $output['success'] = '1';
        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == "listofcentraladmin") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $where = " where 1=1 ";

            if (isset($obj->cleaningName) && $obj->cleaningName != '0' && $obj->cleaningName != '') {
                $where .= " and contactPerson like '%" . $obj->cleaningName . "%'";
            }

            $product = "SELECT `userId`,`type`,contactPerson as `cleaningName`,`mobile` FROM `user` " . $where . " and type ='2' and isDelete= '0' and istatus= '1'";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {
                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $output['CentralAdminList'][] = $rowproduct;
                }
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                // $output['CentralAdminList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['CentralAdminList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == 'createcleaning') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo' and type='1'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {
            $hash_result = create_hash($obj->password);
            $hash_params = explode(":", $hash_result);
            $salt = $hash_params[HASH_SALT_INDEX];
            $hash = $hash_params[HASH_PBKDF2_INDEX];
            $data = array(
                "cleaningName" => $obj->cleaningName,
                "contactPerson" => $obj->contactPerson,
                "mobile" => $obj->mobile,
                "password" => $hash,
                "type" => 3,
                "salt" => $salt,
                "strEntryDate" => date('d-m-Y H:i:s'),
                "strIP" => $_SERVER['REMOTE_ADDR'],

            );
            $dealer_res = $connect->insertrecord($dbconn, 'user', $data);
            $output['message'] = 'Added Successfully !';
            $output['success'] = '1';
        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == 'updatecleaning') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo' and type='1'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $data = array(
                "cleaningName" => $obj->cleaningName,
                "contactPerson" => $obj->contactPerson,
                "mobile" => $obj->mobile,
                "type" => 3,
                "strEntryDate" => date('d-m-Y H:i:s'),
                "strIP" => $_SERVER['REMOTE_ADDR'],

            );
            $where = " where userId = " . $obj->userId;
            $dealer = $connect->updaterecord($dbconn, 'user', $data, $where);

            $output['message'] = 'Update Successfully !';
            $output['success'] = '1';
        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == "listofcleaning") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt,type from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $where = " where 1=1 ";

            if (isset($obj->cleaningName) && ($obj->cleaningName != '0')) {
                $where .= " and cleaningName like '%" . $obj->cleaningName . "%'";
            }

            $arrayVal = array('userId' => '', 'cleaningName' => '', 'mobile' => '', 'contactPerson' => '');
            $product = "SELECT `userId`,`cleaningName`,`mobile`,`contactPerson` FROM `user` " . $where . " and type ='3' and isDelete= '0' and istatus= '1'";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {

                if ($row['type'] == "2" || $row['type'] == "3") {
                    $output['CentralAdminList'][] = array('userId' => '0', 'cleaningName' => 'Select Clinic', 'mobile' => '', 'contactPerson' => '');
                }
                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $output['CentralAdminList'][] = $rowproduct;
                }

                $output['message'] = '';
                $output['success'] = '1';
            } else {
                // $output['CentralAdminList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['CentralAdminList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == 'changepassword') {
    file_put_contents('changepassword.txt',file_get_contents('php://input'));
    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $hash_result = create_hash($obj->NewPassword);
            $hash_params = explode(":", $hash_result);
            $salt = $hash_params[HASH_SALT_INDEX];
            $hash = $hash_params[HASH_PBKDF2_INDEX];
            $data = array(

                "password" => $hash,
                "salt" => $salt,
                "strEntryDate" => date('d-m-Y H:i:s'),
                "strIP" => $_SERVER['REMOTE_ADDR'],

            );
            $where = " where userId = " . $obj->userId;
            $dealer = $connect->updaterecord($dbconn, 'user', $data, $where);

            $output['message'] = 'Update Successfully !';
            $output['success'] = '1';
        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == 'pregeneratepurchaseorder') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $data = array(
                "poStatus" => -1,
                "enterBy" => $row['userId'],
                "strEntryDate" => date('d-m-Y H:i:s'),
                "strIP" => $_SERVER['REMOTE_ADDR'],

            );
            $dealer_res = $connect->insertrecord($dbconn, 'purchaseorder', $data);
            $output['purchaseOrderId'] = $dealer_res;
            $output['message'] = 'Added Successfully !';
            $output['success'] = '1';
        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == 'updatcreatepurchaseorder') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $data = array(
                "venderId" => $obj->venderId,
                "date" => $obj->date,
                "pono" => $obj->pono,
                "poStatus" => 0,
                "strEntryDate" => date('d-m-Y H:i:s'),
                "strIP" => $_SERVER['REMOTE_ADDR'],

            );
            $where = " where purchaseOrderId = " . $obj->purchaseOrderId;
            $dealer = $connect->updaterecord($dbconn, 'purchaseorder', $data, $where);

            $output['message'] = 'Created Successfully !';
            $output['success'] = '1';
        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == "listofpurchaseorder") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $product = "SELECT purchaseorder.purchaseOrderId,purchaseorder.date,purchaseorder.pono,(select venderName from vender where vender.venderId=purchaseorder.venderId) as `venderName` FROM `purchaseorder`where  purchaseorder.poStatus='0' and purchaseorder.isDelete= '0' and purchaseorder.istatus= '1'";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {
                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $output['PurchaseOrderList'][] = $rowproduct;
                }
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                //    $output['PurchaseOrderList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['PurchaseOrderList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == 'purchaseorderapplyverification') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $data = array(

                "enterBy" => $row['userId'],
                "poStatus" => 1,
                "strEntryDate" => date('d-m-Y H:i:s'),
                "strIP" => $_SERVER['REMOTE_ADDR'],

            );
            $where = " where purchaseOrderId = " . $obj->purchaseOrderId;
            $dealer = $connect->updaterecord($dbconn, 'purchaseorder', $data, $where);

            $output['message'] = 'Apply Successfully !';
            $output['success'] = '1';
        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == 'addproductpurchaseorderdetails') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $data = array(
                "purchaseOrderId" => $obj->purchaseOrderId,
                "productId" => $obj->productId,
                "qty" => $obj->qty,
                "lpr" => $obj->lpr,
                "amount" => $obj->qty * $obj->lpr,
                "strEntryDate" => date('d-m-Y H:i:s'),
                "strIP" => $_SERVER['REMOTE_ADDR'],

            );
            $dealer_res = $connect->insertrecord($dbconn, 'purchaseorderdetail', $data);
            $output['message'] = 'Added Successfully !';
            $output['success'] = '1';
        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == "deleteproductpurchaseorderdetails") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $where = ' where purchaseOrderDetailsId=' . $obj->purchaseOrderDetailsId;
            $dealer = $connect->deleterecord($dbconn, 'purchaseorderdetail', $where);

            $output['message'] = 'delete successfully !';
            $output['success'] = '1';

        } else {

            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == "listofadminpurchaseorder") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $product = "SELECT purchaseOrderId,date,pono,(select venderName from vender where vender.venderId=purchaseorder.venderId) as `venderName` FROM `purchaseorder` where  poStatus='1' and isDelete= '0' and istatus= '1'";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {
                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $output['AdminPurchaseOrderList'][] = $rowproduct;
                }
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                //    $output['AdminPurchaseOrderList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['AdminPurchaseOrderList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == "listofpurchaseorderdetails") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {
            $where = " and purchaseOrderId = " . $obj->purchaseOrderId;
            $product = "SELECT qty,(select product.lpr from product where product.productId = purchaseorderdetail.productId) as lpr,amount,purchaseOrderDetailsId,productId,(select orderdetails.receivedQty from orderdetails where orderdetails.purchaseOrderDetailsId = purchaseorderdetail.purchaseOrderDetailsId) as receivedQty,(select productName from product where product.productId=purchaseorderdetail.productId) as `productName` FROM `purchaseorderdetail` where  isDelete= '0' and istatus= '1' " . $where . "";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {
                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $output['PurchaseOrderList'][] = $rowproduct;
                }
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                //    $output['PurchaseOrderList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['PurchaseOrderList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == 'approvedpurchaseorder') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $data = array(

                "approvedBy" => $row['userId'],
                "approvedDate" => date('d-m-Y H:i:s'),
                "poStatus" => 2,
                "strIP" => $_SERVER['REMOTE_ADDR'],

            );
            $where = " where purchaseOrderId = " . $obj->purchaseOrderId;
            $dealer = $connect->updaterecord($dbconn, 'purchaseorder', $data, $where);

            $output['message'] = 'Update Successfully !';
            $output['success'] = '1';
        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == "listofapprovedpurchaseorder") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $product = "SELECT date,pono,purchaseOrderId,venderId,(select venderName from vender where vender.venderId=purchaseorder.venderId) as `venderName` FROM `purchaseorder` where  poStatus='2' and isDelete= '0' and istatus= '1'";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {
                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $output['purchaseOrderId'] = $rowproduct['purchaseOrderId'];
                    $output['PurchaseOrderList'][] = $rowproduct;
                }
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                //    $output['PurchaseOrderList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['PurchaseOrderList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == 'submitorder') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $poData = "select * from purchaseorder where purchaseOrderId = " . $obj->purchaseOrderId . "";
            $podataRow = mysqli_fetch_assoc(mysqli_query($dbconn, $poData));

            $data = array(
                "purchaseOrderId" => $obj->purchaseOrderId,
                "invoiceNo" => $obj->invoiceNo,
                "invoiceDate" => $obj->invoiceDate,
                "venderId" => $podataRow['venderId'],
                "strEntryDate" => date('d-m-Y H:i:s'),
                "strIP" => $_SERVER['REMOTE_ADDR'],
            );
            $orderID = $connect->insertrecord($dbconn, 'ordermaster', $data);

            for ($i = 0; $i < sizeof($obj->orderDetail); $i++) {
                if ($obj->orderDetail[$i]->purchaseOrderDetailsId > 0) {
                    //query to get product from produc table using product id
                    // select * from product where productId = $obj->orderDetail[$i]->productId
                    $product = "select productId,lpr,cr,openingStock from product where productId= " . $obj->orderDetail[$i]->productId . "";
                    $productRow = mysqli_fetch_assoc(mysqli_query($dbconn, $product));

                    $fdata = array(
                        "orderId" => $orderID,
                        "productId" => $productRow['productId'], //db entry baki che
                        "orderQty" => $obj->orderDetail[$i]->orderQty,
                        "receivedQty" => $obj->orderDetail[$i]->receivedQty,
                        "lpr" => $obj->orderDetail[$i]->rate,
                        "rate" => $obj->orderDetail[$i]->rate,
                        "purchaseOrderDetailsId" => $obj->orderDetail[$i]->purchaseOrderDetailsId,
                        "amount" => $obj->orderDetail[$i]->amount,
                        "strEntryDate" => date('d-m-Y H:i:s'),
                        "strIP" => $_SERVER['REMOTE_ADDR'],

                    );
                    $details = $connect->insertrecord($dbconn, 'orderdetails', $fdata);
                    //ledger entry

                    $fadata = array(
                        "productId" => $productRow['productId'],
                        "crQty" => $obj->orderDetail[$i]->receivedQty,
                        "drQty" => 0,
                        "opening" => 0,
                        "closing" => 0,
                        "strEntryDate" => date('d-m-Y H:i:s'),
                        "strIP" => $_SERVER['REMOTE_ADDR'],

                    );
                    $dealer_res = $connect->insertrecord($dbconn, 'productledger', $fadata);
                    $cr = $obj->orderDetail[$i]->receivedQty + $productRow['cr'];
                    $Closing = $productRow['openingStock'] + $cr - $productRow['dr'];

                    $ProductArray = array(
                        "cr" => $cr,
                        "lpr" => $obj->orderDetail[$i]->rate,
                        "closing" => $Closing,
                    );
                    $productUpdate = " where productId = " . $productRow['productId'];
                    $dealer = $connect->updaterecord($dbconn, 'product', $ProductArray, $productUpdate);
                    //update query function calling
                    //code to purchase order set status : 3

                    $order = array(
                        "poStatus" => 3,

                    );
                    $Update = " where purchaseOrderId = " . $obj->purchaseOrderId;
                    $dealer = $connect->updaterecord($dbconn, 'purchaseorder', $order, $Update);
                }
            }

            $output['message'] = 'Added Successfully !';
            $output['success'] = '1';

        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == "purchasehistory") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $where = " and 1=1 ";

            if (isset($obj->FromDate) && ($obj->FromDate != '')) {

                $where .= " and STR_TO_DATE(strEntryDate,'%d-%m-%Y') >= STR_TO_DATE('" . $obj->FromDate . "','%d-%m-%Y')";
            }

            if (isset($obj->ToDate) && ($obj->ToDate != '')) {

                $where .= " and STR_TO_DATE(strEntryDate, '%d-%m-%Y')<=STR_TO_DATE('" . $obj->ToDate . "','%d-%m-%Y')";
            }

            if (isset($obj->VenderId) && $obj->VenderId != '0' && $obj->VenderId != '') {
                $where .= " and venderId = '" . $obj->VenderId . "'";
            }
            // data from order master
            $product = "select ordermaster.invoiceDate as date ,ordermaster.invoiceNo,
            (select sum(orderdetails.receivedQty * orderdetails.rate) from orderdetails where orderdetails.orderId = ordermaster.orderId) as Amount,
            (select purchaseorder.pono from purchaseorder where purchaseorder.purchaseOrderId = ordermaster.purchaseOrderId) as pono,
            (select purchaseorder.poStatus from purchaseorder where purchaseorder.purchaseOrderId = ordermaster.purchaseOrderId) as poStatus,
            (select vender.venderName from vender where vender.venderId=ordermaster.venderId) as venderName from ordermaster where   isDelete= '0' and istatus= '1' " . $where . "";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {
                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $poStatus = "";
                    if($rowproduct['poStatus'] == 4) {
                        $poStatus = "Paid";
                    } else {
                        $poStatus = "Unpaid";
                    }
                    $data = array(
                        "date" => $rowproduct['date'],
                        "invoiceNo" => $rowproduct['invoiceNo'],
                        "Amount" => $rowproduct['Amount'],
                        "pono" => $rowproduct['pono'],
                        "venderName" => $rowproduct['venderName'],
                        "poStatus" => $poStatus
                    );
                    //$output['PurchaseOrderList'][] = $rowproduct;
                    $output['PurchaseOrderList'][] = $data;
                }
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                //    $output['PurchaseOrderList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['PurchaseOrderList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == 'clinicordersubmit') {

    define('SERVER_API_KEY', 'AAAAL1zvBwk:APA91bHsUzoVQ7d85SDCEZlXSuJCAg4WTKXY0aosKmMRQCfOmYCLwM8hzLIW2Qlbsw64ekc6ik09Z12lnrMFNFovD3a8Uy4s4DCqRLy2_uRMfcsJyvNp4ZSY_rHvF3H-BpjSgH3DzH7d');

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt,cleaningName from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        $clinicName = $row['cleaningName'];

        if (validate_password($obj->Password, $good_hash)) {

            $data = array(
                "clinicId" => $row['userId'],
                "orderStatus" => 0,

                "strEntryDate" => date('d-m-Y H:i:s'),
                "strIP" => $_SERVER['REMOTE_ADDR'],

            );
            $order = $connect->insertrecord($dbconn, 'clinicordermaster', $data);

            for ($i = 0; $i < sizeof($obj->orderDetail); $i++) {
                $product = "select * from product where productId= " . $obj->orderDetail[$i]->productId . "";
                $productRow = mysqli_fetch_assoc(mysqli_query($dbconn, $product));
                // $productRow="";
                $fdata = array(
                    "clinicOrderId" => $order,
                    "productId" => $obj->orderDetail[$i]->productId,
                    "orderQty" => $obj->orderDetail[$i]->orderQty,
                    "dispatchQty" => 0,
                    "rate" => 0,
                    "amount" => 0,

                    "strEntryDate" => date('d-m-Y H:i:s'),
                    "strIP" => $_SERVER['REMOTE_ADDR'],

                );
                $details = $connect->insertrecord($dbconn, 'clinicorderdetails', $fdata);

                $output['message'] = 'Added Successfully !';
                $output['success'] = '1';

                //  send notification code

                $central = "select * from user where type = '2'  and isDelete = '0' and istatus = '1'";
                $rowcentral = mysqli_query($dbconn, $central);

                while ($rowcentralresult = mysqli_fetch_assoc($rowcentral)) {
                    $devicetokenid = $rowcentralresult['deviceToken'];

                    $bodydata = 'Order From ' . $clinicName;

                    $tokens = [$devicetokenid];

                    $header = [
                        'Authorization: Key=' . SERVER_API_KEY,
                        'Content-Type: Application/json',
                    ];

                    $msg = [
                        'title' => 'New Order',
                        'body' => $bodydata,
                        'click_action' => '2',

                    ];
                    $payload = [
                        'registration_ids' => $tokens,
                        'notification' => $msg,
                    ];

                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => "https://fcm.googleapis.com/fcm/send",
                        CURLOPT_RETURNTRANSFER => true,

                        CURLOPT_CUSTOMREQUEST => "POST",
                        CURLOPT_POSTFIELDS => json_encode($payload),
                        CURLOPT_HTTPHEADER => $header,

                    ));

                    $response = curl_exec($curl);
                    $err = curl_error($curl);

                    curl_close($curl);

                    // if ($err) {
                    //   echo "cURL Error #:" . $err;
                    // } else {
                    //   echo $response;
                    // }
                }
            }

        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == "clinicorderdetail") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $where = " where clinicOrderId = " . $obj->orderId . "";

            // if (isset($obj->FromDate) && ($obj->FromDate != '')) {

            //     $where .= " and STR_TO_DATE(clinicordermaster.strEntryDate,'%d-%m-%Y') >= STR_TO_DATE('" . $obj->FromDate . "','%d-%m-%Y')";
            // }

            // if (isset($obj->ToDate) && ($obj->ToDate != '')) {

            //     $where .= " and STR_TO_DATE(clinicordermaster.strEntryDate, '%d-%m-%Y')<=STR_TO_DATE('" . $obj->ToDate . "','%d-%m-%Y')";
            // }

            $product = "SELECT clinicorderdetails.clinicOrderDetailsId
            ,clinicorderdetails.orderQty,dispatchQty,rate,amount,(select productName from product where clinicorderdetails.productId=product.productId) as `productName`
             from clinicorderdetails inner join clinicordermaster on  clinicordermaster.clinicOrderMasterId= clinicorderdetails.clinicOrderId " . $where . " and  clinicorderdetails.istatus='1' and clinicorderdetails.isDelete='0' ";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {
                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $output['ClinicPendingOrderList'][] = $rowproduct;
                }
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                //    $output['PurchaseOrderList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['PurchaseOrderList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == "clinicpendingorderlist") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $where = " where clinicId = " . $row['userId'] . "";

            if (isset($obj->FromDate) && ($obj->FromDate != '')) {

                $where .= " and STR_TO_DATE(clinicordermaster.strEntryDate,'%d-%m-%Y') >= STR_TO_DATE('" . $obj->FromDate . "','%d-%m-%Y')";
            }

            if (isset($obj->ToDate) && ($obj->ToDate != '')) {

                $where .= " and STR_TO_DATE(clinicordermaster.strEntryDate, '%d-%m-%Y')<=STR_TO_DATE('" . $obj->ToDate . "','%d-%m-%Y')";
            }

            $product = "SELECT strEntryDate as OrderDate,clinicOrderMasterId as OrdreId, 0 as OrderAmount  from clinicordermaster " . $where . " and clinicordermaster.orderStatus='0'";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {
                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $output['ClinicPendingOrderList'][] = $rowproduct;
                }
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                //    $output['PurchaseOrderList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['PurchaseOrderList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == "clinicpendingorderlistforcentraladmin") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $where = " where 1=1 ";

            if (isset($obj->FromDate) && ($obj->FromDate != '')) {

                $where .= " and STR_TO_DATE(clinicordermaster.strEntryDate,'%d-%m-%Y') >= STR_TO_DATE('" . $obj->FromDate . "','%d-%m-%Y')";
            }

            if (isset($obj->ToDate) && ($obj->ToDate != '')) {

                $where .= " and STR_TO_DATE(clinicordermaster.strEntryDate, '%d-%m-%Y')<=STR_TO_DATE('" . $obj->ToDate . "','%d-%m-%Y')";
            }

            $product = "SELECT strEntryDate as OrderDate,clinicOrderMasterId as OrdreId, 0 as OrderAmount,(select cleaningName from user where userId = clinicordermaster.clinicId) as ClinicName  from clinicordermaster " . $where . " and clinicordermaster.orderStatus='0'";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {
                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $output['ClinicPendingOrderList'][] = $rowproduct;
                }
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                //    $output['PurchaseOrderList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['PurchaseOrderList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
    
} 
else if ($actions == "deleteclinicpendingorder") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);
    
    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];
    
        if (validate_password($obj->Password, $good_hash)) {
    
            $where = ' where clinicOrderMasterId=' . $obj->clinicOrderMasterId;
            $dealer = $connect->deleterecord($dbconn, 'clinicordermaster', $where);


          $where1 = ' where clinicOrderId=' . $obj->clinicOrderMasterId;
            $dealer = $connect->deleterecord($dbconn, 'clinicorderdetails', $where1);

            $output['message'] = 'delete successfully !';
            $output['success'] = '1';
    
        } else {
    
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
    } 
else if ($actions == 'dispatchorder') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            for ($i = 0; $i < sizeof($obj->orderDetail); $i++) {

                $product = "select product.* from clinicorderdetails,product where product.productId =clinicorderdetails.productId and clinicOrderDetailsId= " . $obj->orderDetail[$i]->clinicOrderDetailsId . "";
                $productRow = mysqli_fetch_assoc(mysqli_query($dbconn, $product));
                $Amount = $productRow['lpr'] * $obj->orderDetail[$i]->dispatchQty;
                // $productRow="";
                $fdata = array(
                    "dispatchQty" => $obj->orderDetail[$i]->dispatchQty,
                    "rate" => $productRow['lpr'],
                    "amount" => $Amount,
                    "strEntryDate" => date('d-m-Y H:i:s'),
                    "strIP" => $_SERVER['REMOTE_ADDR'],

                );
                $Update = " where clinicOrderDetailsId = " . $obj->orderDetail[$i]->clinicOrderDetailsId;
                $dealer = $connect->updaterecord($dbconn, 'clinicorderdetails', $fdata, $Update);

            }
            //code to set dispatch status of order
            $fdata = array(
                "orderStatus" => "1",
            );
            $Update = " where clinicOrderMasterId = " . $obj->ClinicOrderId;
            $dealer = $connect->updaterecord($dbconn, 'clinicordermaster', $fdata, $Update);

            $output['message'] = 'Order Dispatched Successfully !';
            $output['success'] = '1';

        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == "clinicdispatchorderlist") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $where = " where clinicId = " . $row['userId'] . "";

            if (isset($obj->FromDate) && ($obj->FromDate != '')) {

                $where .= " and STR_TO_DATE(clinicordermaster.strEntryDate,'%d-%m-%Y') >= STR_TO_DATE('" . $obj->FromDate . "','%d-%m-%Y')";
            }

            if (isset($obj->ToDate) && ($obj->ToDate != '')) {

                $where .= " and STR_TO_DATE(clinicordermaster.strEntryDate, '%d-%m-%Y')<=STR_TO_DATE('" . $obj->ToDate . "','%d-%m-%Y')";
            }

            $product = "SELECT strEntryDate as OrderDate,clinicOrderMasterId as OrdreId, (select sum(dispatchQty*rate) from clinicorderdetails where clinicorderdetails.clinicOrderId = clinicordermaster.clinicOrderMasterId) as OrderAmount  from clinicordermaster " . $where . " and clinicordermaster.orderStatus='1'";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {
                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $output['ClinicPendingOrderList'][] = $rowproduct;
                }
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                //    $output['PurchaseOrderList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['PurchaseOrderList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == "clinicdispatchorderlistforcentraladmin") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $where = " where 1=1";

            if (isset($obj->FromDate) && ($obj->FromDate != '')) {

                $where .= " and STR_TO_DATE(clinicordermaster.strEntryDate,'%d-%m-%Y') >= STR_TO_DATE('" . $obj->FromDate . "','%d-%m-%Y')";
            }

            if (isset($obj->ToDate) && ($obj->ToDate != '')) {

                $where .= " and STR_TO_DATE(clinicordermaster.strEntryDate, '%d-%m-%Y')<=STR_TO_DATE('" . $obj->ToDate . "','%d-%m-%Y')";
            }
            $product = "SELECT strEntryDate as OrderDate,clinicOrderMasterId as OrdreId,clinicordermaster.clinicId, (select sum(dispatchQty*rate) from clinicorderdetails where clinicorderdetails.clinicOrderId = clinicordermaster.clinicOrderMasterId) as OrderAmount,(select cleaningName from user where userId = clinicordermaster.clinicId) as ClinicName   from clinicordermaster " . $where . " and clinicordermaster.orderStatus='1' order by clinicordermaster.clinicOrderMasterId desc";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {
                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $output['ClinicPendingOrderList'][] = $rowproduct;
                }
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                //    $output['PurchaseOrderList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['PurchaseOrderList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == 'clinicreceivedordersubmit') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $product = "select product.*,clinicorderdetails.dispatchQty,clinicorderdetails.orderQty from clinicorderdetails,product where product.productId =clinicorderdetails.productId and clinicOrderId = " . $obj->clinicOrderId . "";
            $productRow = mysqli_query($dbconn, $product);
            $order=0;
            while ($rowproduct = mysqli_fetch_assoc($productRow)) {

                //ledger entry

                $fadata = array(
                    "ClinicOrderId" => $obj->clinicOrderId,
                    "productId" => $rowproduct['productId'],
                    "crQty" => 0,
                    "drQty" => $rowproduct['dispatchQty'],
                    "opening" => 0,
                    "closing" => 0,
                    "strEntryDate" => date('d-m-Y H:i:s'),
                    "strIP" => $_SERVER['REMOTE_ADDR'],
                );
                $dealer_res = $connect->insertrecord($dbconn, 'productledger', $fadata);
                $cr = $rowproduct['cr'];
                $dr = $rowproduct['dr'] + $rowproduct['dispatchQty'];
                $Closing = $rowproduct['openingStock'] + $cr - $dr;

                $ProductArray = array(
                    "dr" => $dr,
                    "closing" => $Closing,
                );
                $productUpdate = " where productId = " . $rowproduct['productId'];
                $dealer = $connect->updaterecord($dbconn, 'product', $ProductArray, $productUpdate);

                //CODE TO CREATE NEW ORDER AUTO ORDER
                if ($rowproduct['dispatchQty'] == 0) {
                    if ($order==0) {
                        $data = array(
                    "clinicId" => $row['userId'],
                    "orderStatus" => 0,
        
                    "strEntryDate" => date('d-m-Y H:i:s'),
                    "strIP" => $_SERVER['REMOTE_ADDR'],
        
                    );
                        $order = $connect->insertrecord($dbconn, 'clinicordermaster', $data);
                    }
                }
                //code to add product in order detail
                if ($rowproduct['dispatchQty'] == 0) {
                    $fdata = array(
                    "clinicOrderId" => $order,
                    "productId" => $rowproduct['productId'],
                    "orderQty" => $rowproduct['orderQty'],
                    "dispatchQty" => 0,
                    "rate" => 0,
                    "amount" => 0,
    
                    "strEntryDate" => date('d-m-Y H:i:s'),
                    "strIP" => $_SERVER['REMOTE_ADDR'],
    
                );
                    $details = $connect->insertrecord($dbconn, 'clinicorderdetails', $fdata);
                }


            }
            //code to set dispatch status of order
            $fdata = array(
                "orderStatus" => "2",
            );
            $Update = " where clinicOrderMasterId = " . $obj->clinicOrderId;
            $dealer = $connect->updaterecord($dbconn, 'clinicordermaster', $fdata, $Update);

            $output['message'] = 'Order Received Successfully !';
            $output['success'] = '1';

        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == "clinicreceivedorderlist") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $tstatus = 0;
            $where = " where clinicId = " . $row['userId'] . "";

            if (isset($obj->FromDate) && ($obj->FromDate != '')) {

                $where .= " and STR_TO_DATE(clinicordermaster.strEntryDate,'%d-%m-%Y') >= STR_TO_DATE('" . $obj->FromDate . "','%d-%m-%Y')";
                $tstatus = 1;
            }

            if (isset($obj->ToDate) && ($obj->ToDate != '')) {

                $where .= " and STR_TO_DATE(clinicordermaster.strEntryDate, '%d-%m-%Y') <= STR_TO_DATE('" . $obj->ToDate . "','%d-%m-%Y')";
                $tstatus = 1;
            }

            $product = "";
            if ($tstatus == 0) {
                $product = "SELECT strEntryDate as OrderDate,clinicOrderMasterId as OrdreId, (select sum(dispatchQty*rate) from clinicorderdetails where clinicorderdetails.clinicOrderId = clinicordermaster.clinicOrderMasterId) as OrderAmount  from clinicordermaster " . $where . " and clinicordermaster.orderStatus='2' order by clinicOrderMasterId desc limit 10";
            } else {
                $product = "SELECT strEntryDate as OrderDate,clinicOrderMasterId as OrdreId, (select sum(dispatchQty*rate) from clinicorderdetails where clinicorderdetails.clinicOrderId = clinicordermaster.clinicOrderMasterId) as OrderAmount  from clinicordermaster " . $where . " and clinicordermaster.orderStatus='2' order by clinicOrderMasterId ";
            }
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {
                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $output['ClinicReceivedOrderList'][] = $rowproduct;
                }
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                //    $output['PurchaseOrderList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['PurchaseOrderList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == "clinicreceivedorderlistcentraladmin") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $where = " where 1=1 ";

            if (isset($obj->FromDate) && ($obj->FromDate != '')) {

                $where .= " and STR_TO_DATE(clinicordermaster.strEntryDate,'%d-%m-%Y') >= STR_TO_DATE('" . $obj->FromDate . "','%d-%m-%Y')";
            }

            if (isset($obj->ToDate) && ($obj->ToDate != '')) {

                $where .= " and STR_TO_DATE(clinicordermaster.strEntryDate, '%d-%m-%Y')<=STR_TO_DATE('" . $obj->ToDate . "','%d-%m-%Y')";
            }

            if (isset($obj->clinicId) && $obj->clinicId != '0' && $obj->clinicId != '') {
                $where .= " and clinicId = '" . $obj->clinicId . "'";
            }

            $product = "SELECT strEntryDate as OrderDate,clinicOrderMasterId as OrdreId, (select sum(dispatchQty*rate) from clinicorderdetails where clinicorderdetails.clinicOrderId = clinicordermaster.clinicOrderMasterId) as OrderAmount,(select cleaningName from user where userId = clinicordermaster.clinicId) as ClinicName  from clinicordermaster " . $where . " and clinicordermaster.orderStatus='2'";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {
                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $output['ClinicReceivedOrderList'][] = $rowproduct;
                }
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                //    $output['PurchaseOrderList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['PurchaseOrderList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == "centraladminconsolidatedorderreport") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $product = "select product.productId,product.productName,product.lpr,product.minStockQty,product.closing as balanceStock,sum(clinicorderdetails.orderQty) clinicOrdQty
                from clinicorderdetails,product where product.productId =clinicorderdetails.productId
                    and clinicorderdetails.clinicOrderId in (select clinicordermaster.clinicOrderMasterId from clinicordermaster where clinicordermaster.orderStatus=0)
                    group by clinicorderdetails.productId";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {
                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $output['ProductList'][] = $rowproduct;
                }
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                // $output['ProductList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            //  $output['ProductList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == "outofstock") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $product = "select productName,lpr,minStockQty,closing from product where closing <= minStockQty and minStockQty>0 and isDelete='0' and istatus='1' ";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {
                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $output['OutOfStockList'][] = $rowproduct;
                }
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                //    $output['PurchaseOrderList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['PurchaseOrderList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == "clinicdashboard") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $product = "SELECT (select count(*) from clinicordermaster where clinicordermaster.orderStatus = 0 and clinicordermaster.clinicId = " . $row['userId'] . ") as PendingOrder , (select count(*) from clinicordermaster where clinicordermaster.orderStatus = 1 and clinicordermaster.clinicId = " . $row['userId'] . ") as DispatchedOrder";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {
                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $output['clinicdashboard'][] = $rowproduct;
                }
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                // $output['ProductList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            //  $output['ProductList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == "listofvenderdetail") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $vender = "SELECT `venderName`,`mobile`,`contactPerson` FROM `vender` where venderId = '" . $obj->venderId . "' and isDelete= '0' and istatus= '1'";
            $vender = mysqli_query($dbconn, $vender);
            if (mysqli_num_rows($vender) > 0) {
                while ($rowvender = mysqli_fetch_assoc($vender)) {
                    $output['VenderList'][] = $rowvender;
                }
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                //  $output['VenderList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['VenderList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == "listofcleaningdetail") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $product = "SELECT `cleaningName`,`mobile`,`contactPerson` FROM `user` where userId = '" . $obj->userId . "' and  type ='3' and isDelete= '0' and istatus= '1'";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {
                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $output['ClinicList'][] = $rowproduct;
                }
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                // $output['CentralAdminList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['CentralAdminList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == "receivedorderhistoryforpayment") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $where = " where 1=1 ";

            if (isset($obj->FromDate) && ($obj->FromDate != '')) {

                $where .= " and STR_TO_DATE(purchaseorder.strEntryDate,'%d-%m-%Y') >= STR_TO_DATE('" . $obj->FromDate . "','%d-%m-%Y')";
            }

            if (isset($obj->ToDate) && ($obj->ToDate != '')) {

                $where .= " and STR_TO_DATE(purchaseorder.strEntryDate, '%d-%m-%Y')<=STR_TO_DATE('" . $obj->ToDate . "','%d-%m-%Y')";
            }

            if (isset($obj->venderId) && $obj->venderId != '0' && $obj->venderId != '') {
                $where .= " and purchaseorder.venderId = '" . $obj->venderId . "'";
            }

            $product = "SELECT purchaseorder.pono,purchaseorder.date,ordermaster.invoiceNo,ordermaster.invoiceDate,purchaseorder.venderId,purchaseorder.purchaseOrderId,
                           (select venderName from vender where vender.venderId=purchaseorder.venderId) as `venderName`
                            ,(SELECT sum(orderdetails.receivedQty * orderdetails.rate) from orderdetails where orderdetails.orderId= ordermaster.orderId and orderdetails.istatus =1 and orderdetails.isDelete=0) as Amount
          from purchaseorder inner join ordermaster on purchaseorder.purchaseOrderId=ordermaster.purchaseOrderId  " . $where . " and purchaseorder.poStatus='3' and purchaseorder.isDelete= '0' and purchaseorder.istatus= '1' order by purchaseorder.purchaseOrderId desc limit 10";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {
                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $output['ReceivedOrderHistoryDetails'][] = $rowproduct;
                }
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                //    $output['PurchaseOrderList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['PurchaseOrderList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == 'markaspaidforreceived') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $data = array(

                "paidBy" => $row['userId'],
                "paymentDate" => date('d-m-Y'),
                "poStatus" => 4,
                "strIP" => $_SERVER['REMOTE_ADDR'],

            );
            $where = " where purchaseOrderId = " . $obj->purchaseOrderId;
            $dealer = $connect->updaterecord($dbconn, 'purchaseorder', $data, $where);

            $output['message'] = 'Update Successfully !';
            $output['success'] = '1';
        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == 'paidallreceived') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $data = array(

                "paidBy" => $row['userId'],
                "paymentDate" => date('d-m-Y'),
                "poStatus" => 4,
                "strIP" => $_SERVER['REMOTE_ADDR'],

            );
            $where = " where venderId = " . $obj->venderId;
            $dealer = $connect->updaterecord($dbconn, 'purchaseorder', $data, $where);

            $output['message'] = 'Update Successfully !';
            $output['success'] = '1';
        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == "listofpaidpaymentofreceivedorder") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $where = " where 1=1 ";

            if (isset($obj->FromDate) && ($obj->FromDate != '')) {

                $where .= " and STR_TO_DATE(purchaseorder.paymentDate,'%d-%m-%Y') >= STR_TO_DATE('" . $obj->FromDate . "','%d-%m-%Y')";
            }

            if (isset($obj->ToDate) && ($obj->ToDate != '')) {

                $where .= " and STR_TO_DATE(purchaseorder.paymentDate, '%d-%m-%Y')<=STR_TO_DATE('" . $obj->ToDate . "','%d-%m-%Y')";
            }

            if (isset($obj->venderId) && $obj->venderId != '0' && $obj->venderId != '') {
                $where .= " and purchaseorder.venderId = '" . $obj->venderId . "'";
            }

            $product = "SELECT purchaseorder.pono,purchaseorder.date,ordermaster.invoiceNo,ordermaster.invoiceDate,purchaseorder.venderId,purchaseorder.purchaseOrderId,purchaseorder.paymentDate,
                           (select venderName from vender where vender.venderId=purchaseorder.venderId) as `venderName`
                            ,(SELECT sum(orderdetails.receivedQty * orderdetails.rate) from orderdetails where orderdetails.orderId= ordermaster.orderId and orderdetails.istatus =1 and orderdetails.isDelete=0) as Amount
          from purchaseorder inner join ordermaster on purchaseorder.purchaseOrderId=ordermaster.purchaseOrderId   " . $where . " and purchaseorder.poStatus='4' and purchaseorder.isDelete= '0' and purchaseorder.istatus= '1'";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {
                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $output['ListOfPaidPayment'][] = $rowproduct;
                }
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                //    $output['PurchaseOrderList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['PurchaseOrderList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == "monthlymaterialconsumptionreport") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $where = " where 1=1 ";

            if (isset($obj->FromDate) && ($obj->FromDate != '')) {

                $where .= " and STR_TO_DATE(clinicordermaster.strEntryDate,'%d-%m-%Y') >= STR_TO_DATE('" . $obj->FromDate . "','%d-%m-%Y')";
            }

            if (isset($obj->ToDate) && ($obj->ToDate != '')) {

                $where .= " and STR_TO_DATE(clinicordermaster.strEntryDate, '%d-%m-%Y')<=STR_TO_DATE('" . $obj->ToDate . "','%d-%m-%Y')";
            }

            if (isset($obj->clinicId) && $obj->clinicId != '0' && $obj->clinicId != '') {
                $where .= " and clinicordermaster.clinicId = '" . $obj->clinicId . "'";
            }

            $clinic = "select clinicorderdetails.productId,(select productName from product where product.productId=clinicorderdetails.productId) as `productName`,
    clinicorderdetails.dispatchQty,clinicorderdetails.rate,clinicorderdetails.amount,clinicordermaster.strEntryDate from clinicordermaster inner join clinicorderdetails on clinicordermaster.clinicOrderMasterId=clinicorderdetails.clinicOrderId  " . $where . " and clinicordermaster.orderStatus='2' and clinicordermaster.isDelete='0' and clinicordermaster.istatus='1'";

            $clinic = mysqli_query($dbconn, $clinic);
            if (mysqli_num_rows($clinic) > 0) {
                while ($rowclinic = mysqli_fetch_assoc($clinic)) {
                    $output['ListOfClinic'][] = $rowclinic;
                }
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                //    $output['PurchaseOrderList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['PurchaseOrderList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == "materialreportclinicwise") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $where = " where 1=1 ";

            if (isset($obj->FromDate) && ($obj->FromDate != '')) {

                $where .= " and STR_TO_DATE(clinicorderdetails.strEntryDate,'%d-%m-%Y') >= STR_TO_DATE('" . $obj->FromDate . "','%d-%m-%Y')";
            }

            if (isset($obj->ToDate) && ($obj->ToDate != '')) {

                $where .= " and STR_TO_DATE(clinicorderdetails.strEntryDate, '%d-%m-%Y')<=STR_TO_DATE('" . $obj->ToDate . "','%d-%m-%Y')";
            }

            if (isset($obj->productId) && $obj->productId != '0' && $obj->productId != '') {
                $where .= " and clinicorderdetails.productId = '" . $obj->productId . "'";
            }

            $clinic = "SELECT SUM(clinicorderdetails.dispatchQty) as `dispatchQty`, clinicorderdetails.productId,(select productName from product where product.productId=clinicorderdetails.productId) as `productName`,
              clinicordermaster.clinicId,(select cleaningName from user where user.userId=clinicordermaster.clinicId) as `cleaningName`
              FROM `clinicorderdetails` INNER JOIN clinicordermaster on clinicorderdetails.clinicOrderId=clinicordermaster.clinicOrderMasterId " . $where . " and clinicordermaster.isDelete= '0' and clinicordermaster.istatus= '1' and clinicordermaster.orderStatus= '2' GROUP by clinicorderdetails.productId,clinicordermaster.clinicId";

            $clinic = mysqli_query($dbconn, $clinic);
            if (mysqli_num_rows($clinic) > 0) {
                while ($rowclinic = mysqli_fetch_assoc($clinic)) {
                    $output['ListOfClinic'][] = $rowclinic;
                }
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                //    $output['PurchaseOrderList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['PurchaseOrderList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == "materialpurchasereport") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $where = " where 1=1 ";

            if (isset($obj->FromDate) && ($obj->FromDate != '')) {

                $where .= " and STR_TO_DATE(orderdetails.strEntryDate,'%d-%m-%Y') >= STR_TO_DATE('" . $obj->FromDate . "','%d-%m-%Y')";
            }

            if (isset($obj->ToDate) && ($obj->ToDate != '')) {

                $where .= " and STR_TO_DATE(orderdetails.strEntryDate, '%d-%m-%Y')<=STR_TO_DATE('" . $obj->ToDate . "','%d-%m-%Y')";
            }

            if (isset($obj->productId) && $obj->productId != '0' && $obj->productId != '') {
                $where .= " and orderdetails.productId = '" . $obj->productId . "'";
            }

            $vender = "SELECT sum(orderdetails.receivedQty) as `receivedQty`,orderdetails.productId,orderdetails.rate,sum(orderdetails.amount) as `amount`,ordermaster.venderId,orderdetails.strEntryDate,
    (select venderName from vender WHERE vender.venderId=ordermaster.venderId) as `venderName` FROM `orderdetails`
     INNER join ordermaster on orderdetails.orderId=ordermaster.orderId " . $where . " and  orderdetails.istatus=1 and orderdetails.isDelete=0 GROUP by orderdetails.productId,ordermaster.venderId";

            $vender = mysqli_query($dbconn, $vender);
            if (mysqli_num_rows($vender) > 0) {
                while ($rowvender = mysqli_fetch_assoc($vender)) {
                    $output['ListOfPurchaseReport'][] = $rowvender;
                }
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                //    $output['PurchaseOrderList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['PurchaseOrderList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == "productanalysis") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $where = " where 1=1 ";

            if (isset($obj->FromDate) && ($obj->FromDate != '')) {

                $where .= " and STR_TO_DATE(strEntryDate,'%d-%m-%Y') >= STR_TO_DATE('" . $obj->FromDate . "','%d-%m-%Y')";
            }

            if (isset($obj->ToDate) && ($obj->ToDate != '')) {

                $where .= " and STR_TO_DATE(strEntryDate, '%d-%m-%Y')<=STR_TO_DATE('" . $obj->ToDate . "','%d-%m-%Y')";
            }

            $product = "SELECT productName,openingStock,closing,productId,(select sum(receivedQty) from orderdetails where orderdetails.productId=product.productId) as `receivedQty`,
           (select sum(dispatchQty) from clinicorderdetails WHERE clinicorderdetails.productId=product.productId) as `dispatchQty`
                   from product  " . $where . " and istatus=1 and isDelete=0 ";

            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {
                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $output['ListOfProduct'][] = $rowproduct;
                }
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                //    $output['PurchaseOrderList'][] = "";

                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['PurchaseOrderList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == 'createotherstaff') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo' and type='1'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {
            $hash_result = create_hash($obj->password);
            $hash_params = explode(":", $hash_result);
            $salt = $hash_params[HASH_SALT_INDEX];
            $hash = $hash_params[HASH_PBKDF2_INDEX];
            $data = array(
                "cleaningName" => $obj->cleaningName,
                "contactPerson" => $obj->contactPerson,
                "mobile" => $obj->mobile,
                "password" => $hash,
                "type" => 5,
                "salt" => $salt,
                "strEntryDate" => date('d-m-Y H:i:s'),
                "strIP" => $_SERVER['REMOTE_ADDR'],

            );
            $dealer_res = $connect->insertrecord($dbconn, 'user', $data);
            $output['message'] = 'Added Successfully !';
            $output['success'] = '1';
        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == 'updateotherstaff') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo' and type='1'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $data = array(
                "cleaningName" => $obj->cleaningName,
                "contactPerson" => $obj->contactPerson,
                "mobile" => $obj->mobile,
                "type" => 5,
                "strEntryDate" => date('d-m-Y H:i:s'),
                "strIP" => $_SERVER['REMOTE_ADDR'],

            );
            $where = " where userId = " . $obj->userId;
            $dealer = $connect->updaterecord($dbconn, 'user', $data, $where);

            $output['message'] = 'Update Successfully !';
            $output['success'] = '1';
        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == "listofotherstaff") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt,type from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $where = " where 1=1 ";

            if (isset($obj->cleaningName) && ($obj->cleaningName != '0')) {
                $where .= " and cleaningName like '%" . $obj->cleaningName . "%'";
            }

            $arrayVal = array('userId' => '', 'cleaningName' => '', 'mobile' => '', 'contactPerson' => '');
            $product = "SELECT `userId`,`cleaningName`,`mobile`,`contactPerson` FROM `user` " . $where . " and type ='5' and isDelete= '0' and istatus= '1'";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {

                if ($row['type'] == "2" || $row['type'] == "3") {
                    $output['CentralAdminList'][] = array('userId' => '0', 'cleaningName' => 'Select Clinic', 'mobile' => '', 'contactPerson' => '');
                }
                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $output['CentralAdminList'][] = $rowproduct;
                }

                $output['message'] = '';
                $output['success'] = '1';
            } else {
                // $output['CentralAdminList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['CentralAdminList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == "gettendancetime") {
    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt,type from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {
            $sql = "SELECT * FROM `attendance` where userId = " . $row['userId'] . " and STR_TO_DATE(strEntryDate, '%d-%m-%Y') = DATE_FORMAT(now(),'%Y-%m-%d') and isDelete= '0' and istatus= '1'";
            $attendanceData = mysqli_query($dbconn, $sql);
            if (mysqli_num_rows($attendanceData) > 0) {
                while ($rowAttendance = mysqli_fetch_assoc($attendanceData)) {
                    $output['eveOutTime'] = $rowAttendance['eveOutTime'];
                    $output['MOutTime'] = $rowAttendance['MOutTime'];
                    $output['eveInTime'] = $rowAttendance['eveInTime'];
                    $output['MInTime'] = $rowAttendance['MInTime'];
                }
            } else {
                $output['eveOutTime'] = "-";
                $output['MOutTime'] = "-";
                $output['eveInTime'] = "-";
                $output['MInTime'] = "-";
            }
            $output['message'] = 'Attendance Found Successfully !';
            $output["success"] = '1';
        } else {
            $output['message'] = 'Error in Attandance';
            $output["success"] = '0';
        }
    }

} else if ($actions == "markattendance") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt,type from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $sql = "SELECT * FROM `attendance` where userId = " . $row['userId'] . " and STR_TO_DATE(strEntryDate, '%d-%m-%Y') = DATE_FORMAT(now(),'%Y-%m-%d') and isDelete= '0' and istatus= '1'";
            $attendanceData = mysqli_query($dbconn, $sql);
            if (mysqli_num_rows($attendanceData) > 0) {

                while ($rowAttendance = mysqli_fetch_assoc($attendanceData)) {

                    $where = " where attendanceId = " . $rowAttendance['attendanceId'];
                    if ($obj->Type == "Out") {
                            if ($rowAttendance['MOutTime'] == '' && $rowAttendance['eveInTime'] == '') {
                                $data = array(
                                "MOutTime" => $obj->PunchTime,
                                "MOutLat" => $obj->PunchLat,
                                "MOutLong" => $obj->PunchLong,
                                "MOutAddress" => $obj->PunchAddress,
                            );
                                $dealer = $connect->updaterecord($dbconn, 'attendance', $data, $where);
                            } elseif ($rowAttendance['eveOutTime'] == '') {
                                $data = array(
                                "eveOutTime" => $obj->PunchTime,
                                "eveOutLat" => $obj->PunchLat,
                                "eveOutLong" => $obj->PunchLong,
                                "eveOutAddress" => $obj->PunchAddress,
                            );
                                $dealer = $connect->updaterecord($dbconn, 'attendance', $data, $where);
                            }
                        
                    } else if ($obj->Type == "In") {
                        if ($rowAttendance['MInTime'] == '') {
                            $data = array(
                                "MInTime" => $obj->PunchTime,
                                "MInLat" => $obj->PunchLat,
                                "MInLong" => $obj->PunchLong,
                                "MInAddress" => $obj->PunchAddress,
                            );
                            $dealer = $connect->updaterecord($dbconn, 'attendance', $data, $where);
                        } else if ($rowAttendance['eveInTime'] == '') {
                            $data = array(
                                "eveInTime" => $obj->PunchTime,
                                "eveInLat" => $obj->PunchLat,
                                "eveInLong" => $obj->PunchLong,
                                "eveInAddress" => $obj->PunchAddress,
                            );
                            $dealer = $connect->updaterecord($dbconn, 'attendance', $data, $where);
                        }
                    }

                }

                $output['message'] = 'Attendance Marked Successfully !';
                $output['success'] = '1';
            } else {
                if ($obj->Type == "Out") {
                    $data = array(
                        "userId" => $row['userId'],
                        "MInTime" => '',
                        "MInLat" => '',
                        "MInLong" => '',
                        "MOutTime" => $obj->PunchTime,
                        "MOutLat" => $obj->PunchLat,
                        "MOutLong" => $obj->PunchLong,
                        "MOutAddress" => $obj->PunchAddress,
                        "eveInTime" => '',
                        "eveInLat" => '',
                        "eveInLong" => '',
                        "eveOutTime" => '',
                        "eveOutLat" => '',
                        "eveOutLong	" => '',
                        "strEntryDate" => date('d-m-Y H:i:s'),
                        "strIP" => $_SERVER['REMOTE_ADDR'],

                    );
                    $dealer_res = $connect->insertrecord($dbconn, 'attendance', $data);
                } else if ($obj->Type == "In") {
                    $data = array(
                        "userId" => $row['userId'],
                        "MInTime" => $obj->PunchTime,
                        "MInLat" => $obj->PunchLat,
                        "MInLong" => $obj->PunchLong,
                        "MInAddress" => $obj->PunchAddress,
                        "MOutTime" => '',
                        "MOutLat" => '',
                        "MOutLong" => '',
                        "MOutAddress" => '',
                        "eveInTime" => '',
                        "eveInLat" => '',
                        "eveInLong" => '',
                        "eveOutTime" => '',
                        "eveOutLat" => '',
                        "eveOutLong	" => '',
                        "eveInAddress	" => '',
                        "eveOutAddress	" => '',
                        "strEntryDate" => date('d-m-Y H:i:s'),
                        "strIP" => $_SERVER['REMOTE_ADDR'],

                    );
                    $dealer_res = $connect->insertrecord($dbconn, 'attendance', $data);
                }

                $output['message'] = 'Attendance Marked Successfully !';
                $output["success"] = '1';
            }

        } else {
            // $output['CentralAdminList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == "attendancereport") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt,type from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            //$where = " where userId ".$obj->EmpId;

            //$product = "SELECT `userId`,`cleaningName`,`mobile`,`contactPerson` FROM `user` ".$where." and type ='5' and isDelete= '0' and istatus= '1'";
         $sql = "SELECT (select user.contactPerson from user where user.userId = attendance.userId) as EmployeeName,TIME(STR_TO_DATE(MInTime,'%d-%m-%Y %H:%i:%s')) as MInTime, `MInLat`, `MInLong`,MInAddress,TIME(STR_TO_DATE(MOutTime,'%d-%m-%Y %H:%i:%s')) as MOutTime, `MOutLat`, `MOutLong`, MOutAddress,IFNULL(null,TIME(STR_TO_DATE(`eveInTime`,'%d-%m-%Y %H:%i:%s'))) as `eveInTime`, `eveInLat`, `eveInLong`, eveInAddress,IFNULL(null,TIME(STR_TO_DATE(`eveOutTime`,'%d-%m-%Y %H:%i:%s'))) as `eveOutTime`, `eveOutLat`, `eveOutLong`, eveOutAddress,DATE_FORMAT(STR_TO_DATE(strEntryDate, '%d-%m-%Y'),'%d-%m-%Y') as `strEntryDate` FROM `attendance` where userId	 = " . $obj->EmpId . " and STR_TO_DATE(strEntryDate, '%d-%m-%Y') >= STR_TO_DATE('" . $obj->DateFrom . "','%d-%m-%Y') and STR_TO_DATE(strEntryDate, '%d-%m-%Y') <= STR_TO_DATE('" . $obj->DateTo . "','%d-%m-%Y') and isDelete= '0' and istatus= '1' order by attendanceId asc";
            $product = mysqli_query($dbconn, $sql);
            if (mysqli_num_rows($product) > 0) {
                while ($rowAttendance = mysqli_fetch_assoc($product)) {
                    $output['AttendanceList'][] = $rowAttendance;
                }

                $output['message'] = 'Report found successfully !';
                $output['success'] = '1';
            } else {
                // $output['CentralAdminList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['CentralAdminList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == "listofallemployee") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt,type from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $product = "SELECT `userId` as EmpId,CONCAT(`contactPerson`,'-',ifnull(cleaningName,'')) as EmployeeName FROM `user` where type in ('3','2','5') and isDelete= '0' and istatus= '1'";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {

                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $output['listofallemployee'][] = $rowproduct;
                }

                $output['message'] = 'Employee Found Successfully !';
                $output['success'] = '1';
            } else {
                // $output['CentralAdminList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['CentralAdminList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == "admincentraldashboard") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {
//purchaseorder
            $order = "SELECT (select count(*) from purchaseorder where purchaseorder.poStatus = 1 ) as pendingpo , (select count(*) from purchaseorder where purchaseorder.poStatus = 2) as approvedpo,(select count(*) from clinicordermaster where clinicordermaster.orderStatus = 0 ) as PendingOrder,(select count(*) from clinicordermaster where clinicordermaster.orderStatus = 1)as deispatchorder,(select count(*) from product where closing <= minStockQty and minStockQty>0) as `outofstock`";
            $order = mysqli_query($dbconn, $order);
            if (mysqli_num_rows($order) > 0) {
                while ($roworder = mysqli_fetch_assoc($order)) {
                    $output['Dashboard'][] = $roworder;
                }
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                // $output['ProductList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            //  $output['ProductList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == 'addleaveapplication') {

    define('SERVER_API_KEY', 'AAAAL1zvBwk:APA91bHsUzoVQ7d85SDCEZlXSuJCAg4WTKXY0aosKmMRQCfOmYCLwM8hzLIW2Qlbsw64ekc6ik09Z12lnrMFNFovD3a8Uy4s4DCqRLy2_uRMfcsJyvNp4ZSY_rHvF3H-BpjSgH3DzH7d');

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt,contactPerson from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        $Name = $row['contactPerson'];

        if (validate_password($obj->Password, $good_hash)) {
            $data = array(
                "leaveReason" => $obj->leaveReason,
                "leaveDate" => $obj->leaveDate,
                "leaveType" => $obj->leaveType,

                "employeeId" => $row['userId'],
                "leaveStatus" => 0,
                "strEntryDate" => date('d-m-Y H:i:s'),
                "strIP" => $_SERVER['REMOTE_ADDR'],

            );
            $dealer = $connect->insertrecord($dbconn, 'leaveapplication', $data);
            
            $output['employeeId'] = $row['userId'];
            $output['message'] = 'Added Successfully !';
            $output['success'] = '1';

            //  send notification code

            $admin = "select * from user where type = '1'  and isDelete = '0' and istatus = '1'";
            $rowadmin = mysqli_query($dbconn, $admin);

            while ($rowadminresult = mysqli_fetch_assoc($rowadmin)) {
                $devicetokenid = $rowadminresult['deviceToken'];

                $bodydata = 'Get New Leave Application From ' . $Name;

                $tokens = [$devicetokenid];

                $header = [
                    'Authorization: Key=' . SERVER_API_KEY,
                    'Content-Type: Application/json',
                ];

                $msg = [
                    'title' => 'Leave Application',
                    'body' => $bodydata,
                    'click_action' => '1',

                ];
                $payload = [
                    'registration_ids' => $tokens,
                    'notification' => $msg,
                ];

                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://fcm.googleapis.com/fcm/send",
                    CURLOPT_RETURNTRANSFER => true,

                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => json_encode($payload),
                    CURLOPT_HTTPHEADER => $header,

                ));

                $response = curl_exec($curl);
                $err = curl_error($curl);

                curl_close($curl);
            }
        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == "listofleaveapplication") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt,type from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);

        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $where = " where 1=1 ";

            if (isset($obj->FromDate) && ($obj->FromDate != '')) {

                $where .= " and STR_TO_DATE(leaveDate,'%d-%m-%Y') >= STR_TO_DATE('" . $obj->FromDate . "','%d-%m-%Y')";
            }

            if (isset($obj->ToDate) && ($obj->ToDate != '')) {

                $where .= " and STR_TO_DATE(leaveDate, '%d-%m-%Y')<=STR_TO_DATE('" . $obj->ToDate . "','%d-%m-%Y')";
            }

            if (isset($obj->leaveStatus) && $obj->leaveStatus != '') {
                $where .= " and leaveStatus like '%" . $obj->leaveStatus . "%'";
            }
            //  $arrayVal = array('productId' => '', 'productName' => '', 'lpr' => '', 'openingStock' => '', 'minStockQty' => '');

            $leave = "SELECT `leaveId`,`leaveReason`,`leaveDate`,leaveType,employeeId,leaveStatus FROM `leaveapplication` " . $where . " and isDelete= '0' and istatus= '1' and employeeId=".$row['userId']." order by leaveId desc";
            $leave = mysqli_query($dbconn, $leave);
            if (mysqli_num_rows($leave) > 0) {

                while ($rowleave = mysqli_fetch_assoc($leave)) {

                    $output['LeaveApplicationList'][] = $rowleave;

                }

                $output['message'] = '';
                $output['success'] = '1';
            } else {
                // $output['ProductList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            //  $output['ProductList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == "listofpendingleaveapplication") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt,type from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);

        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            // $where = " where 1=1 ";

            // if (isset($obj->FromDate) && ($obj->FromDate != '')) {

            //     $where .= " and STR_TO_DATE(leaveDate,'%d-%m-%Y') >= STR_TO_DATE('" . $obj->FromDate . "','%d-%m-%Y')";
            // }

            // if (isset($obj->ToDate) && ($obj->ToDate != '')) {

            //     $where .= " and STR_TO_DATE(leaveDate, '%d-%m-%Y')<=STR_TO_DATE('" . $obj->ToDate . "','%d-%m-%Y')";
            // }

            // if (isset($obj->employeeId) && $obj->employeeId != '0' && $obj->employeeId != '') {
            //     $where .= " and employeeId like '%" . $obj->employeeId . "%'";
            // }

            //  $arrayVal = array('productId' => '', 'productName' => '', 'lpr' => '', 'openingStock' => '', 'minStockQty' => '');

            $leave = "SELECT `leaveId`,`leaveReason`,`leaveDate`,leaveType,employeeId,leaveStatus,(select contactPerson from user where user.userId=leaveapplication.employeeId) as `contactPerson` FROM `leaveapplication` where isDelete= '0' and istatus= '1' and leaveStatus= '0' order by leaveId desc";
            $leave = mysqli_query($dbconn, $leave);
            if (mysqli_num_rows($leave) > 0) {

                while ($rowleave = mysqli_fetch_assoc($leave)) {
                    $output['PendingLeaveApplicationList'][] = $rowleave;
                }

                $output['message'] = '';
                $output['success'] = '1';
            } else {
                // $output['ProductList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            //  $output['ProductList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == 'approvedleaveapplication') {

    define('SERVER_API_KEY', 'AAAAL1zvBwk:APA91bHsUzoVQ7d85SDCEZlXSuJCAg4WTKXY0aosKmMRQCfOmYCLwM8hzLIW2Qlbsw64ekc6ik09Z12lnrMFNFovD3a8Uy4s4DCqRLy2_uRMfcsJyvNp4ZSY_rHvF3H-BpjSgH3DzH7d');

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $data = array(

                "employeeId" => $obj->employeeId,
                "leaveStatus" => 1,
                "approvedDate" => date('d-m-Y'),

            );
            $where = " where leaveId = " . $obj->leaveId;
            $dealer = $connect->updaterecord($dbconn, 'leaveapplication', $data, $where);

            $output['message'] = 'Approved Successfully !';
            $output['success'] = '1';

            //  send notification code

            $admin = "select * from user where userId=" . $obj->employeeId . "  and isDelete = '0' and istatus = '1'";
            $rowadmin = mysqli_query($dbconn, $admin);

            while ($rowadminresult = mysqli_fetch_assoc($rowadmin)) {
                $devicetokenid = $rowadminresult['deviceToken'];

                $bodydata = 'Leave Application has been approved by admin';

                $tokens = [$devicetokenid];

                $header = [
                    'Authorization: Key=' . SERVER_API_KEY,
                    'Content-Type: Application/json',
                ];

                $msg = [
                    'title' => 'Leave Application',
                    'body' => $bodydata,
                    'click_action' => '3',

                ];
                $payload = [
                    'registration_ids' => $tokens,
                    'notification' => $msg,
                ];

                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://fcm.googleapis.com/fcm/send",
                    CURLOPT_RETURNTRANSFER => true,

                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => json_encode($payload),
                    CURLOPT_HTTPHEADER => $header,

                ));

                $response = curl_exec($curl);
                $err = curl_error($curl);

                curl_close($curl);
            }
        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == 'rejectedleaveapplication') {

    define('SERVER_API_KEY', 'AAAAL1zvBwk:APA91bHsUzoVQ7d85SDCEZlXSuJCAg4WTKXY0aosKmMRQCfOmYCLwM8hzLIW2Qlbsw64ekc6ik09Z12lnrMFNFovD3a8Uy4s4DCqRLy2_uRMfcsJyvNp4ZSY_rHvF3H-BpjSgH3DzH7d');

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $data = array(

                "employeeId" => $obj->employeeId,
                "leaveStatus" => 2,
                "rejectedDate" => date('d-m-Y'),

            );
            $where = " where leaveId = " . $obj->leaveId;
            $dealer = $connect->updaterecord($dbconn, 'leaveapplication', $data, $where);

            $output['message'] = 'Rejected Successfully !';
            $output['success'] = '1';

         //  send notification code

            $admin = "select * from user where userId=" . $obj->employeeId . "  and isDelete = '0' and istatus = '1'";
            $rowadmin = mysqli_query($dbconn, $admin);

            while ($rowadminresult = mysqli_fetch_assoc($rowadmin)) {
                $devicetokenid = $rowadminresult['deviceToken'];

                $bodydata = 'Leave Application has been rejected by admin';

                $tokens = [$devicetokenid];

                $header = [
                    'Authorization: Key=' . SERVER_API_KEY,
                    'Content-Type: Application/json',
                ];

                $msg = [
                    'title' => 'Leave Application',
                    'body' => $bodydata,
                    'click_action' => '3',

                ];
                $payload = [
                    'registration_ids' => $tokens,
                    'notification' => $msg,
                ];

                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://fcm.googleapis.com/fcm/send",
                    CURLOPT_RETURNTRANSFER => true,

                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => json_encode($payload),
                    CURLOPT_HTTPHEADER => $header,

                ));

                $response = curl_exec($curl);
                $err = curl_error($curl);

                curl_close($curl);
            }
        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} 
else if ($actions == "leavereport") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt,type from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);

        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $where = " where 1=1 ";

            if (isset($obj->FromDate) && ($obj->FromDate != '')) {

                $where .= " and STR_TO_DATE(leaveDate,'%d-%m-%Y') >= STR_TO_DATE('" . $obj->FromDate . "','%d-%m-%Y')";
            }

            if (isset($obj->ToDate) && ($obj->ToDate != '')) {

                $where .= " and STR_TO_DATE(leaveDate, '%d-%m-%Y')<=STR_TO_DATE('" . $obj->ToDate . "','%d-%m-%Y')";
            }

            if (isset($obj->contactPersonId) && $obj->contactPersonId != '0' && $obj->contactPersonId != '') {
                $where .= " and employeeId = '" . $obj->contactPersonId . "'";

            }

            if (isset($obj->leaveStatus) && $obj->leaveStatus != '') {
                $where .= " and leaveStatus like '%" . $obj->leaveStatus . "%'";
            }

            //  $arrayVal = array('productId' => '', 'productName' => '', 'lpr' => '', 'openingStock' => '', 'minStockQty' => '');

            $count = "select sum(leaveType) as `count`,leaveStatus from leaveapplication " . $where . " and isDelete= '0' and istatus= '1' and leaveStatus != '0'  group by leaveStatus";
            $count = mysqli_query($dbconn, $count);

            $leavecount = '';
            $totalleavecount= "";
            while ($rowcount = mysqli_fetch_assoc($count)) {

                if ($rowcount['leaveStatus'] == '1') {
                    $leavecount = $rowcount['count'];
                    $totalleavecount=$totalleavecount+$rowcount['count'];
                    
                } elseif ($rowcount['leaveStatus'] == '2') {
                    $leavecount = $rowcount['count'];
                    $totalleavecount=$totalleavecount+$rowcount['count'];
                }
            }
            

            $leave = "SELECT `leaveId`,`leaveReason`,`leaveDate`,leaveType,employeeId,leaveStatus,(select contactPerson from user where user.userId=leaveapplication.employeeId) as `contactPerson` FROM `leaveapplication` " . $where . " and isDelete= '0' and istatus= '1' and leaveStatus in (1,2) order by leaveId desc";
            $leave = mysqli_query($dbconn, $leave);
            if (mysqli_num_rows($leave) > 0) {

                while ($rowleave = mysqli_fetch_assoc($leave)) {
                    $output['LeaveReport'][] = $rowleave;
                }
                if ($obj->leaveStatus == 1 || $obj->leaveStatus == 2) 
                    $output['count'] =$leavecount;
                 else
                    $output['count'] =  strval($totalleavecount); 
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                // $output['ProductList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            //  $output['ProductList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
}
else if ($actions == "employeelist") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt,type from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);

        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

          

            //  $arrayVal = array('productId' => '', 'productName' => '', 'lpr' => '', 'openingStock' => '', 'minStockQty' => '');

            $employee = "SELECT userId,contactPerson FROM `user` where isDelete= '0' and istatus= '1'";
            $employee = mysqli_query($dbconn, $employee);
            if (mysqli_num_rows($employee) > 0) {

                while ($rowemployee = mysqli_fetch_assoc($employee)) {
                    $output['EmployeeList'][] = $rowemployee;
                }

                $output['message'] = '';
                $output['success'] = '1';
            } else {
                // $output['ProductList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            //  $output['ProductList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == 'submitvoucher') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {
            $queryvoucher = "SELECT * FROM `voucher` where strVoucherNo like '%".trim($obj->strVoucherNo)."%'";
            $resultvoucherData = mysqli_query($dbconn, $queryvoucher);
            if (mysqli_num_rows($resultvoucherData) > 0) {
                $output['message'] = 'Voucher Number already exist.';
                $output['success'] = '0';
            } else {
                $query = "SELECT * FROM `vender` where venderId=".$obj->iVenderId;
                $resultData = mysqli_query($dbconn, $query);
                $rowData = mysqli_fetch_assoc($resultData);
                $data = array(
                    "strVoucherNo" => $obj->strVoucherNo,
                    "strVoucherDate" => date('d-m-Y',strtotime($obj->strVoucherDate)),
                    "iVenderId" => $obj->iVenderId,
                    "strVoucherName" => $rowData['venderName'],
                    "iEntryBy" => $row['userId'],
                    "strEntryDate" => date('d-m-Y H:i:s'),
                    "strIP" => $_SERVER['REMOTE_ADDR'],
                );
                $iVoucherId = $connect->insertrecord($dbconn, 'voucher', $data);
                $output['strVoucherName'] = $rowData['venderName'];
                $output['iVenderId'] = $obj->iVenderId;
                $output['iVoucherId'] = $iVoucherId;
                $output['message'] = 'Added Successfully !';
                $output['success'] = '1';
            }
        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == "voucherlisting") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $where = " and 1=1 ";

            if (isset($obj->FromDate) && ($obj->FromDate != '')) {

                $where .= " and STR_TO_DATE(strVoucherDate,'%d-%m-%Y') >= STR_TO_DATE('" . $obj->FromDate . "','%d-%m-%Y')";
            }

            if (isset($obj->ToDate) && ($obj->ToDate != '')) {

                $where .= " and STR_TO_DATE(strVoucherDate, '%d-%m-%Y')<=STR_TO_DATE('" . $obj->ToDate . "','%d-%m-%Y')";
            }

            if (isset($obj->VenderId) && $obj->VenderId != '0' && $obj->VenderId != '') {
                $where .= " and iVenderId = '" . $obj->VenderId . "'";
            }
            // data from order master
            $product = "select id,strVoucherNo,strVoucherName,strVoucherDate,iVenderId,iTotalAmount,isVoucherLock from voucher where   isDelete= '0' and istatus= '1' " . $where . "";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {
                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $data = array(
                        "iVoucherId" => $rowproduct['id'],
                        "strVoucherNo" => $rowproduct['strVoucherNo'],
                        "strVoucherName" => $rowproduct['strVoucherName'],
                        "strVoucherDate" => $rowproduct['strVoucherDate'],
                        "iTotalAmount" => $rowproduct['iTotalAmount'],
                        "iVenderId" => $rowproduct['iVenderId'],
                        "isVoucherLock" => $rowproduct['isVoucherLock']
                    );
                    //$output['PurchaseOrderList'][] = $rowproduct;
                    $output['voucherlisting'][] = $data;
                }
                $output['message'] = '';
                $output['success'] = '1';
            } else {
                //    $output['PurchaseOrderList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['PurchaseOrderList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
}
else if ($actions == "pendingvoucherlisting") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $where = " and 1=1 ";

            if (isset($obj->FromDate) && ($obj->FromDate != '')) {

                $where .= " and STR_TO_DATE(ordermaster.strEntryDate,'%d-%m-%Y') >= STR_TO_DATE('" . $obj->FromDate . "','%d-%m-%Y')";
            }

            if (isset($obj->ToDate) && ($obj->ToDate != '')) {

                $where .= " and STR_TO_DATE(ordermaster.strEntryDate, '%d-%m-%Y')<=STR_TO_DATE('" . $obj->ToDate . "','%d-%m-%Y')";
            }

            if (isset($obj->VenderId) && $obj->VenderId != '0' && $obj->VenderId != '') {
                $where .= " and ordermaster.venderId = '" . $obj->VenderId . "'";
            }
            // data from order master
            // $product = "select ordermaster.orderId,ordermaster.invoiceDate as date ,ordermaster.invoiceNo,
            // (select sum(orderdetails.receivedQty * orderdetails.rate) from orderdetails where orderdetails.orderId = ordermaster.orderId) as Amount,
            // (select purchaseorder.pono from purchaseorder where purchaseorder.purchaseOrderId = ordermaster.purchaseOrderId) as pono,
            // (select purchaseorder.poStatus from purchaseorder where purchaseorder.purchaseOrderId = ordermaster.purchaseOrderId) as poStatus,
            // (select vender.venderName from vender where vender.venderId=ordermaster.venderId) as venderName from ordermaster where poStatus!=4 and  isDelete= '0' and istatus= '1' " . $where . "";
            $product = "SELECT ordermaster.orderId,purchaseorder.pono,purchaseorder.date,ordermaster.invoiceNo,ordermaster.invoiceDate,purchaseorder.venderId,purchaseorder.purchaseOrderId,
                           (select venderName from vender where vender.venderId=purchaseorder.venderId) as `venderName`
                            ,(SELECT sum(orderdetails.receivedQty * orderdetails.rate) from orderdetails where orderdetails.orderId= ordermaster.orderId and orderdetails.istatus =1 and orderdetails.isDelete=0) as Amount
          from purchaseorder inner join ordermaster on purchaseorder.purchaseOrderId=ordermaster.purchaseOrderId  " . $where . " and purchaseorder.poStatus='3' and purchaseorder.isDelete= '0' and purchaseorder.istatus= '1' order by purchaseorder.purchaseOrderId desc";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {
                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $data = array(
                        "purchaseOrderId" => $rowproduct['purchaseOrderId'],
                        "orderId" => $rowproduct['orderId'],
                        "invoiceDate" => $rowproduct['invoiceDate'],
                        "date" => $rowproduct['date'],
                        "invoiceNo" => $rowproduct['invoiceNo'],
                        "Amount" => $rowproduct['Amount'],
                        "pono" => $rowproduct['pono'],
                        "venderName" => $rowproduct['venderName'],
                    );
                    //$output['PurchaseOrderList'][] = $rowproduct;
                    $output['PendingVoucherListing'][] = $data;
                }
                $output['message'] = 'Pending Voucher Listing';
                $output['success'] = '1';
            } else {
                //    $output['PurchaseOrderList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['PurchaseOrderList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == 'addinvoucher') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {
            $dataVoucher = array(
                "iVoucherId" => $obj->iVoucherId,
                "iOrderId" => $obj->orderId,
                "iAmount" => $obj->Amount,
                "purchaseOrderId" => $obj->purchaseOrderId,
                "strEntryDate" => date('d-m-Y'),
                "strIP" => $_SERVER['REMOTE_ADDR']
            );
            $dealer = $connect->insertrecord($dbconn, 'VoucherDetails', $dataVoucher);
            
            $quesy = "select iTotalAmount from voucher where id = '$obj->iVoucherId'";
            $resultData = mysqli_query($dbconn, $quesy);
            $rowData = mysqli_fetch_assoc($resultData);
            $iTotalAmount = 0;
            if(isset($rowData['iTotalAmount']) && $rowData['iTotalAmount'] != 0){
                $iTotalAmount = $rowData['iTotalAmount'] + $obj->Amount;
            } else {
                $iTotalAmount += $obj->Amount;
            }
            
            $voucherData = array(
                "iTotalAmount" => $iTotalAmount 
            );
            $whereData = " where id = " . $obj->iVoucherId;
            $dealer = $connect->updaterecord($dbconn, 'voucher', $voucherData, $whereData);
            
            $data = array(
                "paidBy" => $row['userId'],
                "paymentDate" => date('d-m-Y'),
                "poStatus" => 4,
                "strIP" => $_SERVER['REMOTE_ADDR'],
            );
            $where = " where purchaseOrderId = " . $obj->purchaseOrderId;
            $dealer = $connect->updaterecord($dbconn, 'purchaseorder', $data, $where);

            $output['message'] = 'Added Successfully !';
            $output['success'] = '1';
        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == "addinvoucherlisting") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {
            
            $where = " and 1=1 ";
            if (isset($obj->FromDate) && ($obj->FromDate != '')) {
                $where .= " and STR_TO_DATE(voucher.strVoucherDate,'%d-%m-%Y') >= STR_TO_DATE('" . $obj->FromDate . "','%d-%m-%Y')";
            }

            if (isset($obj->ToDate) && ($obj->ToDate != '')) {
                $where .= " and STR_TO_DATE(voucher.strVoucherDate, '%d-%m-%Y')<=STR_TO_DATE('" . $obj->ToDate . "','%d-%m-%Y')";
            }

            if (isset($obj->VenderId) && $obj->VenderId != '0' && $obj->VenderId != '') {
                //$where .= " and voucher.iVenderId = '" . $obj->VenderId . "'";
                $where .= " and purchaseorder.venderId = '" . $obj->VenderId . "'";
            }
            
            //$product = "SELECT VoucherDetails.id,VoucherDetails.strEntryDate,VoucherDetails.iVoucherId,VoucherDetails.iAmount 
            //  from VoucherDetails inner join voucher on voucher.id = VoucherDetails.iVoucherId where VoucherDetails.iVoucherId= " . $obj->iVoucherId . " ".$where." and VoucherDetails.isDelete= '0' and VoucherDetails.iStatus= '1' order by VoucherDetails.id desc";
            $product = "SELECT VoucherDetails.id,VoucherDetails.strEntryDate,VoucherDetails.iVoucherId,VoucherDetails.iAmount,ordermaster.orderId,purchaseorder.pono,purchaseorder.date,ordermaster.invoiceNo,ordermaster.invoiceDate,purchaseorder.venderId,purchaseorder.purchaseOrderId,
                           (select venderName from vender where vender.venderId=purchaseorder.venderId) as `venderName`
                    from purchaseorder inner join ordermaster on purchaseorder.purchaseOrderId=ordermaster.purchaseOrderId INNER join VoucherDetails on VoucherDetails.iOrderId=ordermaster.orderId 
                    where VoucherDetails.iVoucherId= " . $obj->iVoucherId . " ".$where."  and purchaseorder.poStatus='4' and purchaseorder.isDelete= '0' and purchaseorder.istatus= '1' order by purchaseorder.purchaseOrderId desc";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {
                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $data = array(
                        "purchaseOrderId" => $rowproduct['purchaseOrderId'],
                        "orderId" => $rowproduct['orderId'],
                        "invoiceDate" => $rowproduct['invoiceDate'],
                        "date" => $rowproduct['date'],
                        "invoiceNo" => $rowproduct['invoiceNo'],
                        "iAmount" => $rowproduct['iAmount'],
                        "pono" => $rowproduct['pono'],
                        "venderName" => $rowproduct['venderName'],
                        "iVoucherId" => $rowproduct['iVoucherId'],
                        "iVoucherDetailsId" => $rowproduct['id'],
                        "strEntryDate" => $rowproduct['strEntryDate'],
                    );
                    // $data = array(
                    //     "iVoucherDetailsId" => $rowproduct['id'],
                    //     "strEntryDate" => $rowproduct['strEntryDate'],
                    //     "iVoucherId" => $rowproduct['iVoucherId'],
                    //     "iAmount" => $rowproduct['iAmount']
                    // );
                    //$output['PurchaseOrderList'][] = $rowproduct;
                    $output['AddInVoucherListing'][] = $data;
                }
                $productData = "SELECT iTotalAmount from voucher where id= " . $obj->iVoucherId . " and isDelete= '0' and iStatus= '1' order by id desc";
                $productRes = mysqli_query($dbconn, $productData);
                $productrow = mysqli_fetch_assoc($productRes);
                $output['iTotalAmount'] = $productrow['iTotalAmount'];
                $output['message'] = 'Add In Voucher Listing';
                $output['success'] = '1';
            } else {
                //    $output['PurchaseOrderList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['PurchaseOrderList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
}
else if ($actions == "removevoucher") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {
            
            $product = "SELECT * from VoucherDetails where id= " . $obj->iVoucherDetailsId . " and isDelete= '0' and iStatus= '1' order by id desc";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) == 1) {
                $rowproduct = mysqli_fetch_assoc($product);
                $data = array(
                    "paidBy" => 0,
                    "paymentDate" => NULL,
                    "poStatus" => 3
                );
                $where = " where purchaseOrderId = " . $rowproduct['purchaseOrderId'];
                $dealer = $connect->updaterecord($dbconn, 'purchaseorder', $data, $where);
                
                $iVoucherId = $rowproduct['iVoucherId'];
                
                $productData = "delete from VoucherDetails where id= " . $obj->iVoucherDetailsId . "";
                $productRes = mysqli_query($dbconn, $productData);
                
                $quesy = "select sum(iAmount) as iTotalAmount from VoucherDetails where iVoucherId = '$iVoucherId'";
                $resultData = mysqli_query($dbconn, $quesy);
                $rowData = mysqli_fetch_assoc($resultData);
                
                $quesyNew = "update voucher set iTotalAmount='".$rowData['iTotalAmount']."' where id = '$iVoucherId'";
                $resultData = mysqli_query($dbconn, $quesyNew);
                
                $output['message'] = 'Deleted Successfully';
                $output['success'] = '1';
            } else {
                //    $output['PurchaseOrderList'][] = "";
                $output['message'] = 'Not Data Found';
                $output["success"] = '0';
            }
        } else {
            // $output['PurchaseOrderList'][] = "";
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
}
 else if ($actions == 'editvoucher') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {
            $queryvoucher = "SELECT * FROM `voucher` where strVoucherNo like '%".trim($obj->strVoucherNo)."%'";
            $resultvoucherData = mysqli_query($dbconn, $queryvoucher);
            if (mysqli_num_rows($resultvoucherData) > 0) {
                $output['message'] = 'Voucher Number already exist.';
                $output['success'] = '0';
            } else{
                $query = "SELECT * FROM `vender` where venderId='$obj->iVenderId'";
                $resultData = mysqli_query($dbconn, $query);
                $rowData = mysqli_fetch_assoc($resultData);
                $data = array(
                    "strVoucherNo" => $obj->strVoucherNo,
                    "strVoucherDate" => date('d-m-Y',strtotime($obj->strVoucherDate)),
                    "iVenderId" => $obj->iVenderId,
                    "strVoucherName" => $rowData['venderName'],
                    "iEntryBy" => $row['userId'],
                    "strEntryDate" => date('d-m-Y H:i:s'),
                    "strIP" => $_SERVER['REMOTE_ADDR'],
                );
                $where = " where id = " . $obj->iVoucherId;
                $dealer = $connect->updaterecord($dbconn, 'voucher', $data, $where);
                
                $output['iVoucherId'] = $obj->iVoucherId;
                $output['message'] = 'Updated Successfully !';
                $output['success'] = '1';    
            }
            
        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == 'deletevoucher') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {
            $query = "Delete FROM `voucher` where id='$obj->iVoucherId'";
            $resultData = mysqli_query($dbconn, $query);
            
            $queryVoucherDetails = "select *  FROM `VoucherDetails` where iVoucherId='$obj->iVoucherId'";
            $result_Data = mysqli_query($dbconn, $queryVoucherDetails);
            while ($row_product = mysqli_fetch_assoc($result_Data)) {
                $data = array(
                    "paidBy" => 0,
                    "paymentDate" => NULL,
                    "poStatus" => 3
                );
                $where = " where purchaseOrderId = " . $row_product['purchaseOrderId'];
                $dealer = $connect->updaterecord($dbconn, 'purchaseorder', $data, $where);
                
                $productData = "delete from VoucherDetails where id= " . $row_product['id'] . "";
                $productRes = mysqli_query($dbconn, $productData);
            }
            
            $output['message'] = 'Deleted Successfully !';
            $output['success'] = '1';

        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == 'finalsubmit') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {
            $queryVoucherDetails = "select count(*) as TotalRow  FROM `VoucherDetails` where iVoucherId='$obj->iVoucherId'";
            $result_Data = mysqli_query($dbconn, $queryVoucherDetails);
            $rowData = mysqli_fetch_assoc($result_Data);
            if($rowData['TotalRow'] > 0){
                $data = array(
                    "isVoucherLock" => 1
                );
                $where = " where id = " . $obj->iVoucherId;
                $dealer = $connect->updaterecord($dbconn, 'voucher', $data, $where);
                
                $output['message'] = 'Added Successfully !';
                $output['success'] = '1';
            } else{
                $output['message'] = 'Add atlest one voucher.';
                $output['success'] = '0';    
            }

        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
}
else if ($actions == 'unlockvoucher') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo' and type='1'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {
            $data = array(
                "isVoucherLock" => 0
            );
            $where = " where id = " . $obj->iVoucherId;
            $dealer = $connect->updaterecord($dbconn, 'voucher', $data, $where);
            
            $output['message'] = 'Unlock Voucher Successfully !';
            $output['success'] = '1';

        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == 'voucherpdf') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {
            
            $productData = "SELECT strVoucherNo,strVoucherDate,vender.venderName,voucher.iTotalAmount from voucher inner join vender on vender.venderId=voucher.iVenderId where id= " . $obj->iVoucherId . " and voucher.isDelete= '0' and voucher.iStatus= '1' and isVoucherLock=1"; 
            $productRes = mysqli_query($dbconn, $productData);
            $productrow = mysqli_fetch_assoc($productRes);
           
            $logistic_Data_Tr = "";
            $mailFormat="";
               // $resrowc = mysqli_fetch_array($resrowcount);
            //    $mailFormat = file_get_contents($web_url."/admin/memo.html");
            $file = file_get_contents($web_url."/vrajinvoice.html");
            $file = str_replace("#VoucherNo#", ucfirst($productrow['strVoucherNo']), $file);
            $file = str_replace("#VoucherDate#", ucfirst($productrow['strVoucherDate']), $file);
            $file = str_replace("#PartyName#", ucfirst(urldecode($productrow['venderName'])), $file);
            $file = str_replace("#TotalAmount#", ucfirst($productrow['iTotalAmount']), $file);
            
            $filterstr = "SELECT VoucherDetails.iVoucherId,VoucherDetails.iAmount,ordermaster.invoiceNo,ordermaster.invoiceDate FROM `VoucherDetails` inner join ordermaster on VoucherDetails.iOrderId=ordermaster.orderId where iVoucherId= " . $obj->iVoucherId . " and ordermaster.isDelete= '0' and ordermaster.istatus= '1'";
            $resrowcount = mysqli_query($dbconn, $filterstr);
            $iCounter = 1;
            
            while ($resrowc = mysqli_fetch_array($resrowcount)){
                $invoiceNo = $resrowc['invoiceNo'] ." ". $resrowc['invoiceDate'];
                $mailFormat =  file_get_contents($web_url."/vrajinvoicedetails.html", "r");
                $mailFormat = str_replace("#SrNo#", ucfirst($iCounter), $mailFormat);
                $mailFormat = str_replace("#InvoiceNo#", ucfirst($invoiceNo), $mailFormat);
                $mailFormat = str_replace("#Amount#", ucfirst($resrowc['iAmount']), $mailFormat);
                
                $mailFormat = str_replace("#web_url#", ucfirst(urldecode($web_url)), $mailFormat);
                $logistic_Data_Tr = $logistic_Data_Tr . $mailFormat;
                $iCounter++;
            }
            $file = str_replace("#AddDetail2#", ucfirst(urldecode($logistic_Data_Tr)), $file);
            //  echo $file;
            // exit;
            
            // require_once('../tcpdf/config/tcpdf_config.php');
            // require_once('../tcpdf/tcpdf.php');
            require_once('../TCPDF-main/config/tcpdf_config.php');
            require_once('../TCPDF-main/tcpdf.php');
            
            //$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, 'UTF-8', false);
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetMargins(PDF_MARGIN_LEFT, 10, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->AddPage('P', 'A4');
          
            $pdf->setPage($pdf->getPage());
            $pdf->writeHTML($file, true, false, false, false, '');
            ob_end_clean();
         

            //$filename="Voucher.pdf"; 
            $filename= $productrow['venderName']."_".$productrow['strVoucherNo'].".pdf"; 

            $filelocation =  $web_url."webservies/Voucher";
            
        //   if (substr($filelocation, strlen($filelocation) - 1, 1) != '/') {
        //         $filelocation .= '/';
        //     }
        //     $files = glob($filelocation . '*', GLOB_MARK);
        //     foreach ($files as $file) {
        //         if (is_dir($file)) {
        //             self::deleteDir($file);
        //         } else {
        //             unlink($file);
        //         }
        //     }
        //     rmdir($filelocation);
            
            if(!file_exists($filelocation)){
                echo "file create";
                //mkdir(__DIR__ .'/Voucher', 0777, true);
                mkdir($filelocation,0777, true);
            } else{
                echo "file create";
            }
            
          
            ob_clean();
            $fileNL = $filelocation."/".$filename;

            $pdf->Output(__DIR__ .'/Voucher/'.$filename, 'F');
            
            $output['downloadvoucher'] = $fileNL;
            $output['message'] = "Voucher Download Succesfully";
            $output['success'] = '1';
            
        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
} else if ($actions == 'paidallpending') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {
            $where = " and 1=1 ";
            if (isset($obj->FromDate) && ($obj->FromDate != '')) {
                $where .= " and STR_TO_DATE(ordermaster.strEntryDate,'%d-%m-%Y') >= STR_TO_DATE('" . $obj->FromDate . "','%d-%m-%Y')";
            }
            if (isset($obj->ToDate) && ($obj->ToDate != '')) {
                $where .= " and STR_TO_DATE(ordermaster.strEntryDate, '%d-%m-%Y')<=STR_TO_DATE('" . $obj->ToDate . "','%d-%m-%Y')";
            }
            if (isset($obj->VenderId) && $obj->VenderId != '0' && $obj->VenderId != '') {
                $where .= " and ordermaster.venderId = '" . $obj->VenderId . "'";
            }
            
            $product = "SELECT ordermaster.orderId,purchaseorder.pono,purchaseorder.date,ordermaster.invoiceNo,ordermaster.invoiceDate,purchaseorder.venderId,purchaseorder.purchaseOrderId,
                    (select venderName from vender where vender.venderId=purchaseorder.venderId) as `venderName`
                    ,(SELECT sum(orderdetails.receivedQty * orderdetails.rate) from orderdetails where orderdetails.orderId= ordermaster.orderId and orderdetails.istatus =1 and orderdetails.isDelete=0) as Amount
                    from purchaseorder inner join ordermaster on purchaseorder.purchaseOrderId=ordermaster.purchaseOrderId  " . $where . " and purchaseorder.poStatus='3' and purchaseorder.isDelete= '0' and purchaseorder.istatus= '1' order by purchaseorder.purchaseOrderId desc";
            $product = mysqli_query($dbconn, $product);
            if (mysqli_num_rows($product) > 0) {
                while ($rowproduct = mysqli_fetch_assoc($product)) {
                    $dataVoucher = array(
                        "iVoucherId" => $obj->iVoucherId,
                        "iOrderId" => $rowproduct['orderId'],
                        "iAmount" => $rowproduct['Amount'],
                        "purchaseOrderId" => $rowproduct['purchaseOrderId'],
                        "strEntryDate" => date('d-m-Y'),
                        "strIP" => $_SERVER['REMOTE_ADDR']
                    );
                    $dealer = $connect->insertrecord($dbconn, 'VoucherDetails', $dataVoucher);
                    
                    $quesy = "select iTotalAmount from voucher where id = '$obj->iVoucherId'";
                    $resultData = mysqli_query($dbconn, $quesy);
                    $rowData = mysqli_fetch_assoc($resultData);
                    $iTotalAmount = 0;
                    if(isset($rowData['iTotalAmount']) && $rowData['iTotalAmount'] != 0){
                        $iTotalAmount = $rowData['iTotalAmount'] + $rowproduct['Amount'];
                    } else {
                        $iTotalAmount += $rowproduct['Amount'];
                    }
                    
                    $voucherData = array(
                        "iTotalAmount" => $iTotalAmount 
                    );
                    $whereData = " where id = " . $obj->iVoucherId;
                    $dealer = $connect->updaterecord($dbconn, 'voucher', $voucherData, $whereData);
                    
                    $data = array(
                        "paidBy" => $row['userId'],
                        "paymentDate" => date('d-m-Y'),
                        "poStatus" => 4,
                        "strIP" => $_SERVER['REMOTE_ADDR'],
                    );
                    $where = " where purchaseOrderId = " . $rowproduct['purchaseOrderId'];
                    $dealer = $connect->updaterecord($dbconn, 'purchaseorder', $data, $where);
                }
                $output['message'] = 'Added Successfully !';
                $output['success'] = '1';
            } else {
                $output['message'] = 'No Data Found!';
                $output['success'] = '0';    
            }
        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {

        $output['message'] = 'User not';
        $output['success'] = '0';
    }
}
else if ($actions == "deleteofadminpurchaseorder") {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {
            
            $where = ' where purchaseOrderId=' . $obj->purchaseOrderId;
            $dealer = $connect->deleterecord($dbconn, 'purchaseorderdetail', $where);
            
            
            $where = ' where purchaseOrderId=' . $obj->purchaseOrderId;
            $dealer = $connect->deleterecord($dbconn, 'purchaseorder', $where);
            
            $output['message'] = 'delete successfully !';
            $output['success'] = '1';

        } else {

            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    }
} else if ($actions == 'deleteotherstaff') {

    $request_body = @file_get_contents('php://input');
    $obj = json_decode($request_body);

    $sql = "select userId,mobile,password,salt from user where mobile = '$obj->MobileNo' and type='1'";
    $result = mysqli_query($dbconn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $good_hash = PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $row['salt'] . ":" . $row['password'];

        if (validate_password($obj->Password, $good_hash)) {

            $data = array(
                "isDelete" => 1,
                "strEntryDate" => date('d-m-Y H:i:s'),
                "strIP" => $_SERVER['REMOTE_ADDR'],

            );
            $where = " where userId = " . $obj->userId;
            $dealer = $connect->updaterecord($dbconn, 'user', $data, $where);

            $output['message'] = 'Delete Successfully !';
            $output['success'] = '1';
        } else {
            $output['message'] = 'Invalid Request !';
            $output['success'] = '0';
        }
    } else {
        $output['message'] = 'User not';
        $output['success'] = '0';
    }
}


print(json_encode($output));




