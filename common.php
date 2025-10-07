<?php
error_reporting(E_ALL);
require_once('config.php');


class connect {

    public $conn;
//public function __construct($dbServer,$dbUser,$dbPass,$dbName)
//  {
//      $this->conn = mysql_connect($dbServer,$dbUser,$dbPass) or die("Couldn't connection to $host");
//      mysql_select_db($dbName,$this->conn);
//  }
//  public function destruct(){
//      mysql_close($this->conn);
//  }

    var $pages;   // Total number of pages required
    var $openPage;  // currently opened page

    function BindDropDown($result, $text, $value, $select = '', $opt) {
        if ($select != '') {
            echo "<option value=''>{$select}</option>";
        }
        while ($row = mysqli_fetch_array($result)) {
            if ($opt == $row[$value])
                echo "<option value='{$row[$value]}' selected='selected'>{$row[$text]}</option> ";
            else
                echo "<option value='{$row[$value]}'>{$row[$text]}</option> ";
        }
    }

//      
    function htmltodb($date) {
        $date = str_replace('/', '-', $date);
        return date('Y-m-d', strtotime($date));
    }

//   

    function dbtohtml($date) {
        $date = str_replace('-', '/', $date);
        return date('d/m/Y', strtotime($date));
    }

//   
     function deleterecord($dbconn,$table, $where = '') {
        $query = 'delete from ' . $table . " " . $where;


        return $this->GenQuery($dbconn,$query);
    }

//   function checkid($query)
//      {//exit;
//          
//          //echo $query;
//          //$this->connection();
//          $result = mysqli_query($dbconn,$query) or die(mysqli_connect_error());
//          $id=mysqli_fetch_array($result);
//          return $id['id'];
//      }
//          


    function GenQuery($dbconn,$query) {

        $result = mysqli_query($dbconn,$query) or die(mysqli_connect_error());
        return $result;
    }

    function Gridbind($dbconn,$table, $where = '', $order = '') {
        $query = 'select * from ' . $table . " " . $where . " " . $order;

        return $this->GenQuery($dbconn,$query);
    }

    function Gridbindparam($dbconn,$table, $param, $where = '', $order = '') {
        //echo $query;
        //exit;
        $query = 'select ' . $param . ' from ' . $table . " " . $where . " " . $order;
        return $this->GenQuery($dbconn,$query);
    }


    function image_uploadfile($file, $path) {
        //$filename = $file['name'];
        $allowedExts = array("gif", "jpeg", "jpg", "png");
        $temp = explode(".", strtolower($file["name"]));
        $extension = end($temp);
        $filename = $file["name"];
        echo $path = $path . $filename;
        if (in_array($extension, $allowedExts)) {
            if (file_exists($path)) {
                unlink($path);
            }
            if (move_uploaded_file($file['tmp_name'], $path)) {
                $msg = '';
            } else {
                $msg = 'error in upload photo';
            }
        } else {
            $msg = 'invalid file type';
        }
        return $msg;
    }

    function uploadfile($file, $path) {
        //$filename = $file['name'];
        if (file_exists($path)) {
            unlink($path);
        }
        if (move_uploaded_file($file, $path)) {
            $msg = 'file uploded';
        } else {
            $msg = 'error in upload photo';
        }
        return $msg;
    }

    function trackrecord($dbconn,$url, $action) {
//                    $url = $_SERVER['REQUEST_URI'];
//                    $action = 'login';
        {
            $mid = $_SESSION['Mann_App_User'];
            $strIp = $_SERVER['REMOTE_ADDR'];
            $strEntryDate = date('Y-m-d H:i:s');
        }
        $query = "insert into track(imemberId,strIp,strEntryDate,url,action)values('" . $mid . "','" . $strIp . "','" . $strEntryDate . "','" . $url . "','" . $action . "')";
        mysqli_query($dbconn,$query);
    }

    function insertrecord($dbconn,$table, $data) {
        //print_r($data);
        $fields = '';
        $detail = "";
        foreach ($data as $key => $value) {
             $value =strval(  $value ) ;
            if ($value != 'Save') {
                $fields.=$key . ",";
                $value = $this->Strsinglequate_replace(trim($value));
                $value = trim($value);
                $detail.="'" . $value . "',";
            }
        }

        $fields = substr($fields, 0, strrpos($fields, ","));
        $detail = substr($detail, 0, strrpos($detail, ","));

        //exit;
        $query = "insert into " . $table . " (" . $fields . ") values (" . $detail . ")";
        $result = mysqli_query($dbconn,$query) or die(mysqli_connect_error($dbconn));
        $id = mysqli_insert_id($dbconn);
        return $id;
    }

