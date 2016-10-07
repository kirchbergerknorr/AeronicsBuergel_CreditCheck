<?php
/**
 *
 * Copyright (C) 2015 by AERONICS MEDIA GmbH & Co. KG
 *
 * This program is licenced under the AERONICS software licence. With the
 * purchase or the installation of the software in your application you
 * accept the licence agreement. The allowed usage is outlined in the
 * AERONICS software licence which can be found under
 * http://www.aeronics.de/shopware/pluginlicense
 *
 * Any modification or distribution is strictly forbidden. The license
 * grants you the installation in one application.
 *
 * See the AERONICS software licence agreement for more details.
 *
 * @package AeronicsBuergel
 * @subpackage BuergelApi
 * @author AERONICS MEDIA GmbH & Co. KG
 */
require dirname(__FILE__) . '/SoapClientBuergel.class.php';
class BuergelApi {
    private $soapClient = false, $function=null, $logDir="";
    
    /**
     * contructor for the SOAP Client
     * 
     * @param str $username
     * @param str $password
     * @param str $function
     */
    public function __construct($username, $password, $function, $wsdl, $timeout, $logDirectory) {
        // check if function is allowed
        if(!in_array($function, array("concheck","riskcheck"))) return false;
        ini_set("soap.wsdl_cache_enabled", 0);
        // initiate new soap client (modified for buergel)
        $client = new SoapClientBuergel( $wsdl, array(
            'exceptions' => True, 
            'trace' => 1,
            'connection_timeout' => $timeout,
            'cache_wsdl' => WSDL_CACHE_NONE, 
            'encoding'=> 'UTF-8'
        ) );
        
        // set username and password
        $client->__setUsernameToken($username,$password);
        
        // publish function and soap client
        $this->setFunction($function);
        $this->logDir = $logDirectory;
        $this->soapClient = $client;
    }
    
    /**
     * sends a request to Bürgel
     * 
     * @param array $request
     * @return mixed
     */
    public function check($request) {
        // check whether soap client is already initiated
        if(!$this->soapClient) return false;
        
        // call soap function, build the request out of the request array
        try {
            $soapResponse = $this->soapClient->__soapCall($this->function,array($this->buildRequest($request)));
        }
        catch (\Exception $e) {
            // log error
            $this->log($e->getMessage()."\nRequest: ".$this->soapClient->__getLastRequest());
            return false;
        }
        
        return $soapResponse;
    }
    
    /**
     * publish function
     * 
     * @param str $function
     * @return str
     */
    public function setFunction($function){
        $this->function = $function;
        return $this->function;
    }
    
    /**
     * builds a soapVar out of the request array
     * 
     * @param array $request
     * @return \SoapVar
     */
    private function buildRequest($request){
        if($this->function == "concheck") {
            $xml = "<ns1:concheck>"
                . '<concheckIn scoreVersion="3" checkAddressFirst="true">'
                . "<header>"
                . "<customerReference>".$request["customerReference"]."</customerReference>"
                . "<productNumber>".$request['productNumber']."</productNumber>"
                . "<inquiryReason>".$request["inquiryReason"]."</inquiryReason>"
                . "<language>".$request["language"]."</language>"
                . ($request["address"]["person"]["personalnumber"]!=""?'<nonStandardParameters><nonStandardParameter name="personalIdentificationNumber">'.$request["address"]["person"]["personalnumber"].'</nonStandardParameter> </nonStandardParameters>':'')
                . "</header>"
                .'<address>'
                .'<person>'
                .'<firstname>'.$request["address"]["person"]["firstname"].'</firstname>'
                .'<lastname>'.$request["address"]["person"]["lastname"].'</lastname>'
                .($request["address"]["person"]["dateOfBirth"]!=""?'<dateOfBirth>'.$request["address"]["person"]["dateOfBirth"].'</dateOfBirth>':"")
                .($request["address"]["person"]["phone"]!=""?'<phone>'.$request["address"]["person"]["phone"].'</phone>':"")
                .'</person>'
                .'<location>'
                .'<street>'.$request["address"]["location"]["street"].'</street> <houseNumber>'.$request["address"]["location"]["houseNumber"].'</houseNumber> <postalCode>'.$request["address"]["location"]["postalCode"].'</postalCode> <city>'.$request["address"]["location"]["city"].'</city>'
                .'<country>'
                .'<code>'.$request["address"]["location"]["country"]["code"].'</code>'
                .'</country>'
                .'</location>'
                .'</address>'
                ."</concheckIn>"
                ."</ns1:concheck>";
        } elseif($this->function == "riskcheck") {
            $xml = "<ns1:riskcheck>"
                . '<riskcheckIn scoreVersion="3" checkAddressFirst="true">'
                . "<header>"
                . "<customerReference>".$request["customerReference"]."</customerReference>"
                . "<externalUserid>".$request["externalUserid"]."</externalUserid>"
                . "<productNumber>".$request['productNumber']."</productNumber>"
                . "<inquiryReason>".$request["inquiryReason"]."</inquiryReason>"
                . "<language>".$request["language"]."</language>"
                . ($request["address"]["person"]["personalnumber"]!=""?'<nonStandardParameters><nonStandardParameter name="personalIdentificationNumber">'.$request["address"]["person"]["personalnumber"].'</nonStandardParameter> </nonStandardParameters>':'')
                . "</header>"
                .'<address>'
                .'<identification>'
                .'<name>'.$request["address"]["identification"]["name"].'</name>'
                .($request["address"]["identification"]["dateOfBirth"]!=""?'<dateOfBirth>'.$request["address"]["identification"]["dateOfBirth"].'</dateOfBirth>':"")
                .($request["address"]["identification"]["phone"]!=""?'<phone>'.$request["address"]["identification"]["phone"].'</phone>':"")
                .'</identification>'
                .'<location>'
                .'<street>'.$request["address"]["location"]["street"].'</street> <houseNumber>'.$request["address"]["location"]["houseNumber"].'</houseNumber> <postalCode>'.$request["address"]["location"]["postalCode"].'</postalCode> <city>'.$request["address"]["location"]["city"].'</city>'
                .'<country>'
                .'<code>'.$request["address"]["location"]["country"]["code"].'</code>'
                .'</country>'
                .'</location>'
                .'</address>'
                ."</riskcheckIn>"
                ."</ns1:riskcheck>";
        }
        
        // return
        return new SoapVar($xml, XSD_ANYXML);
    }
    
    /**
     * logs the output
     * 
     * @param str $errorMessage
     * @return str
     */
    private function log($errorMessage) {
        // generate log 
        $log = "Error: [".date("Y-m-d H:i:s")."] ".$errorMessage."\n\n";
        
        // put into file
        file_put_contents($this->logDir, $log, FILE_APPEND);
        
        // return
        return $log;
    }
}
