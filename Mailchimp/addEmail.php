<?php
include_once("MailchimpHelper.php");
$email = "";
$name = "";

if(isset($_POST['email']) && isset($_POST['name'])) {
	$email = $_POST['email'];
	$name = $_POST['name'];
}

//instantiate mailchimp and add user
if($email != "") {
	$mailchimp = new MailchimpHelper();
	$res = $mailchimp->insertUser($email, $name);
	if(isset($res->status)) {
		if($res->status == "subscribed") {
			$result["ok"] = true;
			$result["msg"] = "Email added!";
		}else if($res->status == "400") {
			$result["ok"] = false;
			if($res->title == "Member Exists") {
				$result["msg"] = "This address is already included.";
			} else if($res->title == "Invalid Resource"){
				$result["msg"] = "This address is invalid.";
			} else {
				$result["msg"] = "An error has occured";
			}
		} else {
			$result["ok"] = false;
			$result["msg"] = "An error has ocurred";
		}
	} else {
		$result["ok"] = false;
		$result["msg"] = "An error has ocurred";
	}

	echo json_encode($result);
}
