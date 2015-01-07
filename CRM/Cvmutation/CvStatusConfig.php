<?php

class CRM_Cvmutation_CvStatusConfig {
    
    protected static $singleton;
    
    protected $cv_status_field;
    
    protected $cv_in_mutation_value;
    
    protected function __construct() {
        $cg = civicrm_api3('CustomGroup', 'getvalue', array('return' => 'id', 'name' => 'expert_data'));
        $this->cv_status_field = civicrm_api3('CustomField', 'getsingle', array('custom_group_id' => $cg, 'name' => 'CV_Status'));
        $this->cv_in_mutation_value = civicrm_api3('OptionValue', 'getvalue', array(
            'return' => 'value',
            'option_group_id' => $this->cv_status_field['option_group_id'],
            'label' => 'CV_in_mutation',
        ));
    }
    
    /**
     * @return CRM_Cvmutation_CvStatusConfig
     */
    public static function singleton() {
        if (!self::$singleton) {
            self::$singleton = new CRM_Cvmutation_CvStatusConfig();
        }
        return self::$singleton;
    }
    
    public function getStatusFieldId() {
        return $this->cv_status_field['id'];
    }
    
    public function getCvInMutationValue() {
        return $this->cv_in_mutation_value;
    }
}