    function updaterecord($dbconn,$table, $data, $where) {
        $detail = "";

        foreach ($data as $key => $value) {
             $value =strval(  $value ) ;
            if ($value != 'Update') {
                $value = $this->Strsinglequate_replace($value);
                $value = trim($value);
                $detail.=$key . "='" . $value . "',";
            }
        }
        $detail = substr($detail, 0, strrpos($detail, ","));
     $query = "update " . $table . " set " . $detail . " " . $where;
        $result = mysqli_query($dbconn,$query) or die(mysqli_connect_error($dbconn));
        $id = mysqli_affected_rows($dbconn);
        return $id;
    }

    function Strsinglequate_replace($strvalue) {
        return str_replace("'", "''", $strvalue);
    }
    

//   //pagination
    function createPaging($dbconn,$table, $where, $resultPerPage, $order = '') {
        $query = 'select * from ' . $table . " " . $where . " " . $order;
        //echo $query;
        //exit;
        $fullresult = $this->Gridbind($table, $where);
        $totalresult = mysqli_num_rows($fullresult);
        $this->pages = $this->findPages($totalresult, $resultPerPage);
        if (isset($_GET['pno']) && isset($_GET['pno']) > 0) {
            $this->openPage = $_GET['pno'];
            if ($this->openPage > $this->pages) {
                $this->pages = 1;
            }
            $start = $this->openPage * $resultPerPage - $resultPerPage;
            $end = $resultPerPage;
            $query.= " LIMIT $start,$end";
        } elseif (isset($_GET['pno']) > $this->pages) {
            $start = $this->pages;
            $end = $resultPerPage;
            $query.= " LIMIT $start,$end";
        } else {
            $this->openPage = 1;
            $query .= " LIMIT 0,$resultPerPage";
        }
        //  echo $query;
        $resultpage = mysqli_query($dbconn,$query);
        return $resultpage;
    }

///*
//function to calculate the total number of pages required
//@param - Total number of records available
//@param - Result per page
//*/
    function findPages($total, $perpage) {
        $this->pages = intval($total / $perpage);
        if ($total % $perpage > 0)
            $this->pages++;
        return $this->pages;
    }

//  
///*
//function to display the pagination
//*/


     function fetchData($dbconn,$table, $where) {
        $query = "select * from " . $table . " where " . $where;
        $result = mysqli_query($dbconn,$query) or die(mysqli_connect_error($dbconn));

        return mysqli_fetch_array($result);
    }

    function displayPaging($qry_str = '') {
        if (isset($_GET['page'])) {
            $self = $_SERVER['PHP_SELF'] . '?page=' . $_GET['page'];
        } else {
            $self = $_SERVER['PHP_SELF'] . '?1=1';
        }
        $self = $self . $qry_str;
        if ($this->openPage <= 0) {
            $next = 2;
        } else {
            $next = $this->openPage + 1;
        }

        $prev = $this->openPage - 1;
        $last = $this->pages;

        if ($this->openPage > 1) {
            echo "<a href=$self&pno=1>First</a>&nbsp&nbsp;";
            echo "<a href=$self&pno=$prev>Prev</a>&nbsp&nbsp;";
        } else {
            echo "First&nbsp&nbsp;";
            echo "Prev&nbsp&nbsp;";
        }
        /* for($i=1;$i<=$this->pages;$i++) {
          if($i == $this->openPage)
          echo "$i&nbsp&nbsp;";
          else
          echo "<a href=$self&pno=$i>$i</a>&nbsp&nbsp;";
          } */
        if ($this->openPage < $this->pages) {
            echo "<a href=$self&pno=$next>Next</a>&nbsp&nbsp;";
            echo "<a href=$self&pno=$last>Last</a>&nbsp&nbsp;";
        } else {
            echo "Next&nbsp&nbsp;";
            echo "Last&nbsp&nbsp;";
        }
    }

