<?php

class CRM_Cvmutation_Config {
    
    protected static $singleton;
    
    protected $custom_group_names = array('Workhistory', 'Education');
    
    protected $cg = array();
    
    protected $CVMutationActivity;

    protected $side_activities_field_id;

    protected $expert_data_custom_group_id;
    
    protected function __construct() {
        foreach($this->custom_group_names as $cg_name) {
            $this->cg[$cg_name] = civicrm_api3('CustomGroup', 'getsingle', array('name' => $cg_name));
        }
        
        $activity_type = civicrm_api3('OptionGroup', 'getvalue', array('return' => 'id', 'name' => 'activity_type'));
        $this->CVMutationActivity = civicrm_api3('OptionValue', 'getsingle', array('name' => 'CVMutation', 'option_group_id' => $activity_type));

        $this->expert_data_custom_group_id = civicrm_api3('CustomGroup', 'getvalue', array('return' => 'id', 'name' => 'expert_data'));
        $this->side_activities_field_id = civicrm_api3('CustomField', 'getvalue', array('return' => 'id', 'name' => 'side_activities', 'custom_group_id' => $this->expert_data_custom_group_id));
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

    public function getExpertDataCustomGroupId() {
        return $this->expert_data_custom_group_id;
    }

    public function getSideActivitiesFieldId() {
        return $this->side_activities_field_id;
    }
}

