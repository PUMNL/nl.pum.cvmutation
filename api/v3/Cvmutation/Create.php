<?php
/**
 * @author Jaap Jansma (CiviCooP) <jaap.jansma@civicoop.org>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */

/**
 * Create a CV Mutation activity for an expert
 *
 * @author Jaap Jansma (CiviCooP) <jaap.jansma@civicoop.org>
 * @date 4 March 2016
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 */
function civicrm_api3_cvmutation_create($params) {
  $cvMutation = CRM_Cvmutation_Handler::singleton();
  if (empty($params['contact_id'])) {
    return civicrm_api3_create_error('contact_id is required');
  }
  $returnValues[]['activity_id'] = $cvMutation->cvmutation($params['contact_id']);
  return civicrm_api3_create_success($returnValues, $params, 'Cvmutation', 'Create');
}