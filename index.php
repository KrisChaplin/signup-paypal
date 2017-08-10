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
 *currency_code
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

	include_once('include/common/common.php');
	include_once('library/config_read.php');
	$txnId = createPassword(64, $configValues['CONFIG_USER_ALLOWEDRANDOMCHARS']); 
                   // to be used for setting up the return url (success.php page)
				   // for later retreiving of the transaction details

	$status = "firstload";
	$errorMissingFields = false;
	$userPIN = "";

	if (isset($_POST['submit'])) {

		(isset($_POST['firstName'])) ? $firstName = $_POST['firstName'] : $firstName = "";
		(isset($_POST['lastName'])) ? $lastName = $_POST['lastName'] : $lastName =  "";
		(isset($_POST['address'])) ? $address = $_POST['address'] : $address = "";
		(isset($_POST['city'])) ? $city = $_POST['city'] : $city = "";
		(isset($_POST['state'])) ? $state = $_POST['state'] : $state = "";
		(isset($_POST['planId'])) ? $planId = $_POST['planId'] : $planId = "";

		if ( ($firstName != "") && ($lastName != "") && ($address != "") && ($city != "") && ($state != "") && ($planId != "") ) {

			// all paramteres have been set, save it in the database
			include('library/opendb.php');

			$currDate = date('Y-m-d H:i:s');
			$currBy = "paypal-webinterface";

			// lets create some random data for user pin
			while (true) {
				
				// generate the pin for the user
				$userPIN = createPassword($configValues['CONFIG_USERNAME_LENGTH'], $configValues['CONFIG_USER_ALLOWEDRANDOMCHARS']);
				
				// check if this pin, although random, may be used before
				$sql = "SELECT * FROM ".$configValues['CONFIG_DB_TBL_RADCHECK']." WHERE UserName='".
					$dbSocket->escapeSimple($userPIN)."'";
				$res = $dbSocket->query($sql);
	
				// if it wasn't used then we break out of the loop, otherwise we continue to 
				// generate pins and check them
				if ($res->numRows() == 0)
					break;
			}
			
			
			$planId = $dbSocket->escapeSimple($planId);

			// grab information about a plan from the table
			$sql = "SELECT id AS planId,planName,planCost,planTax,planCurrency,planRecurring,planRecurringPeriod FROM ".$configValues['CONFIG_DB_TBL_DALOBILLINGPLANS'].
					" WHERE (planType='PayPal') AND (id=$planId) ";
			$res = $dbSocket->query($sql);
			$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
			$planId = $row['planId'];
			$planName = $row['planName'];
			$planCost = $row['planCost'];
			$planTax = $row['planTax'];
			$planRecurring = $row['planRecurring'];
			$planRecurringPeriod = $row['planRecurringPeriod'];
			
			// the tax is a relative percentage amount of the price, thus we need to
			// calculate the tax amount
			$planTax = (($planTax/100)*$planCost);

			$planCurrency = $row['planCurrency'];

            $planTax = number_format($planTax, 2, '.', '');
            $planCost = number_format($planCost, 2, '.', '');

			// lets add user information to the database
			$sql = "INSERT INTO ".$configValues['CONFIG_DB_TBL_DALOUSERINFO'].
					" (id, username, firstname, lastname, address, city, state, creationdate, creationby)".
					" VALUES (0,'$userPIN','".$dbSocket->escapeSimple($firstName)."','".$dbSocket->escapeSimple($lastName)."', '".
					$dbSocket->escapeSimple($address)."','".$dbSocket->escapeSimple($city)."','".$dbSocket->escapeSimple($state)."', ".
					"'$currDate','$currBy'".
					")";
			$res = $dbSocket->query($sql);
			
			// lets add user billing information to the database
			$sql = "INSERT INTO ".$configValues['CONFIG_DB_TBL_DALOUSERBILLINFO'].
					" (id, username, planname, contactperson, address, city, state, creationdate, creationby) ".
					" VALUES (0, '$userPIN', '$planName', '".$dbSocket->escapeSimple($firstName)." ".$dbSocket->escapeSimple($lastName)."', '".
					$dbSocket->escapeSimple($address)."','".$dbSocket->escapeSimple($city)."','".$dbSocket->escapeSimple($state)."', ".
					" '$currDate', '$currBy'".
					")";
			$res = $dbSocket->query($sql);
			
			//if ($planRecurring == "No") {
				// lets add user billing information to the database
				$sql = "INSERT INTO ".$configValues['CONFIG_DB_TBL_DALOBILLINGMERCHANT'].
						" (id, username, txnId, planId, vendor_type, payment_date)".
						" VALUES (0,'$userPIN','$txnId', $planId, 'PayPal', '$currDate'".
						")";
				$res = $dbSocket->query($sql);
			//}
			
			$status = "paypal";

			include('library/closedb.php');

		} else {

			// if the paramteres haven't been set, we alert the user that these are required
			$errorMissingFields = true;
		}

	}

?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>User Sign-Up</title>
<style type="text/css">

