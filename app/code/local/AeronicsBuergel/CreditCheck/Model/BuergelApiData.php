<?php
/**
 *
 * Copyright (C) 2016 by AERONICS MEDIA GmbH & Co. KG
 *
 * This program is licenced under the AERONICS software licence. With the
 * purchase or the installation of the software in your application you
 * accept the licence agreement. The allowed usage is outlined in the
 * AERONICS software licence which can be found under
 * http://www.aeronics.de/magento/pluginlicense
 *
 * Any modification or distribution is strictly forbidden. The license
 * grants you the installation in one application.
 *
 * See the AERONICS software licence agreement for more details.
 *
 * @package AeronicsBuergel
 * @author AERONICS MEDIA GmbH & Co. KG
 */

/**
 * Buergel Api Data
 *
 * @author AERONICS
 */
class AeronicsBuergel_CreditCheck_Model_BuergelApiData
    extends Mage_Core_Model_Abstract
{
    /** @var array */
    private $wsdlUrl = array(
        '1' => 'https://www.buergel-online.de/b2c-ws/services/b2c/v1/%s?wsdl',
        '2' => 'https://www.buergel-online.de/b2c-ws-test/services/b2c/v1/%s?wsdl',
    );
    
    /** @var string */
    private $logFile = '/var/www/buergel-mg19.project.aeronics.de/web/var/log/aeronicsbuergel_creditcheck.log';
    
    /** @var array */
    private $concheckMappingDe = array(
        '1' => 42,   // ConCheck basic
        '2' => 40,   // ConCheck
        '3' => 43,   // ConCheck plus
        '4' => 44,   // ConCheck jur.
        '5' => 45,   // ConCheck jur. plus
        '6' => 47,   // ConCheck move
        '7' => 48,   // ConCheck jur. move
        '8' => 1910, // ConCheck RealTime basic plus
    );

    /** @var array */
    private $concheckMappingAt = array(
        '1' => 40,   // Deltavista
        '2' => 1905, // Bisnode
        '3' => 1906, // Crif
        '4' => 0,    // Inactive
    );

    /** @var array */
    private $concheckMappingCh = array(
        '1' => 67,   // Intrum
        '2' => 1908, // Bisnode
        '3' => 1907, // Crif
        '4' => 0,    // Inactive
    );

    /** @var array */
    private $concheckMappingDefault = array(
        '1' => 40, // Active
        '2' => 0,  // Inactive
    );
    
    /** @var array */
    private $riskcheckMapping = array(
        '1' => 75, // RiskCheck standard
        '2' => 46, // RiskCheck advanced
        '3' => 77, // RiskCheck professional
    );
    
    /** @var array */
    private $countryCodeMapping = array(
        'DE' => 276,
        'AT' => 40,
        'CH' => 756,
        'SE' => 752,
        'FI' => 246,
        'NW' => 578,
        'BE' => 56,
    );
    
    /**
     * Returns the Buergel WSDL url.
     * 
     * @param string $mode
     * @param string $productGroup
     * 
     * @return string
     */
    public function getWsdlUrl($mode, $productGroup)
    {
        return sprintf($this->wsdlUrl[$mode], $productGroup);
    }
    
    /**
     * Returns path to the log file.
     * 
     * @return string
     */
    public function getLogFile()
    {
        return $this->logFile;
    }
    
    /**
     * Returns the ConCheck product.
     * 
     * @param string $countryCode
     * @param string $product
     * 
     * @return string
     */
    public function getConCheckProduct($countryCode, $product)
    {        
        switch ($countryCode) {
            case 'DE':
                $buergelProduct = $this->concheckMappingDe[$product];
                break;
            case 'AT':
                $buergelProduct = $this->concheckMappingAt[$product];
                break;
            case 'CH':
                $buergelProduct = $this->concheckMappingCh[$product];
                break;
            default:
                $buergelProduct = $this->concheckMappingDefault[$product];
        }
        
        return $buergelProduct;
    }
    
    /**
     * Returns the RiskCheck product.
     * 
     * @param string $countryCode
     * @param string $product
     * 
     * @return string
     * 
     * @throws InvalidArgumentException
     */
    public function getRiskCheckProduct($countryCode, $product)
    {
        if($countryCode != 'DE') {
            $msg = 'No RiskCheck Product available for Country "%s".';
            throw new InvalidArgumentException(sprintf($msg, $countryCode));
        }
        
        $buergelProduct = $this->riskcheckMapping[$product];
        
        return $buergelProduct;
    }
    
    /**
     * Transforms the 2 letters country code into iso int country code.
     * 
     * @param str $countryCode
     * 
     * @return boolean|int
     */
    public function getIsoInt($countryCode)
    {
        return isset($this->countryCodeMapping[$countryCode]) ? 
            $this->countryCodeMapping[$countryCode] : false;
    }
}