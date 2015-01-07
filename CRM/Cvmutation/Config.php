<?php

class CRM_Cvmutation_Config {
    
    protected static $singleton;
    
    protected $custom_group_names = array('Workhistory', 'Education');
    
    protected $cg = array();
    
    protected $CVMutationActivity;
    
    protected function __construct() {
        foreach($this->custom_group_names as $cg_name) {
            $this->cg[$cg_name] = civicrm_api3('CustomGroup', 'getsingle', array('name' => $cg_name));
        }
        
        $activity_type = civicrm_api3('OptionGroup', 'getvalue', array('return' => 'id', 'name' => 'activity_type'));
        $this->CVMutationActivity = civicrm_api3('OptionValue', 'getsingle', array('name' => 'CVMutation', 'option_group_id' => $activity_type));
    }
    
    /**
     * @return CRM_Cvmutation_Config
     */
    public static function singleton() {
        if (!self::$singleton) {
            self::$singleton = new CRM_Cvmutation_Config();
        }
        return self::$singleton;
    }
    
    /**
     * Returns an array with custom groups
     * which contain the fields for a CV
     */
    public function getCvCustomGroupIds() {
        $return = array();
        foreach($this->cg as $cg) {
            $return[] = $cg['id'];
        }
        return $return;
    }
    
    public function getCVMutationActivityTypeId() {
        return $this->CVMutationActivity['value'];
    }
}

