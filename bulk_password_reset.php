<?php

/**

 * Bulk Password Reset Module

 *

 * This module will reset all the hosting password and clients password

 * Please refer to the full documentation @ http://docs.whmcs.com/Addon_Modules for more details.

 *

 * @package    WHMCS

 * @author     Achintha Samindika <achintha@outlook.com>

 * @copyright  Copyright (c) Achintha Samindika 2013

 * @license    http://www.whmcs.com/license/ WHMCS Eula

 * @version    $Id$

 * @link       http://www.whmcs.com/

 */

set_time_limit(3600);


if (!defined("WHMCS"))

	die("This file cannot be accessed directly");



function bulk_password_reset_config() {

    $configarray = array(

    "name" => "Bulk Password Reset Module",

    "description" => "This module will reset all the hosting password and clients password.",

    "version" => "1.0",

    "author" => "Achintha Samindika",

    "language" => "english",

    "fields" => array(
        "adminuser" => array ("FriendlyName" => "Admin Username", "Type" => "text", "Size" => "25", "Description" => "", "Default" => "", ),
        "resetclient" => array ("FriendlyName" => "Reset Client's Password?", "Type" => "yesno", "Size" => "25", "Description" => "Reset Client's Password", ),

    ));

    return $configarray;

}


function bulk_password_reset_output($vars) {

    $adminuser = $vars['adminuser'];

    clients_password_reset($adminuser);

    products_password_reset($adminuser);  

}


function clients_password_reset($adminuser){
    
    $command = "getclients";
    $values['limitnum'] = 10000;
    $api_clients = localAPI($command,$values,$adminuser);

    if(!empty($api_clients['clients']['client'])){
        $clients = $api_clients['clients']['client'];
    }
    else{
        return false;
    }

    if( !empty($clients) && is_array($clients)){

        foreach ($clients as $client) {

            $pw_command = "updateclient";
            $pw_values["clientid"] = $client['id'];
            $pw_values["password2"] = generate_password(10);
            
            $pw_results = localAPI($pw_command, $pw_values, $adminuser);
            
            echo 'Reset password for: ' . $client['email'];

            if(!empty($pw_results['result'])){
                if($pw_results['result'] === 'success'){
                    echo ' Success <br>';

                    $email_command = "sendemail";
                    $email_values["messagename"] = "Automated Password Reset";
                    $email_values["id"] = $client['id'];
                     
                    $email_results = localAPI($email_command, $email_values, $adminuser);
                }
                else{
                    echo ' Failed ('.$pw_results['result'].') <br>';
                }
            }
             
        }

    }

}


function products_password_reset($adminuser){

    $command = "getclientsproducts";

    $api_services = localAPI($command,$values,$adminuser);

    if(!empty($api_services['products']['product'])){
        $products = $api_services['products']['product'];
    }
    else{
        return false;
    }

    if( !empty($products) && is_array($products)){

        foreach ($products as $product) {
             
             if($product['status'] === 'Active'){
                
                $pw_command = "modulechangepw";
                $pw_values["serviceid"] = $product['id'];
                $pw_values["servicepassword"] = generate_password();
                 
                $pw_results = localAPI($pw_command, $pw_values, $adminuser);
                
                echo 'Reset password for: ' . $product['domain'];

                if(!empty($pw_results['result'])){
                    if($pw_results['result'] === 'success'){
                        echo ' Success <br>';

                        $email_command = "sendemail";
                        $email_values["messagename"] = "Hosting Account Welcome Email";
                        $email_values["id"] = $product['id'];
                         
                        $email_results = localAPI($email_command, $email_values, $adminuser);
                    }
                    else{
                        echo ' Failed ('.$pw_results['result'].') <br>';
                    }
                }

             }
        }

    }

}

function generate_password($length = 15, $add_dashes = false, $available_sets = 'luds')
{
    $sets = array();
   
    if(strpos($available_sets, 'l') !== false)
        $sets[] = 'abcdefghjkmnpqrstuvwxyz';

    if(strpos($available_sets, 'u') !== false)
        $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';

    if(strpos($available_sets, 'd') !== false)
        $sets[] = '23456789';

    if(strpos($available_sets, 's') !== false)
        $sets[] = '!@#$%&*?';
     
    $all = '';
    $password = '';

    foreach($sets as $set)
    {
        $password .= $set[array_rand(str_split($set))];
        $all .= $set;
    }
     
    $all = str_split($all);

    for($i = 0; $i < $length - count($sets); $i++)
        $password .= $all[array_rand($all)];
     
    $password = str_shuffle($password);
     
    if(!$add_dashes)
        return $password;
     
    $dash_len = floor(sqrt($length));
    $dash_str = '';

    while(strlen($password) > $dash_len)
    {
        $dash_str .= substr($password, 0, $dash_len) . '-';
        $password = substr($password, $dash_len);
    }

    $dash_str .= $password;
    return $dash_str;
}