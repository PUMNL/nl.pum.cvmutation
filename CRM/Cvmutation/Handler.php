<?php

/*
 * CV Mutation handler.
 * This class checks if CV data has been changed by the expert
 * If so additional action could be performed such as sending e-mail to sector
 * cordinator. Or setting a status field.
 */

class CRM_Cvmutation_Handler {

    protected static $singleton;

    /**
     * Flag to test if the Cvmutation has already been handled
     * 
     * This flag is needed because a CV is build with multiple custom groups
     * and each custom group is handled indivually
     * So we will set this flag as soon as the first handling to prevent further 
     * handling
     * 
     * @var bool
     */
    protected $alreadyHandled = false;

    protected function __construct() {
        
    }

    /**
     * @return CRM_Cvmutation_Handler
     */
    public static function singleton() {
        if (!self::$singleton) {
            self::$singleton = new CRM_Cvmutation_Handler();
        }
        return self::$singleton;
    }

    public function custom($op, $groupID, $entityID, &$params) {
        if (!$this->isValid($op, $groupID, $entityID, $params)) {
            return;
        }

        if ($this->alreadyHandled) {
            return;
        }

        //create activity - this activity could be used with scheduled reminders
        //to inform the SC
        $this->createCVMutationActivity();
        
        //set custom field CV in mutation
        $this->setCvStatus($entityID);
        
        $this->alreadyHandled = true;
    }
    
    protected function setCvStatus($contact_id) {
        $config = CRM_Cvmutation_CvStatusConfig::singleton();
        $params['id'] = $contact_id;
        $params['custom_'.$config->getStatusFieldId()] = $config->getCvInMutationValue();
        civicrm_api3('Contact', 'create', $params);
    }

    protected function createCVMutationActivity() {
        $config = CRM_Cvmutation_Config::singleton();
        $enhancedTags = CRM_Cvmutation_EnhancedTags::singleton();
        $session = CRM_Core_Session::singleton();

        $sc_contact_id = $enhancedTags->get_sector_coordinator_id($session->get('userID'));
        if (!$sc_contact_id) {
            return;
        }

        $act_params = array();
        $act_params['activity_type_id'] = $config->getCVMutationActivityTypeId();
        $act_params['status_id'] = 2; //completed
        $act_params['activity_date_time'] = date('YmdHis');
        $act_params['assignee_contact_id'] = $sc_contact_id;
        
        civicrm_api3('Activity', 'create', $act_params);
    }

    protected function isValid($op, $groupID, $entityID, &$params) {
        $config = CRM_Cvmutation_Config::singleton();
        $session = CRM_Core_Session::singleton();

        if (!$session->get('userID')) {
            return false;
        }

        //check if the groupID is a CV custom group
        if (!in_array($groupID, $config->getCvCustomGroupIds())) {
            return false;
        }

        //check if the current user has changed his own CV 
        //if someone change a CV from someone else then we will do not
        //handle this
        if ($session->get('userID') != $entityID) {
            return false;
        }

        return true;
    }

}
