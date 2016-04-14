<?php

/**
 * Collection of upgrade steps
 */
class CRM_Cvmutation_Upgrader extends CRM_Cvmutation_Upgrader_Base {
    
    protected $activity_type;
    
    public function __construct($extensionName, $extensionDir) {
        parent::__construct($extensionName, $extensionDir);
        $this->activity_type = civicrm_api3('OptionGroup', 'getvalue', array('return' => 'id', 'name' => 'activity_type'));
    }
    

    public function install() {
        //create activity type
        $this->createCvMuationActivityType();
    }

    public function uninstall() {
        //Should we remove the activity type?
    }

    /**
     * Example: Run a couple simple queries
     *
     * @return TRUE on success
     * @throws Exception
     */
    public function upgrade_1001() {
        $this->createCvMuationActivityType();
        return TRUE;
    }

    /**
     * Upgrade for version 1.12
     *
     * @return bool
     */
    public function upgrade_1120() {
        $this->executeSqlFile('sql/civicrm_cvmutation.sql');
        return TRUE;
    }

    protected function createCvMuationActivityType() {
        $this->addOptionValue('CVMutation', 'CV Mutation', $this->activity_type, 1);
    }

    protected function addOptionValue($name, $label, $option_group_id, $is_reserved = 0, $component_id = false) {
        try {
            $exist_id = civicrm_api3('OptionValue', 'getvalue', array('return' => 'id', 'name' => $name, 'option_group_id' => $option_group_id));
            $params['id'] = $exist_id;
        } catch (Exception $e) {
            //do nothing
        }

        $params['name'] = $name;
        $params['label'] = $label;
        $params['is_active'] = 1;
        $params['is_reserved'] = $is_reserved;
        $params['option_group_id'] = $option_group_id;
        if ($component_id) {
            $params['component_id'] = $component_id;
        }
        civicrm_api3('OptionValue', 'create', $params);
    }

    protected function removeOptionValue($name, $option_group_id) {
        try {
            $exist_id = civicrm_api3('OptionValue', 'getvalue', array('return' => 'id', 'name' => $name, 'option_group_id' => $option_group_id));
            civicrm_api3('OptionValue', 'delete', array('id' => $exist_id));
            return; //aleardy exist
        } catch (Exception $e) {
            //do nothing
        }
    }

}
