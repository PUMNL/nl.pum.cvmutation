<?php

require_once 'cvmutation.civix.php';

/**
 * Send e-mail to Sector Coordinator and set CV in mutation when an
 * expert has changed his/her own CV
 * 
 * @param type $op
 * @param type $groupID
 * @param type $entityID
 * @param type $params
 */
function cvmutation_civicrm_custom( $op, $groupID, $entityID, &$params ) {
    //delegates the handlig to a class
    $handler = CRM_Cvmutation_Handler::singleton();
    $handler->custom($op, $groupID, $entityID, $params);
}

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function cvmutation_civicrm_config(&$config) {
  _cvmutation_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function cvmutation_civicrm_xmlMenu(&$files) {
  _cvmutation_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function cvmutation_civicrm_install() {
  _cvmutation_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function cvmutation_civicrm_uninstall() {
  _cvmutation_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function cvmutation_civicrm_enable() {
  _cvmutation_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function cvmutation_civicrm_disable() {
  _cvmutation_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function cvmutation_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _cvmutation_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function cvmutation_civicrm_managed(&$entities) {
  _cvmutation_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function cvmutation_civicrm_caseTypes(&$caseTypes) {
  _cvmutation_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function cvmutation_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _cvmutation_civix_civicrm_alterSettingsFolders($metaDataFolders);
}
