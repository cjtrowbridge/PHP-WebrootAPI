<?php

/*

PHP Webroot API
CJ Trowbridge
---------------
Implements a PHP interface to the Webroot Unity API. I built this while at Tech 2U as part of a custom tool which can automatically generate keys for customers. 
This is much faster than using Webroot's web console.

Ideally, you should implement caching for the AuthToken which is passed between the two functions here. Depending on how you are using this script, there are lots of different ways to do that. 
Caching would speed up your requests and also decrease the load on Webroot's servers.

There are several things that need to be filled in, and then you can simply call this function which will return a new key;

  MakeWebrootKey(
    $CustomerName,
    $CustomerEmail,
    $Seats = 1,
    $Policy = '[Insert Default Policy ID]',
    $CustID = null
  );
  
  
Or this function which will return all your keys;
  
  GetAllWebrootKeysByGSM(
    $GSM
  );
  

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

function GetAllWebrootKeys(){
  $GSMs=array(
    1 => "[Insert GSM IDs]"
  );
  $Output = array();
  foreach($GSMs as $Number => $GSM){
    $GSM=GetAllWebrootKeysByGSM($GSM);
    foreach($GSM['Sites'] as $Site){
     $Output[$Site['SiteName']]=array(
        'SiteName'       => $Site['SiteName'],
        'AccountKeyCode' => $Site['AccountKeyCode'],
        'DevicesAllowed' => $Site['DevicesAllowed'],
        'Deactivated'    => $Site['Deactivated'],
        'Suspended'      => $Site['Suspended'],
        'EndDate'        => $Site['EndDate']
      ); 
    }
  }
  return $Output;
}

function GetAllWebrootKeysByGSM($GSM){
  $API=WebrootAPIRequest('/service/api/console/gsm/'.$GSM.'/sites');
  return $API;
}

function MakeWebrootKey($CustomerName,$CustomerEmail,$Seats = 1,$Policy = '[Insert Default Policy ID]',$CustID = null){
  
  //Update this to whatever you want the deafult email to be if none is passed
  if(trim($CustomerEmail)==''){$CustomerEmail='default@domain.com';}
  
  $CustomerEmail=str_replace(' ','',$CustomerEmail);
  $CustomerEmail=trim($CustomerEmail);
  
  $SiteName=$CustomerName.' '.date('n-d-y');
  $PostFields = array(
    
    'SiteName'        => $SiteName,
    'Seats'           => $Seats,
    'Comments'        => $SiteName,
    'BillingCycle'    => "Annually",
    'BillingDate'     => date("M j"),
    'GlobalPolicies'  => 'true',
    'GlobalOverrides' => 'true',
    'PolicyId'        => $Policy,
    'Emails'          => $CustomerEmail,
    'Trial'           => 'false'
  );
  
  
  $API=WebrootAPIRequest('/service/api/console/gsm/'.CURRENT_GSM.'/sites',$PostFields,'post');
  
  if(!(isset($API['SiteId']))){
    echo '<h1>FAILED TO CREATE WEBROOT KEY</h1>';
    echo '<pre>';
    if(isset($API['error_description'])){
      echo $API['error_description'];
    }
    var_dump($PostFields);
    var_dump($API)
    echo '</pre>';
    exit;
  }
  
  AddAllAdminsBySiteID($API['SiteId']);
  
  //I am assuming you are selling one-year keys
  $EndDate = time()+(60*60*24*365);
  
  $CustID=intval($CustID);
  if($CustID==0){$CustID='null';}
  
  //Make sure to create a table and connect a database if you want the keys to be stored locally, otherwise comment out this query.
  $SQL= "INSERT INTO WebrootKeys SET `Key`='".mysqli_real_escape_string($API['KeyCode'])."',`SiteName`='".mysqli_real_escape_string($SiteName)."',`CustomerID`=".$CustID.",`Seats`='".intval($Seats)."',`Deactivated`='0',`Suspended`='0',`EndDate`='".mysqli_real_escape_string($EndDate)."';";
  $Results=mysqli_query($SQL);
  return $API['KeyCode'];
}

function ListAdmins(){
  $API=WebrootAPIRequest('/service/api/console/gsm/'.CURRENT_GSM.'/admins');
  return $API;
}

function AddAllAdminsBySiteID($SiteID){
  $Admins=ListAdmins();
  if(!(isset($Admins['Admins']))){die('Unable to load list of admins for current gsm.');}
  $List=array('Admins'=>array());
  foreach($Admins['Admins'] as $Admin){
    $List['Admins'][]=array(
      'UserID'      => $Admin['UserId'],
      'AccessLevel' => 128
    );
  }
   
  $API=WebrootAPIRequest('/service/api/console/gsm/'.CURRENT_GSM.'/sites/'.$SiteID.'/admins',$List,'put');
}
