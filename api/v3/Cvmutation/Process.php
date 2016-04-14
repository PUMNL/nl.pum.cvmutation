<?php

/**
 * Cvmutation.Process API
 *
 * Process all CV Mutation records and create an activity in CiviCRM.
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_cvmutation_process($params) {
  $cvMutation = CRM_Cvmutation_Handler::singleton();
  $since = new DateTime();
  $since->modify('-3 hours');
  $sql = "SELECT * FROM `civicrm_cvmutation` WHERE `date` <= %1 ORDER BY `date` ASC LIMIT 0, 100";
  $sqlParams[1] = array($since->format('Y-m-d H:i'), 'String');
  $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
  $count = 0;
  $deleteIds = array();
  while($dao->fetch()) {
    $old_cv = unserialize($dao->old_cv);
    $new_cv = unserialize($dao->new_cv);
    if (is_array($old_cv) && is_array($new_cv)) {
      $details = $cvMutation->formatCvDataToDetailText($old_cv, $new_cv);
      $cvMutation->createCVMutationActivity($dao->contact_id, $details);
    }
    $count ++;
    $deleteIds[] = $dao->id;
  }

  // Clean up, remove the records from the cvmuatuion table.
  if (count($deleteIds) > 0) {
    $cleanUpSql = "DELETE FROM `civicrm_cvmutation` WHERE `id` IN(".implode(",", $deleteIds).")";
    //CRM_Core_DAO::executeQuery($cleanUpSql);
  }

  $returnValues = array('count' => $count);
  return civicrm_api3_create_success($returnValues, $params, 'Cvmutation', 'Post');
}

