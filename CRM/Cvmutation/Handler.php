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


    protected $completed_case_status;

    protected $expert_application_case_type;

    protected function __construct() {
      $case_status_id = civicrm_api3('OptionGroup', 'getvalue', array('return' => 'id', 'name' => 'case_status'));
      $this->completed_case_status = civicrm_api3('OptionValue', 'getsingle', array('name' => 'Completed', 'option_group_id' => $case_status_id));

      $case_type_id = civicrm_api3('OptionGroup', 'getvalue', array('return' => 'id', 'name' => 'case_type'));
      $this->expert_application_case_type = civicrm_api3('OptionValue', 'getsingle', array('name' => 'Expertapplication', 'option_group_id' => $case_type_id));
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

    protected function checkIfExpertApplicationCaseIsActive($contact_id) {
      $sql = "SELECT COUNT(*)
              FROM civicrm_case
              INNER JOIN civicrm_case_contact ON civicrm_case.id = civicrm_case_contact.case_id
              WHERE civicrm_case.case_type_id LIKE %1
              AND civicrm_case.status_id != %2
              AND civicrm_case_contact.contact_id = %3";
      $params[1] = array('%'.CRM_Core_DAO::VALUE_SEPARATOR.$this->expert_application_case_type['value'].CRM_Core_DAO::VALUE_SEPARATOR.'%', 'String');
      $params[2] = array($this->completed_case_status['value'], 'Integer');
      $params[3] = array($contact_id, 'Integer');
      $count = CRM_Core_DAO::singleValueQuery($sql, $params);

      if ($count > 0) {
        return true;
      }
      return false;
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

        if ($this->checkIfExpertApplicationCaseIsActive($entityID)) {
          return false;
        }

        return true;
    }

}