body {
color: #737373; font-size: 10px; font-family: verdana;

background-image:url('./images/beach.png');
    
background-repeat:no-repeat;
    
background-attachment:fixed;
    
background-position:center center;
	
background-size:cover;

}
</style>
</head>
<body>
<script src="library/javascript/common.js" type="text/javascript"></script>


<div id="wrap">
<table width="400" align="center" bgcolor="#ccccff" style="border: 1px solid #cccccc; padding: 0px;">

<tr><td align="center"><img src="./images/logo.jpg" alt="welshwifi.net" /></td></tr>
<tr><td align="center"></td></tr>
                
                       
	<?php

                /*************************************************************************************************************************************************
                 *
                 * switch case for status of the sign-up process, whether it's the first time the user accesses it, or rather he already submitted
                 * the form with either successful or errornous result
                 *
                 *************************************************************************************************************************************************/

                if ( (isset($errorMissingFields)) && ($errorMissingFields == true) ) {

                        printq('
                                <br/>
                                        <font color="red"><b> Missing fields, please fill out all fields! </b></font>
                                <br/><br/>
                                ');
                }


                switch ($status) {
                        case "firstload":

                                echo "
                                        <tr><td align=\"center\">Welcome to this welshwifi.net portal at Freshwater East.</td></tr>
                                        <tr><td align=\"center\">Please complete the form below selecting the plan you wish to subscribe to and click on Submit to continue.</td></tr>
</table>

                                        <form name='newuser' action='".$_SERVER['PHP_SELF']."' method='post'>
<table  width=\"400\" align=\"center\" bgcolor=\"#ccccff\" style=\"border: 1px solid #cccccc; padding: 0px;\">
                                        <tr><td>Select your plan:</td>
                                        	<td><select id='planId' name='planId'>
                                        ";

                                include('library/opendb.php');

                                $sql = "SELECT id AS planId,planName,planCost,planTax,planCurrency FROM ".$configValues['CONFIG_DB_TBL_DALOBILLINGPLANS'].
                                        " WHERE planType='PayPal'";
                                $res = $dbSocket->query($sql);
                                while ($row = $res->fetchRow()) {
                                        echo "<option value=\"$row[0]\">$row[1] - Cost $row[2] $row[4] </option>";
                                }

                                include('library/closedb.php');

                                echo "
                                        </select></td></tr>

                                        

                                        <tr><td>First name:</td>
                                            <td> <input name='firstName' value='"; if (isset($firstName)) echo $firstName; echo "' /> </td></tr>
                                        <tr><td>Last name:</td>
                                            <td> <input name='lastName' value='"; if (isset($lastName)) echo $lastName; echo "' /> </td></tr>
                                        <tr><td>Address:</td>
                                            <td> <input name='address' value='"; if (isset($address)) echo $address; echo "' /> </td></tr>
                                        <tr><td>City:</td>
                                            <td> <input name='city' value='"; if (isset($city)) echo $city; echo "' /> </td></tr>
                                        <tr><td>State:</td>
                                            <td> <input name='state' value='"; if (isset($state)) echo $state; echo "' /> </td></tr>

					<tr><td><input type='submit' value='Submit' name='submit' /></td></tr>
					</table>
                                        </form>
                                        ";

                                break;


                        case "paypal":
                                printq('
					</table>
					<table  width="400" align="center" bgcolor="#ccccff" style="border: 1px solid #cccccc; padding: 0px;">
                                        <tr><td><font color="blue"><b>Thank you...</b></font></td></tr>
                                        <tr><td><b>Your PIN code has been created but it will only be activated after you complete and confirm your payment through PayPal. After payment you will be automatically logged in, however <font color="red">you will need this pin code to re-login or log in on another machine.</font></b></td></tr>
                                        <tr align="center"><td>PIN Code: <b>
                                        ');

                                echo $userPIN;
								
                                echo '
                                        </b></td></tr></table>
                                        <br/>
										';
										
								if ($planRecurring == "No") {
								
									echo '
                                        <form action="'.$configValues['CONFIG_MERCHANT_WEB_PAYMENT'].'" method="post">
                                                
						<input type="hidden" name="cmd" value="_xclick" />
                                                <input type="hidden" name="business" value="'.$configValues['CONFIG_MERCHANT_BUSINESS_ID'].'" />

                                                <input type="hidden" name="return" value="'.$configValues['CONFIG_MERCHANT_IPN_URL_ROOT'].'/'.
																								$configValues['CONFIG_MERCHANT_IPN_URL_RELATIVE_SUCCESS'].
																								'?txnId='.$txnId.'&username='.$userPIN.'" />
                                                <input type="hidden" name="cancel_return" value="'.$configValues['CONFIG_MERCHANT_IPN_URL_ROOT'].'/'.
																										$configValues['CONFIG_MERCHANT_IPN_URL_RELATIVE_FAILURE'].'" />
                                                <input type="hidden" name="notify_url" value="'.$configValues['CONFIG_MERCHANT_IPN_URL_ROOT'].'/'.
																										$configValues['CONFIG_MERCHANT_IPN_URL_RELATIVE_DIR'].'" />

                                                <input type="hidden" id="amount" name="amount" value="'; if (isset($planCost)) echo $planCost; echo '" />
                                                <input type="hidden" id="item_name" name="item_name" value= "'; if (isset($planName)) echo $planName; echo ' - Username: ';echo $userPIN; echo '" />
                                                <input type="hidden" name="quantity" value="1" />
                                                <input type="hidden" id="tax" name="tax" value="'; if (isset($planTax)) echo $planTax; echo '" />
                                                <input type="hidden" id="item_number" name="item_number" value="'; if (isset($planId)) echo $planId; echo '" />

                                                <input type="hidden" name="no_note" value="1" />
                                                <input type="hidden" id="currency_code" name="currency_code" value="'; if (isset($planCurrency)) echo $planCurrency; echo '" />
												<input type="hidden" name="no_shipping" value="1" />
                                                <input type="hidden" name="lc" value="US" />

                                                <input type="hidden" name="on0" value="Transaction ID" />
                                                <input type="hidden" name="os0" value="'.$txnId.'" />

                                                <input type="hidden" name="on1" value="Username" />
                                                <input type="hidden" name="os1" value="'.$userPIN.'" />
												
                                                <center><input type="image" align="middle" src="https://www.paypal.com/en_US/i/btn/x-click-but23.gif" name="submit"
                                                alt="Make payments with PayPal - its fast, free and secure!" /></center>
                                        </form>
										';
								
								} else if ($planRecurring == "Yes") {
								
									//$t3 = billing cycle period
									
									$t3 = "M"; //billing cycle period
									$p3 = "1"; //billing cycle length
									if ($planRecurringPeriod == "Daily")
										$t3 = "D";
									
									if ($planRecurringPeriod == "Weekly")
										$t3 = "W";
									
									if ($planRecurringPeriod == "Monthly")
										$t3 = "M";
									
									if ($planRecurringPeriod == "Yearly")
										$t3 = "Y";
									
									echo '
										<form name="_xclick" action="'.$configValues['CONFIG_MERCHANT_WEB_PAYMENT'].'" method="post">
										<input type="hidden" name="cmd" value="_xclick-subscriptions">
										<input type="hidden" name="business" value="'.$configValues['CONFIG_MERCHANT_BUSINESS_ID'].'">
										<input type="hidden" name="no_shipping" value="1" />
										<input type="hidden" name="return" value="'.$configValues['CONFIG_MERCHANT_IPN_URL_ROOT'].'/'.
																						$configValues['CONFIG_MERCHANT_IPN_URL_RELATIVE_SUCCESS'].
																						'?txnId='.$txnId.'" />
										<input type="hidden" name="cancel_return" value="'.$configValues['CONFIG_MERCHANT_IPN_URL_ROOT'].'/'.
																								$configValues['CONFIG_MERCHANT_IPN_URL_RELATIVE_FAILURE'].'" />
										<input type="hidden" name="notify_url" value="'.$configValues['CONFIG_MERCHANT_IPN_URL_ROOT'].'/'.
																								$configValues['CONFIG_MERCHANT_IPN_URL_RELATIVE_DIR'].'" />
										<input type="hidden" id="currency_code" name="currency_code" value="'; if (isset($planCurrency)) echo $planCurrency; echo '" />
										<input type="hidden" name="lc" value="US">
										
										<input type="hidden" name="on0" value="Transaction ID" />
										<input type="hidden" name="os0" value="'.$txnId.'" />
										<input type="hidden" name="on1" value="Username" />
										<input type="hidden" name="os1" value="'.$userPIN.'" />
										
										<input type="hidden" id="item_name" name="item_name" value="'; if (isset($planName)) echo $planName; echo '" />
										<input type="hidden" name="quantity" value="1" />
										<input type="hidden" id="tax" name="tax" value="'; if (isset($planTax)) echo $planTax; echo '" />
										<input type="hidden" id="item_number" name="item_number" value="'; if (isset($planId)) echo $planId; echo '" />

										<input type="hidden" name="no_note" value="1">
										
										<input type="hidden" id="a3" name="a3" value="'; if (isset($planCost)) echo $planCost; echo '" />
										<input type="hidden" name="p3" value="'.$p3.'">
										<input type="hidden" name="t3" value="'.$t3.'">
										<input type="hidden" name="src" value="1">
										<input type="hidden" name="sra" value="1">
										
										<input type="hidden" name="custom" value="'.$userPIN.'" />
										
										<input type="image" src="http://www.paypal.com/en_US/i/btn/btn_subscribe_LG.gif" align="center"  name="submit" alt="Make payments with PayPal - its fast, free and secure!" />
										</form>
									';
								}

								echo '
									<br/>
									<table  width="400" align="center" bgcolor="#ccccff" style="border: 1px solid #cccccc; padding: 0px;"> 
									<tr><td><b>Please make a note or take a screen shot of this PIN code, in case of a failure!</b></td></tr>
									</table>
									 ';


                                break;

                }


        ?>


                        
                

        



       

</div>


</body>
</html>

