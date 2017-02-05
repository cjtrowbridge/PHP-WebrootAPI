<?php

/*

PHP Webroot API
CJ Trowbridge
---------------
Implements a PHP interface to the Webroot Unity API. I built this while at Tech 2U as part of a custom tool which can automatically generate keys for customers. 
This is much faster than using Webroot's web console.

Ideally, you should implement caching for the AuthToken which is passed between the two functions here. Depending on how you are using this script, there are lots of different ways to do that. 
Caching would speed up your requests and also decrease the load on Webroot's servers.

To get a client_id and client_secret, go to 

*/

global $WebrootCredentials,$WebrootToken;

$WebrootToken=null;
$WebrootCredentials=array(
  'username'      => '',
  'password'      => '',
  'client_id'     => '',
  'client_secret' => ''
);

function GetWebrootAuthToken(){
  global $WebrootCredentials,$WebrootToken;
  
  $WebrootRequest = array(
    'username'      => urlencode($WebrootCredentials['username']),
    'password'      => urlencode($WebrootCredentials['password']),
    'client_id'     => urlencode($WebrootCredentials['client_id']),
    'client_secret' => urlencode($WebrootCredentials['client_secret']),
    'grant_type'    => urlencode('password'),
    'scope'         => urlencode('*')
  );
  
  $APIURL = 'https://unityapi.webrootcloudav.com/auth/token';
  
  $FieldsString='';
  foreach($PostFields as $Key=>$Value){
    $FieldsString .= $Key.'='.$Value.'&';
  }
  $FieldsString=rtrim($fields_string, '&');
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
  
  $JSON = json_decode($Result, true);
  return $JSON['access_token'];
  
}

function WebrootAPIRequest($APIPath = '/service/api/health/ping',$PostFields=null){
  /*
    Call this function with empty arguments to ping the API and verify the connection and authentication are working.
  */
  
  //If no POST fields are passed, 
  if($PostFields==null){
    $PostFields=array();
  }
  
  /*
    This Token should ideally be cached and only gotten when it expires, but 
    implementing that will depend on the environment in which you are using the API.
  */
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
