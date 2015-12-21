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

    protected $activity_id = false;

    protected $oldCvData = array();

    protected $newCvData = array();


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

        $this->newCvData = $this->getCvData($entityID);
        $details = $this->formatCvDataToDetailText();

        if ($this->alreadyHandled) {
            if ($this->activity_id) {
                $activity_params['id'] = $this->activity_id;
                $activity_params['details'] = $details;
                civicrm_api3('Activity', 'create', $activity_params);
            }
            return;
        }

        //create activity - this activity could be used with scheduled reminders
        //to inform the SC
        $this->createCVMutationActivity($details);

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

    protected function formatCvDataToDetailText() {
        $group_ids = array();
        foreach(array_keys($this->oldCvData) as $group_id) {
            $group_ids[] = $group_id;
        }
        foreach(array_keys($this->newCvData) as $group_id) {
            if (!in_array($group_id, $group_ids)) {
                $group_ids[] = $group_id;
            }
        }

        $details = '';
        foreach($group_ids as $group_id) {
            $groupTitle = '';
            if (isset($this->oldCvData[$group_id])) {
                $groupTitle = $this->oldCvData[$group_id]['label'];
            } elseif (isset($this->newCvData[$group_id])) {
                $groupTitle = $this->newCvData[$group_id]['label'];
            }

            $details .= '<h2>'.$groupTitle.'</h2>';

            $originalRecords = array_values($this->oldCvData[$group_id]['data']);
            $newRecords = array_values($this->newCvData[$group_id]['data']);
            $maxRecords = count($newRecords);
            if (count($originalRecords) > count($newRecords)) {
                $maxRecords = count($originalRecords);
            }

            for($i=0; $i < $maxRecords; $i++) {
                $details .= '<table><thead><tr><th style="width: 33%">'.ts('Field').'</th><th style="width: 33%">'.ts('original value').'</th><th style="width: 33%">'.ts('new value').'</th></tr></thead><tbody';
                $fields = array();
                if (isset($originalRecords[$i])) {
                    $fields = $originalRecords[$i]['fields'];
                } elseif (isset($newRecords[$i])) {
                    $fields = $newRecords[$i]['fields'];
                }
                foreach($fields as $field_id => $field) {
                    $label = $field['label'];
                    $originalValue = '';
                    $newValue = '';
                    if (isset($originalRecords[$i]['fields'][$field_id])) {
                        $originalValue = $originalRecords[$i]['fields'][$field_id]['value'];
                    }
                    if (isset($newRecords[$i]['fields'][$field_id])) {
                        $newValue = $newRecords[$i]['fields'][$field_id]['value'];
                    }
                    $details .= '<tr><td>'.$label.'</td><td>'.$originalValue.'</td><td>'.$newValue.'</td></tr>';
                }
                $details .= '</tbody></table>';
            }

        }

        return $details;
    }
    
    protected function setCvStatus($contact_id) {
        $config = CRM_Cvmutation_CvStatusConfig::singleton();
        $params['id'] = $contact_id;
        $params['custom_'.$config->getStatusFieldId()] = $config->getCvInMutationValue();
        civicrm_api3('Contact', 'create', $params);
    }

    protected function createCVMutationActivity($details) {
        $config = CRM_Cvmutation_Config::singleton();
        $enhancedTags = CRM_Cvmutation_EnhancedTags::singleton();
        $session = CRM_Core_Session::singleton();

        $sc_contact_id = $enhancedTags->get_sector_coordinator_id($session->get('userID'));

        $act_params = array();
        if ($this->activity_id) {
            $act_params['id'] = $this->activity_id;
        }
        $act_params['activity_type_id'] = $config->getCVMutationActivityTypeId();
        $act_params['status_id'] = 2; //completed
        $act_params['activity_date_time'] = date('YmdHis');
        if ($sc_contact_id) {
            $act_params['assignee_contact_id'] = $sc_contact_id;
        }
        $act_params['details'] = $details;
        
        $result = civicrm_api3('Activity', 'create', $act_params);
        $this->activity_id = $result['id'];
    }

    public function pre($op, $objectName, $id, &$params) {
        if ($objectName != 'Individual') {
            return;
        }
        if ($op != 'edit') {
            return;
        }

        $config = CRM_Cvmutation_Config::singleton();
        if (!isset($params['custom_'.$config->getSideActivitiesFieldId()])) {
            return;
        }

        if (empty($this->oldCvData)) {
            $this->oldCvData = $this->getCvData($id);
        }
    }

    protected function getCvData($contact_id) {
        $form = CRM_Core_DAO::$_nullObject;
        $return = array();
        $config = CRM_Cvmutation_Config::singleton();
        foreach($config->getCvCustomGroupIds() as $groupId) {
            $group = civicrm_api3('CustomGroup', 'getsingle', array('id' => $groupId));
            $return[$groupId]['label'] = $group['title'];
            $return[$groupId]['data'] = array();
            $fields = civicrm_api3('CustomField', 'get', array('custom_group_id' => $groupId));
            $dataParams = array();
            $dataParams['entity_id'] = $contact_id;
            $fieldInfo= array();
            foreach($fields['values'] as $field) {
                $dataParams['return.custom_'.$field['id']] = 1;
                $fieldInfo[$field['id']] = $field;
            }

            $customData = civicrm_api3('CustomValue', 'get', $dataParams);
            foreach($customData['values'] as $field_id => $dataArray) {
                foreach($dataArray as $recId => $value) {
                    if (in_array($recId, array('entity_id', 'latest', 'id'))) {
                        continue;
                    }
                    if (!isset($return[$groupId]['data'][$recId])) {
                        $return[$groupId]['data'][$recId] = array();
                        $return[$groupId]['data'][$recId]['fields'] = array();
                        foreach($fieldInfo as $field) {
                            $return[$groupId]['data'][$recId]['fields'][$field['id']] = array(
                                'label' => $field['label'],
                                'value' => '',
                            );
                        }
                    }
                    $formattedValue = $value;
                    if ($fieldInfo[$dataArray['id']]['data_type'] == 'Boolean') {
                        $formattedValue = $value ? ts('Yes') : ts('No');
                    } elseif ($fieldInfo[$dataArray['id']]['data_type'] == 'Country') {
                        $formattedValue = '';
                        if (!is_array($value)) {
                            $value = array($value);
                        }
                        foreach($value as $country_id) {
                            $country = civicrm_api3('Country', 'getvalue', array('id' => $country_id, 'return' => 'name'));
                            if (strlen($formattedValue)) {
                                $formattedValue .= ', ';
                            }
                            $formattedValue .= $country;
                        }
                    } elseif (is_array($value)) {
                        $formattedValue = implode(",", $value);
                    }
                    $return[$groupId]['data'][$recId]['fields'][$dataArray['id']]['value'] = $formattedValue;
                }
            }
        }
        return $return;
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
