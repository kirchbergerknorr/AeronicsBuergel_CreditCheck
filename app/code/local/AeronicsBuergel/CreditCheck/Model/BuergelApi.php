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

require_once __DIR__ . '/../Library/BuergelApi/BuergelApi.class.php';

/**
 * Buergel Api
 *
 * @author AERONICS
 */
class AeronicsBuergel_CreditCheck_Model_BuergelApi
    extends Mage_Core_Model_Abstract
{
    /** @var array */
    private $config;
    
    /** @var AeronicsBuergel_CreditCheck_Model_BuergelApiData */
    private $data;
    
    /** @var AeronicsBuergel_CreditCheck_Model_RiskManagement */
    private $risk;
    
    /** @var Mage_Customer_Model_Session */
    private $customerSession;
    
    /** @var Mage_Customer_Model_Session */
    private $checkoutSession;
    
    /** @var int */
    private $buergelScore;
    
    /** @var int */
    private $buergelAddressOrigin;
    
    /** @var string */ 
    private $buergelNotice;
    
    /**
     * Constructor.
     */
    public function __construct()
    {
        $settings       = Mage::getStoreConfig('aeronicsbuergel_creditcheck/settings');
        $checkSettings  = Mage::getStoreConfig('aeronicsbuergel_creditcheck/check_settings');
        $this->config   = array_merge($settings, $checkSettings);
        
        $this->data = Mage::getModel('aeronicsbuergel_creditcheck/BuergelApiData');
        $this->risk = Mage::getModel('aeronicsbuergel_creditcheck/RiskManagement');

        $this->customerSession = Mage::getSingleton('customer/session');
        $this->checkoutSession = Mage::getSingleton('checkout/session');
    }

    /**
     * Returns the checkout address
     *
     * @return Mage_Checkout_Model_Address
     */
    public function getCheckoutAddress() {
        if(!$this->checkoutSession->getQuote()->getBillingAddress()) return false;
        return $this->checkoutSession->getQuote()->getBillingAddress();
    }

    /**
     * Returns the customer.
     * 
     * @return Mage_Customer_Model_Customer
     * 
     * @throws \RuntimeException
     */
    public function getCustomer()
    {
        if(!$this->customerSession->isLoggedIn()) {
            return false;
        }
        
        return $this->customerSession->getCustomer();
    }
    
    /**
     * Returns the customer default billing address.
     * 
     * @return Mage_Customer_Model_Address
     */
    public function getCustomerBillingAddress()
    {
        return $this->getCheckoutAddress();
    }
    
    /**
     * Returns the quote.
     * 
     * @return Mage_Sales_Model_Quote
     * 
     * @throws \RuntimeException
     */
    public function getQuote()
    {
        if (!$this->checkoutSession->getQuote()->getItemsCount()) {
            return false;
        }
        
        return $this->checkoutSession->getQuote();
    }
    
    /**
     * Returns the method code of selected payment method in quote.
     * 
     * @return string
     */
    public function getQuotePaymentMethod()
    {
        return $this
            ->getQuote()
            ->getPayment()
            ->getMethodInstance()
            ->getCode();
    }
    
    /**
     * Returns the billing address in quote.
     * 
     * @return Mage_Sales_Model_Quote_Address
     */
    public function getQuoteBillingAddress()
    {
        return $this
            ->getQuote()
            ->getBillingAddress();
    }
    
    /**
     * Retrives the method code of disabled payment method.
     * 
     * @return string
     */
    public function getDisabledPaymentMethod()
    {
        return $this
            ->checkoutSession
            ->getAeronicsBuergelDisabledMethodCode();
    }
    
    /**
     * Sets the method code of disabled payment method.
     * 
     * @param type $methodCode
     * 
     * @return null
     */
    public function setDisabledPaymentMethod($methodCode)
    {        
        $this
            ->checkoutSession
            ->setAeronicsBuergelDisabledMethodCode($methodCode);
    }
    
    /**
     * Retrieves the config.
     * 
     * @param null|string $option
     * 
     * @return string|array
     */
    public function getConfig($option = null)
    {        
        return $option ? $this->config[$option] : $this->config;
    }
    
    /**
     * Returns the Buergel product group.
     * 
     * @return string
     */
    public function getBuergelProductGroup()
    {
        switch ($this->getConfig('product')) {
            case '1':
                $group = 'concheck';
                
                break;
            case '2':
                $company = $this->getQuoteBillingAddress()->getCompany();
                $group   = $company ? 'riskcheck' : 'concheck';

                break;
            case '3':
                $group = 'riskcheck';

                break;
            default:
                $group = 'concheck';
                
                break;
        }
        
        return $group;
    }
    
    /**
     * Retrieves the Buergel score.
     * 
     * @return int
     */
    public function getBuergelScore()
    {
        return $this->buergelScore;
    }
    
    /**
     * Retrieves the Buergel address origin.
     * 
     * @return int
     */
    public function getBuergelAddressOrigin()
    {
        return $this->buergelAddressOrigin;
    }
    
    /**
     * Retrieves the Buergel notice.
     * 
     * @return string
     */
    public function getBuergelNotice()
    {
        return $this->buergelNotice;
    }
    
    /**
     * Checks risk.
     * 
     * @param string $methodCode
     * @param bool   $active
     * 
     * @return bool false, if no risk found
     */
    public function checkRisk($methodCode, $active)
    {
        $this->updateCustomerInfos();

        $type = $this->getConfig('type');
        if ('1' == $type && !$active || '2' == $type && $active) {
            return false;
        }
        
        try {
            $this->getQuote();
        } catch (\RuntimeException $ex) {
            return false;
        }

        try {
            if($this->getQuoteBillingAddress()->getBuergelExclude() == "1") return false;
        } catch(\Exception $e) { }

        // check for excluded customer groups
        $cgConfig = Mage::getStoreConfig('aeronicsbuergel_creditcheck/risk_management/cgexclude');
        if(trim($cgConfig) != "") {
            $roleId = Mage::getSingleton('customer/session')->getCustomerGroupId();
            $groupname = Mage::getModel('customer/group')->load($roleId)->getCustomerGroupCode();
            $ruleValueArray = preg_split('/[ ]*,[ ]*/', $cgConfig);
            if (in_array($groupname, $ruleValueArray)) return false;
        }

        if(!$this->getCustomerBillingAddress()) return false;

        // check if is company
        $productConfigured = Mage::getStoreConfig('aeronicsbuergel_creditcheck/settings/product');
        if($this->getCustomerBillingAddress()->getCompany() != "") {
            if($productConfigured == "1") return false;
        }

        $address = $active ?
            $this->getCustomerBillingAddress() : 
            $this->getQuoteBillingAddress();

        if (!$this->readBuergelScore()) {
            $this->buergelRequest($address);
        }

        //if (-1 == $this->buergelScore) {
        //    return false;
        //}
        
        return $this->risk->executeRules(
            $methodCode,
            $this->getQuote()->getBaseSubtotal(),
            $this->buergelScore,
            $this->buergelAddressOrigin,
            $this->getBuergelProductGroup(),
            $address->getCountryId()
         );
    }
    
    /**
     * Performs a buergel api request.
     * 
     * @param mixed $address
     * 
     * @return void
     */
    public function buergelRequest($address)
    {
        $productGroup = $this->getBuergelProductGroup();
        $logFile      = $this->data->getLogFile();

        $buergelApi = new BuergelApi(
            $this->getConfig('user'),
            $this->getConfig('password'),
            $productGroup,
            $this->data->getWsdlUrl($this->getConfig('mode'), $productGroup),
            $this->getBuergelTimeout($productGroup, $address->getCountryId()),
            $logFile
        );
        
        $this->buergelScore         = -1;
        $this->buergelAddressOrigin = 0;
        $this->buergelNotice        = 'Error';
        
        $response = $buergelApi->check(
            $this->getBuergelRequestData($productGroup, $address)
        );
        
        if($response) {
            $outIdentifier              = $productGroup . 'Out';
            $this->buergelScore         = floatval($response->$outIdentifier->decision->score) * 10; 
            $this->buergelAddressOrigin = $response->$outIdentifier->decision->addressOrigin->code;
            $this->buergelNotice        = $response->$outIdentifier->decision->text;
        }
        
        $this->saveBuergelScore();
        if ('2' == $this->getConfig('mode')) {
            $this->log($logFile, $productGroup);
        }
    }
    
    /**
     * Returns data for performing request.
     * 
     * @param string $productGroup
     * @param mixed  $address
     * 
     * @return array
     */
    private function getBuergelRequestData($productGroup, $address)
    {
        $personalNumber =  '';
        if ($this->getConfig('concheck_personalnumber') == '1') {
            $personalNumber = $this->getCheckoutAddress()->getPersonalNumber();
        }
        
        $dateOfBirth = $this->getCheckoutAddress()->getDob();
        if ($dateOfBirth) {
            $dateOfBirthArray = explode(' ', $dateOfBirth);
            $dateOfBirth      = $dateOfBirthArray[0];
        }


        $data = array(
            'customerReference' => $this->getQuoteBillingAddress()->getId() . ($this->getQuoteBillingAddress()->getId()!=''?'-':'') .
                                   $address->getFirstname() . '_' .
                                   $address->getLastname(),
            'inquiryReason'     => 'SOLVENCY_CHECK',
            'language'          => 'DE', // Enumeration: [DE, EN, ES, FR, IT]
            'address'           => array(
                'location' => array(
                    'street'      => $address->getStreetFull(),
                    'houseNumber' => '.', // House number can not be empty.
                    'postalCode'  => $address->getPostcode(),
                    'city'        => $address->getCity(),
                    'country'     => array(
                        'code' => $this->data->getIsoInt($address->getCountryId())
                    ),
                )
            )
        );
        
        if ($productGroup == 'concheck') {
            $product = $this->getConfig('product_concheck');
            $data['productNumber'] = $this->data->getConCheckProduct(
                $address->getCountryId(),
                $product
            );
            
            $data['address']['person'] = array(
                'firstname'      => $address->getFirstname(),
                'lastname'       => $address->getLastname(),
                'phone'          => $address->getTelephone(),
                'personalnumber' => $personalNumber,
                'dateOfBirth'    => $dateOfBirth,
             );
        } else {
            $product = $this->getConfig('product_riskcheck');
            $data['productNumber'] = $this->data->getRiskCheckProduct();
            $data['address']['identification'] = array(
                'name'           => $address->getCompany(),
                'phone'          => $address->getTelephone(),
                'personalnumber' => $personalNumber,
                'dateOfBirth'    => $dateOfBirth,
            );
        }
        
        return $this->normalizeAddress($data);
    }
    
    /**
     * Saves score infos to database.
     * 
     * @return array
     */
    private function saveBuergelScore()
    {
        $sql = 'INSERT INTO buergel_scores '
            . '(billingaddressID, hash, score, addressOrigin, product, notice, created, sessionID) VALUES'
            . '(:billingaddressID, :hash, :score, :addressOrigin, :product, :notice, :created, :sessionID)';
        
        $binds = array(
            'billingaddressID' => $this->getQuoteBillingAddress()->getId(),
            'hash'             => $this->getBuergelAddressHash(),
            'score'            => $this->buergelScore,
            'addressOrigin'    => $this->buergelAddressOrigin,
            'product'          => $this->getBuergelProductGroup(),
            'notice'           => $this->buergelNotice,
            'created'          => time(),
            'sessionID'        => session_id(),
        );
        
        Mage::getSingleton('core/resource')
            ->getConnection('core_write')
            ->query($sql, $binds);

        if($this->getCustomer()) {
            @$this->getCustomer()
                ->setLastBuergelScore(number_format($this->buergelScore/10,1,".",""))
                ->setLastBuergelNotice($this->buergelNotice)
                ->save();
        }
    }

    /**
     * updates customer infos
     */
    private function updateCustomerInfos() {
        try {
            $readSql  = 'SELECT * FROM buergel_scores WHERE '
                . 'billingaddressID = :billingaddressID '
                . 'order by id DESC';
            $readBinds = array(
                'billingaddressID' => $this->getQuoteBillingAddress()->getId()
            );

            $rescource    = Mage::getSingleton('core/resource');
            $data = $rescource->getConnection('core_read')
                ->query($readSql, $readBinds)
                ->fetch();

            if($this->getCustomer() && $data["score"] != "") {
                @$this->getCustomer()
                    ->setLastBuergelScore($data["score"])
                    ->setLastBuergelNotice($data["notice"])
                    ->save();
            }
        } catch(\Exception $e) { $logFile      = $this->data->getLogFile(); $this->log($logFile,$e->getMessage()); }
    }
    
    /**
     * Reads score infos from session or database.
     * 
     * @return boolean
     */
    private function readBuergelScore()
    {
        $rescource    = Mage::getSingleton('core/resource');
        $sessioncache = $this->getConfig('sessioncache');
        $recheck = $this->getConfig('recheck');
        if ($recheck > 30 || $recheck <= 0) { $recheck = 30; }
        
        $lifetime   = 1 == $sessioncache ? $recheck : 30;
        $clearSql   = 'DELETE FROM buergel_scores WHERE created < :time';
        $clearBinds = array('time' => time() - $lifetime * 24 * 60 * 60);
        $rescource->getConnection('core_write')->query($clearSql, $clearBinds);
        
        try {
            $hash = $this->getBuergelAddressHash();
        } catch (\RuntimeException $e) {
            return false;
        }
        
        $readSql  = 'SELECT * FROM buergel_scores WHERE '
            . 'hash = :hash AND sessionID = :id OR '
            . 'hash = :hash AND created >= :time '
            . 'order by id DESC';
        $readBinds = array(
            'hash' => $hash,
            'id'   => session_id(),
            'time' => time() - 60,
        );
        
        $data = $rescource->getConnection('core_read')
            ->query($readSql, $readBinds)
            ->fetch();
        
        if (empty($data)) { return false; }
        
        $this->buergelScore         = $data['score'];
        $this->buergelAddressOrigin = $data['addressOrigin'];
        $this->buergelNotice        = $data['notice'];
        
        return true;
    }
    
    /**
     * Returns Buergel request timeout.
     * 
     * @param string $productGroup
     * @param string $countryCode
     * 
     * @return int
     */
    private function getBuergelTimeout($productGroup, $countryCode)
    {
        if ($productGroup == 'concheck') {
            if ($countryCode == 'DE') {
                $timeout = $this->getConfig('concheck_timeout_de');
            } else {
                $timeout = $this->getConfig('concheck_timeout_int');
            }
        } else {
            $timeout = $this->getConfig('riskcheck_timeout');
        }
        
        return $timeout ? $timeout : 5;
    }
    
    /**
     * Returns hash string as address identifer.
     * 
     * @return string
     */
    private function getBuergelAddressHash()
    {
        $address   = $this->getQuoteBillingAddress();
        $company   = $address->getCompany();
        $firstname = $address->getFirstname();
        $lastname  = $address->getLastname();
        $postcode  = $address->getPostcode();
        $city      = $address->getCity();
        $country   = $address->getCountryId();
        
        return md5($company . $firstname . $lastname . $postcode . $city . $country);
    }
    
    /**
     * Log reqeust infos.
     * 
     * @param string $logFile
     * @param string $productGroup
     * 
     * @return null
     */
    private function log($logFile, $productGroup)
    {
        $log = '[' . date('Y-m-d H:i:s') . ']' .
               'email:' . $this->getCheckoutAddress()->getEmail() .
               '|score:' . $this->buergelScore .
               '|origin:' . $this->buergelAddressOrigin .
               '|group:' . $productGroup . PHP_EOL;
        file_put_contents($logFile, $log, FILE_APPEND);
    }
    
    /**
     * Normalizes request data.
     * 
     * @param array $data
     * 
     * @return array
     */
    private function normalizeAddress(array $data)
    {
        array_walk_recursive($data['address'], function (& $value) {
            $value = preg_replace('/[^\w. -]/u','', strip_tags($value));
        });
        
        return $data;
    }
}
