<?php

class Webroot{
  
  
  private $Token;
  private $WebrootCredentials = array(
    'username'      => '',
    'password'      => '',
    'client_id'     => '',
    'client_secret' => ''
  );
  
  
  //function __construct(){
    
  //}
  
  
  function WebrootAPIRequest($APIPath = '/service/api/health/ping',$PostFields=null){
    /*
      Call this function with empty arguments to ping the API and verify the connection and authentication are working.
    */
    //If no POST fields are passed, 
    if($PostFields==null){
      $PostFields=array();
    }
    $Token = GetWebrootAuthToken();
    $APIURL = 'https://unityapi.webrootcloudav.com'.$APIPath.'?access_token='.urlencode($Token);
    $FieldsString='';
    foreach($PostFields as $Key=>$Value){
      $FieldsString .= $Key.'='.$Value.'&';
    }
    $FieldsString=rtrim($FieldsString, '&');
    $WebrootAPIcURL = curl_init();
    curl_setopt($WebrootAPIcURL,CURLOPT_URL, $APIURL);
    curl_setopt($WebrootAPIcURL,CURLOPT_POST, count($PostFields));
    curl_setopt($WebrootAPIcURL,CURLOPT_POSTFIELDS, $FieldsString);
    curl_setopt($WebrootAPIcURL,CURLOPT_RETURNTRANSFER, true);
    curl_setopt($WebrootAPIcURL,CURLOPT_HTTPHEADER, array(
      'Authorization: Bearer '.$Token
    ));
    $Result = curl_exec($WebrootAPIcURL);
    curl_close($WebrootAPIcURL);

    return json_decode($Result,true);

  }

  function get_key(){
    return $this->name;
  }
  

}	
