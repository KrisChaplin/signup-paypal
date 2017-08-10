<?php
/*
 *********************************************************************************************************
 * daloRADIUS - RADIUS Web Platform
 * Copyright (C) 2007 - Liran Tal <liran@enginx.com> All Rights Reserved.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 *********************************************************************************************************
 *
 * Authors:     Liran Tal <liran@enginx.com>
 *
 * Credits to the implementation of captcha are due to G.Sujith Kumar of codewalkers
 *
 *********************************************************************************************************
 */


        include('library/config_read.php');

        $successMsg = $configValues['CONFIG_MERCHANT_SUCCESS_MSG_PRE'];

        $refresh = true;

        if (isset($_GET['txnId'])) {
                // txnId variable is set, let's check it against the database

                include('library/opendb.php');

                $txnId = $_GET['txnId'];
				//$username = $_GET['username'];
				
				$sql = "SELECT txnId, username, payment_status FROM ".$configValues['CONFIG_DB_TBL_DALOBILLINGMERCHANT'].
                        " WHERE txnId='".$dbSocket->escapeSimple($txnId)."' AND payment_status != ''";
				$res = $dbSocket->query($sql);
                $row = $res->fetchRow(DB_FETCHMODE_ASSOC);
				

                if ( ($row['txnId'] == $txnId) && ($row['payment_status'] == "Completed") ) {
						$successMsg = "We have successfully validated your payment<br/>";
                        $successMsg .= "Your user PIN is:<br/>";
						$successMsg .= "<b>".$row['username']."</b>";
						$successMsg .= "<br/><br/>".$configValues['CONFIG_MERCHANT_SUCCESS_MSG_POST']."<br/><br/>";
						$successMsg .= "Click <a href='http://10.5.50.1/login?user=$row[username]'>here</a> to return to the Login page";
$successMsg .= '<form name="sendin" action="http://10.5.50.1/login" method="post">'; 
$successMsg .= '<input type="hidden" name="username" value="'.$row[username].'"/>';
$successMsg .= '<input type="hidden" name="password" value="" />';
$successMsg .= '<input type="hidden" name="dst" value="" />';
$successMsg .= '<input type="hidden" name="popup" value="true" />';
$successMsg .= '</form>';
$successMsg .= '<script type="text/javascript" src="md5.js"></script>';
$successMsg .= '<script language="JavaScript">';
$successMsg .= 'document.sendin.password.value = hexMD5(\'\303\' + \'\335\043\010\252\135\032\106\221\215\210\064\224\071\372\250\273\');';
$successMsg .= 'document.sendin.submit();'; 
$successMsg .= '</script>';
        

                $refresh = false;
                }

                include('library/closedb.php');

        }

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php
        if ($refresh == true)
                echo '<meta http-equiv="refresh" content="5">';
?>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>User Sign-Up</title>
<style type="text/css">

body {
color: #000000; font-size: 20px; font-family: verdana;
}
</style>

</head>
<body>
<table width="400" align="center" bgcolor="#ccccff" style="border: 1px solid #cccccc; padding: 0px;">

<tr><td align="center"><img src="./images/logo.jpg" alt="welshwifi.net" /></td></tr>
<tr><td align="center"></td></tr>
</table>                
                        <center>

        <?php
                echo "<font color='blue'><b>".$configValues['CONFIG_MERCHANT_SUCCESS_MSG_HEADER']."</b></font>";
                echo $successMsg;
        ?>

                        </center>
                </p>

</body>
</html>

