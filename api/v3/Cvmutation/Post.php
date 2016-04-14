<?php
/**
 * @author Jaap Jansma (CiviCooP) <jaap.jansma@civicoop.org>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */

/**
 * Create a CV Mutation record.
 *
 * When a CV mutation record already exists do nothing.
 *
 * @author Jaap Jansma (CiviCooP) <jaap.jansma@civicoop.org>
 * @date 4 March 2016
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 */
function civicrm_api3_cvmutation_post($params) {
  $cvMutation = CRM_Cvmutation_Handler::singleton();
  if (empty($params['contact_id'])) {
    return civicrm_api3_create_error('contact_id is required');
  }

  $contact_id = $params['contact_id'];
  $cvData = $cvMutation->getCvData($contact_id);

  $updateSql = "UPDATE `civicrm_cvmutation` SET `date` = NOW(), `new_cv` = %1 WHERE `contact_id` = %2";
  $updateParams[1] = array(serialize($cvData), 'String');
  $updateParams[2] = array($contact_id, 'Integer');
  CRM_Core_DAO::executeQuery($updateSql, $updateParams);

  $returnValues = array();
  return civicrm_api3_create_success($returnValues, $params, 'Cvmutation', 'Post');
}