<?php
/**
 * Execute some operations on Mailchimp lists.
 * You need curl installed on your system
 * @author Everton Mendonça - evertonmj@gmail.com
 */
	

class MailchimpHelper {
	//your URL
	private $mailchimpUrl = "<YOUR_MAILCHIMP_URL>";
	//you API Key
	private $mailchimpApiKey = "<YOUR MAILCHIMP_API_KEY>";
	//list id
	private $listId = "<LIST_ID>";

	/**
	* Get user information
	**/
	public function getUser($mailchimpId, $listId) {
		$postdata = "";
		$url = $this->mailchimpUrl . '/lists//' . $this->listId . '/members//' . $mailchimpId;
		$header = 'Authorization: apikey ' . $this->mailchimpApiKey;

		return json_decode( self::sendData($postdata, $url, $header));
	}
        
        /**
	* Insert a new subscriber in the list
	* If your list has custom fields, just add them on "merge_fields" section
	*/
	public function insertUser($email, $name) {
		$postdata = '{"email_address": "'.$email.'", "status": "subscribed", "merge_fields": {"FNAME": "'.$name.'"}}';
		$url = $this->mailchimpUrl . '/lists//' . $this->listId . '/members//';
		$header = 'Authorization: apikey ' . $this->mailchimpApiKey;
		return json_decode(self::sendData($postdata, $url, $header, "POST"));
	}
	
	/**
	* Update user information
	**/
	public function updateUser($email, $name, $listId) {
		$postdata = '{"email_address": "'.$email.'", "status": "pending", "merge_fields": {"FNAME": "'.$name.'"}';
		$url = $this->mailchimpUrl . '/lists//' . $this->listId  . '/members//';
		$header = 'Authorization: apikey ' . $this->mailchimpApiKey;

		return json_decode(self::sendData($postdata, $url, $header, "PATCH"));
	}

	/**
	* Delete an user
	**/	
	public function deleteUser($mailchimpId, $listId) {
		$postdata = "";
		$url = $this->mailchimpUrl . '/lists//' . $this->listId . '/members//' . $mailchimpId;
		$header = 'Authorization: apikey ' . $this->mailchimpApiKey;

		return json_decode(self::sendData($postdata, $url, $header, "DELETE"));
	}
	
	/**
	* Send data
	**/
	private function sendData($data, $url, $header, $verb = "GET") {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		if($verb === "POST") {
			curl_setopt($curl, CURLOPT_POST, true);
		} else if($verb === "PATCH") {
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
		} else if($verb === "DELETE") {
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $verb);
		}
		if($data != "") {
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array($header, 'Content-Type: application/json'));
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$result = trim(curl_exec($curl));
		curl_close($curl);
		return $result;
	}
}

