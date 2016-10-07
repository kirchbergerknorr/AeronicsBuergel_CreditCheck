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
class SoapClientBuergel extends SoapClient {

    private $username="", $password="";
    
    /**
     * generates WS Security Header
     * 
     * @return \SoapHeader
     */
    private function wssecurity_header() {

        /* 
         * The timestamp. The computer must be on time or the server you are
         * connecting may reject the password digest for security.
         */
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        
        /* 
         * A random word. The use of rand() may repeat the word if the server is
         * very loaded.
         */
        $nonce = mt_rand();
        
        /* 
         * This is the right way to create the password digest. Using the
         * password directly may work also, but it's not secure to transmit it
         * without encryption. And anyway, at least with axis+wss4j, the nonce
         * and timestamp are mandatory anyway.
         */
        $passdigest = base64_encode(
            pack('H*',
                sha1(
                    pack('H*', $nonce) . pack('a*',$timestamp).
                    pack('a*',$this->password))));

        /* 
         * custom auth xml
         */
        $auth = '
                <wsse:Security xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" SOAP-ENV:mustUnderstand="1">
                    <wsse:UsernameToken wsu:Id="UsernameToken-6">
                        <wsse:Username>'.$this->username.'</wsse:Username>
                        <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">'.$this->password.'</wsse:Password>
                        <wsse:Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">'.base64_encode(pack('H*', $nonce)).'</wsse:Nonce>
                        <wsu:Created>'.$timestamp.'</wsu:Created>
                    </wsse:UsernameToken>
                </wsse:Security>
                ';

        /* 
         * XSD_ANYXML (or 147) is the code to add xml directly into a SoapVar.
         * Using other codes such as SOAP_ENC, it's really difficult to set the
         * correct namespace for the variables, so the axis server rejects the
         * xml.
         */
        $authvalues = new \SoapVar($auth,XSD_ANYXML);
        $header = new \SoapHeader("http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd", "Security", $authvalues, true);

        // return
        return $header;
    }

    /**
     * sets username and password
     * 
     * @param str $username
     * @param str $password
     * @return true;
     */
    public function __setUsernameToken($username, $password) {
        $this->username = $username;
        $this->password = $password;
        
        return true;
    }


    /**
     * overwrites the default __soapCall functionality
     * 
     * @param str $function_name
     * @param array $arguments
     * @param array $options
     * @param mixed $input_headers
     * @param array $output_headers
     * @return mixed
     */
    public function __soapCall($function_name, $arguments, $options=null,$input_headers=null, &$output_headers=null) {
        $result = parent::__soapCall($function_name, $arguments, $options,$this->wssecurity_header());

        return $result;
    }
}