    function ProductdisplayPaging($qry_str = '') {

        $self = $_SERVER['PHP_SELF'] . $qry_str;

        if ($this->openPage <= 0) {
            $next = 2;
        } else {
            $next = $this->openPage + 1;
        }

        $prev = $this->openPage - 1;
        $last = $this->pages;
        echo '<ul class="pagination">';
        for ($i = 1; $i <= $this->pages; $i++) {
            if ($i == $this->openPage)
                echo " <li class='active'><a href=#>$i&nbsp&nbsp;</a></li>";
            else
                echo "<li><a href=$self&pno=$i>$i</a>&nbsp&nbsp;</li>";
        }

        echo '<ul>';
    }

    function feedbackdisplayPaging($qry_str = '') {

        $self = $_SERVER['PHP_SELF'] . $qry_str;

        if ($this->openPage <= 0) {
            $next = 2;
        } else {
            $next = $this->openPage + 1;
        }

        $prev = $this->openPage - 1;
        $last = $this->pages;
        echo '<ul class="pagination" style="margin-left:35px">';
        for ($i = 1; $i <= $this->pages; $i++) {
            if ($i == $this->openPage)
                echo " <li class='active'><a href=#>$i&nbsp&nbsp;</a></li>";
            else
                echo "<li><a href=$self&pno=$i>$i</a>&nbsp&nbsp;</li>";
        }

        echo '</ul>';
    }

    function sendmail($detail, $giveorder, $sub = '', $mailHost, $mailFrom, $mailFromName, $mailSMTPSecure, $mailAddReplyTo, $mailUsername, $mailPassword) {
 
        
        $mail = new PHPMailer();
        
        try {
            $mail->IsSMTP();
            $mail->Host = $mailHost;
            $mail->SMTPAuth = true;
            $mail->Username = $mailUsername;
            $mail->Password = $mailPassword;
            $mail->SMTPSecure = $mailSMTPSecure;                            // Enable TLS encryption, `ssl` also accepted
            $mail->Port = 587;

            $mail->From = $mailFrom;
            $mail->FromName = $mailFromName;
            $mail->AddReplyTo($mailAddReplyTo);
            
            $emailids = explode(',', $giveorder);
            
            foreach ($emailids as $key => $value) {
                $mail->AddAddress($value);
            }
            
            
            $mail->IsHTML(true);
            $mail->Subject = $sub;
            $mail->Body = $detail;
            
            
//echo $detail;
//exit;
//print_r($mail);
            $res_ofmail = $mail->Send();
        //var_dump($res_ofmail);
        } catch (phpmailerException $e) {
            // $e->errorMessage(); //Pretty error messages from PHPMailer
        } catch (Exception $e) {
            //echo $e->getMessage(); //Boring error messages from anything else!
        }
    }

//function sendsms($MobileNo,$message)
//{
// 
//echo  $data  = 'http://transms.apolloinfotech.com/sendsms.jsp?user=galaxy&password=Abcd@123&mobiles='.$MobileNo.'&sms='.$message. '&senderid=galaxy';
//
//  $ch = curl_init($data);
//  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//  $response = curl_exec($ch);
//  curl_close($ch);
//  
//  // Process your response here
//  echo $response;
//
////exit;
//}

     function sendsms($mobileNo, $message) {
        
         //$data = 'http://ip.shreesms.net/smsserver/SMS10N.aspx?Userid=CRRYKT&UserPassword=crrykt@123@&PhoneNumber=91'. $mobileNo .'&Text='. urlencode($message) .'&GSM=KBCRRY';
         $data = 'http://ip.shreesms.net/smsserver/SMS10N.aspx?Userid=bookmyhometuition&UserPassword=hometuition@123&PhoneNumber=91'. $mobileNo .'&Text='. urlencode($message) .'&GSM=BKMYHT';
     

        $ret = file_get_contents($data);
        return $ret;
    }

    function sendcustomsms($mobileNo, $message) {

        $data = 'http://ip.shreesms.net/smsserver/SMS10N.aspx?Userid=MagicGroth&UserPassword=Groth@123@#&PhoneNumber=91' . $mobileNo . '&Text=' . urlencode($message) . '&GSM=MGROTH';

        $ret = file_get_contents($data);
        return $ret;
    }

