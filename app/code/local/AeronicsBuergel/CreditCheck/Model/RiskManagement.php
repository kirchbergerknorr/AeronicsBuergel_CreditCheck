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
 * Risk management
 *
 * @author AERONICS
 */
class AeronicsBuergel_CreditCheck_Model_RiskManagement
    extends Mage_Core_Model_Abstract
{
    /** @var array */
    private $rulesSet = array();
    
    /** @var array */
    private $ruleMapping = array(
        'bs_gt_eq' => '%1$F >= %3$F',
        'bs_lt_eq' => '%1$F <= %3$F',
        'bs_eq'    => '%1$F == %3$F',
        'gv_gt_eq' => '%2$F >= %3$F',
        'gv_lt_eq' => '%2$F <= %3$F',
        'gv_eq'    => '%2$F == %3$F',
    );

    public function __construct() {
        try {
            for($i=1;$i<3;$i++) {
                if(!empty(unserialize(Mage::getStoreConfig('aeronicsbuergel_creditcheck/risk_management/rules'.$i)))) $this->addRules(unserialize(Mage::getStoreConfig('aeronicsbuergel_creditcheck/risk_management/rules'.$i)));
            }
        } catch(Exception $e) {

        }
    }
    
    /**
     * Adds rules.
     * 
     * @return array
     */
    public function addRules(array $rules)
    {
        $formattedRules = array();
        foreach ($rules as $rule) {
            $paymentMethod = $rule['payment_method'];
            $ruleName      = $rule['rule_name'];
            $ruleValue     = $rule['rule_value'];
            $productGroup  = $rule['product_group'];
            $countryCode   = $rule['country_code'];
            
            $formattedRules[$productGroup][$countryCode][$paymentMethod][] = array(
                $ruleName => $ruleValue,
            );
        }
        
        $this->rulesSet[] = $formattedRules;
        
        return $this;
    }

    /**
     * Executes rules.
     * 
     * @param string $paymentMethod
     * @param int    $quoteTotal
     * @param int    $buergelScore
     * @param int    $addressOrigin
     * @param string $productGroup
     * @param string $countryCode
     * 
     * @return bool false, if no risk found
     */
    public function executeRules(
        $paymentMethod,
        $quoteTotal,
        $buergelScore,
        $addressOrigin,
        $productGroup,
        $countryCode
    ) {
        if($productGroup == "concheck") $productGroup = "1";
        if($productGroup == "riskcheck") $productGroup = "2";

        $isRisky = false;
        foreach ($this->rulesSet as $rules) { 
            if (!isset($rules[$productGroup][$countryCode][$paymentMethod])) {
                continue;
            }

            $rule = $rules[$productGroup][$countryCode][$paymentMethod];
            foreach ($rule as $item) {
                $isRisky = $this->executeRule(
                    key($item), current($item),
                    $buergelScore, $quoteTotal, $addressOrigin
                );

                if ($isRisky) {
                    break; // And relation
                }
            }
            
            if (!$isRisky) {
                break; // Or relation
            }
        }

        return $isRisky;
    }
    
    /**
     * Executes a rule.
     * 
     * @param string $ruleName
     * @param string $ruleValue
     * @param int    $buergelScore
     * @param int    $quoteTotal
     * @param int    $addressOrigin
     * 
     * @return bool false, if no risk found
     */
    private function executeRule(
        $ruleName,
        $ruleValue,
        $buergelScore,
        $quoteTotal,
        $addressOrigin
    ) {
        if ('ao_in' == $ruleName) {
            $ruleValueArray = preg_split('/[ ]*,[ ]*/', $ruleValue);
            $isRisky = in_array($addressOrigin, $ruleValueArray);
        } elseif ('cg_in' == $ruleName) {
            $roleId = Mage::getSingleton('customer/session')->getCustomerGroupId();
            $groupname = Mage::getModel('customer/group')->load($roleId)->getCustomerGroupCode();
            $ruleValueArray = preg_split('/[ ]*,[ ]*/', $ruleValue);
            $isRisky = in_array($groupname, $ruleValueArray);
        } else {
            $params   = array();
            $params[] = $this->ruleMapping[$ruleName];
            $params[] = $buergelScore;
            $params[] = $quoteTotal;
            $params[] = $ruleValue;
            $math     = call_user_func_array('sprintf', $params);
            $isRisky  = $this->calculate($math);
        }
        
        return $isRisky;
    }
    
    /**
     * Calculates a math string.
     *
     * @param string $string
     *
     * @return string
     */
    private function calculate($string)
    {
        $calculate = create_function('', 'return (' . $string . ');');

        return $calculate();
    }
}