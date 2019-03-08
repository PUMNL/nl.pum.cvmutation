<?php

/**
 * Class with contact segment processing
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 5 January 2016
 * @license AGPL-3.0
 */
class CRM_Cvmutation_ContactSegment {
  /**
   * Function to get sector coordinator from expert
   *
   * @param int $contactId
   * @return int|bool
   * @access protected
   * @static
   */
  public static function getSectorCoordinator($contactId) {
    $sectorCoordinator = FALSE;
    $sectors = self::getSectors($contactId);

    foreach ($sectors as $segmentId => $segment) {
      try {
        if(!empty($segmentId) && $segment['is_main'] == 1) {
          $sgm = civicrm_api3('Segment', 'Getsingle', array('id' => $segmentId));
          if (!empty($sgm['is_active']) && $sgm['is_active'] == 1 && is_int((int)$segmentId)) {
            $sectorCoordinator = civicrm_api3('ContactSegment', 'Getvalue', array(
              'is_active' => 1,
              'role_value' => 'Sector Coordinator',
              'segment_id' => (int)$segmentId,
              'return' => 'contact_id'));
          }
        }
      } catch (CiviCRM_API3_Exception $ex) {}
    }
    return $sectorCoordinator;
  }

  /**
   * Function to get sector from expert
   *
   * @param int $contactId
   * @return array
   * @access protected
   * @static
   */
  public static function getSectors($contactId) {
    $sectors = array();
    $contactSegments = civicrm_api3('ContactSegment', 'Get',
      array(
        'contact_id' => $contactId,
        'is_active' => 1,
        'role_value' => 'Expert'));
    foreach ($contactSegments['values'] as $contactSegmentId => $contactSegment) {
      $segment = civicrm_api3('Segment', 'Getsingle', array('id' => $contactSegment['segment_id']));
      if (empty($segment['parent_id'])) {
        $sectors[$segment['id']] = array('label'=>$segment['label'],'is_main'=>$contactSegment['is_main']);
      }
    }
    return $sectors;
  }
}