    function updProQty($dbconn, $om_id) {



        $res_od = mysqli_query($dbconn,"select * from orderdetail where omid=" . $om_id);
        while ($row_od = mysqli_fetch_array($res_od)) {

            $row_odetail = mysqli_query($dbconn,"update product set avail_stock=(avail_stock-" . $row_od['qty'] . ") where pid=" . $row_od['pid']);
            $row_pro = mysqli_fetch_array(mysqli_query($dbconn,"select * from product where pid=" . $row_od['pid']));

            if ($row_pro['avail_stock'] < $row_pro['stock_limit']) {
                $sms_txt = "Your Product :" . $row_pro['pName'] . "(" . $row_pro['pCode'] . ") is now running out of stock.";
                $this->sendsms("9824112793", $sms_txt);
                //$this->sendsms("9427534693",$sms_txt);
            }
            if ($row_pro['avail_stock'] == 0) {
                $row_odetail = mysqli_query($dbconn,"update product set out_of_stock=1 where pid=" . $row_od['pid']);
            }
        }
    }

    function create_hash($password) {
        // format: algorithm:iterations:salt:hash
        $saltBytes = random_bytes(48);
    $PassSalt = bin2hex($saltBytes);
    $salt = base64_encode($PassSalt);   
        //$salt = base64_encode(mcrypt_create_iv(PBKDF2_SALT_BYTES, MCRYPT_DEV_URANDOM));
        return PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" . $salt .
                ":" .
                base64_encode($this->pbkdf2(
                                PBKDF2_HASH_ALGORITHM, $password, $salt, PBKDF2_ITERATIONS, PBKDF2_HASH_BYTES, true
        ));
    }
     function validate_password($password, $good_hash) {
        $params = explode(":", $good_hash);
        if (count($params) < HASH_SECTIONS)
            return false;
        $pbkdf2 = base64_decode($params[HASH_PBKDF2_INDEX]);
        return $this->slow_equals(
                        $pbkdf2, $this->pbkdf2(
                                $params[HASH_ALGORITHM_INDEX], $password, $params[HASH_SALT_INDEX], (int) $params[HASH_ITERATION_INDEX], strlen($pbkdf2), true
                        )
        );
    }

    function pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output = false) {
        $algorithm = strtolower($algorithm);
        if (!in_array($algorithm, hash_algos(), true))
            die('PBKDF2 ERROR: Invalid hash algorithm.');
        if ($count <= 0 || $key_length <= 0)
            die('PBKDF2 ERROR: Invalid parameters.');

        $hash_length = strlen(hash($algorithm, "", true));
        $block_count = ceil($key_length / $hash_length);

        $output = "";
        for ($i = 1; $i <= $block_count; $i++) {
            // $i encoded as 4 bytes, big endian.
            $last = $salt . pack("N", $i);
            // first iteration
            $last = $xorsum = hash_hmac($algorithm, $last, $password, true);
            // perform the other $count - 1 iterations
            for ($j = 1; $j < $count; $j++) {
                $xorsum ^= ($last = hash_hmac($algorithm, $last, $password, true));
            }
            $output .= $xorsum;
        }

        if ($raw_output)
            return substr($output, 0, $key_length);
        else
            return bin2hex(substr($output, 0, $key_length));
    }

    function slow_equals($a, $b) {
        $diff = strlen($a) ^ strlen($b);
        for ($i = 0; $i < strlen($a) && $i < strlen($b); $i++) {
            $diff |= ord($a[$i]) ^ ord($b[$i]);
        }
        return $diff === 0;
    }

    function generatePassword() {
        $chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $res = "";
        for ($i = 0; $i < 6; $i++) {
            $res .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $res;
    }
    
    
    public function sendWhatsappMessage($mobile,$key,$msg,$pdf) {
	   //if(!empty($pdf)){
		  //  $data = "http://api.bulkcampaigns.com/api/wapi?json=true&apikey=".$key."&mobile=".$mobile."&msg=".urlencode($msg)."&pdf=".$pdf;
	   //}else{
		    $data = "http://api.bulkcampaigns.com/api/wapi?json=true&apikey=".$key."&mobile=".$mobile."&msg=".urlencode($msg);
	   //}
	  
        $ret = file_get_contents($data);
		$result = json_decode($ret);
		
        return $result;
	}


}

//$connect = new dboperation($dbServer,$dbUser,$dbPass,$dbName);
//define( "DBH", $connect->conn );  
?>
