<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class CRM_Cvmutation_EnhancedTags {
    /*
     * protected sectorTree
     */

    protected $sectorTree = array();
    protected static $singleton;

    protected $parentSectorTagId; //tag id of Sector tag (toplevel)

    protected function __construct() {
        $this->setSectorTree();
    }

    /**
     * @return CRM_Cvmutation_EnhancedTags
     */
    public static function singleton() {
        if (!self::$singleton) {
            self::$singleton = new CRM_Cvmutation_EnhancedTags();
        }
        return self::$singleton;
    }

    /**
     * Function to get sector coordinator from customer
     * 
     * @param int $client_id
     * @param date $case_start_date
     * @return int $sector_coordinator_id
     * @access protected
     * @static
     */
    public function get_sector_coordinator_id($contact_id) {
        $sector_coordinator_id = 0;
        $contact_tags = $this->get_contact_tags($contact_id);
        foreach ($contact_tags as $contact_tag) {
            if ($this->is_sector_tag($contact_tag['tag_id']) == TRUE) {
                $sector_coordinator_id = $this->get_enhanced_tag_coordinator($contact_tag['tag_id']);
            }
        }
        return $sector_coordinator_id;
    }

    /**
     * Function to get sector from customer
     *
     * @param int $client_id
     * @param date $case_start_date
     * @return int $sector_coordinator_id
     * @access protected
     * @static
     */
    public function get_sector_tag_id($contact_id) {
      $sector_tag_id = 0;
      $contact_tags = $this->get_contact_tags($contact_id);
      foreach ($contact_tags as $contact_tag) {
        if ($this->is_sector_tag($contact_tag['tag_id']) == TRUE) {
          $sector_tag_id = $contact_tag['tag_id'];
        }
      }
      return $sector_tag_id;
    }

    /**
     * Function to get the coordinator for a tag
     * 
     * @param int $tag_id
     * @return int $coordinator_id
     * @access protected
     * @static
     */
    protected function get_enhanced_tag_coordinator($tag_id) {
        $params = array(
            'is_active' => 1,
            'tag_id' => $tag_id,
            'return' => 'coordinator_id');
        try {
            $coordinator_id = civicrm_api3('TagEnhanced', 'Getvalue', $params);
        } catch (CiviCRM_API3_Exception $ex) {
            $coordinator_id = 0;
        }
        return $coordinator_id;
    }

    /**
     * Function to get contact tags for contact
     * 
     * @param int $contact_id
     * @return array
     * @throws Exception when error from API EntityTag Get
     * @access protected
     * @static
     */
    protected function get_contact_tags($contact_id) {
        $params = array(
            'entity_table' => 'civicrm_contact',
            'entity_id' => $contact_id,
            'options' => array('limit' => 99999));
        try {
            $contact_tags = civicrm_api3('EntityTag', 'Get', $params);
        } catch (CiviCRM_API3_Exception $ex) {
            throw new Exception('Error retrieving contact tags with API EntityTag Get: ' . $ex->getMessage());
        }
        return $contact_tags['values'];
    }

    /**
     * Function to determine if tag is a sector tag
     * 
     * @param int $tag_id
     * @return boolean
     * @access protected
     * @static
     */
    protected function is_sector_tag($tag_id) {
        if (empty($tag_id)) {
            return FALSE;
        }

        $tag = civicrm_api3('Tag', 'getsingle', array('id' => $tag_id));
        if (isset($tag['parent_id']) && $tag['parent_id'] == $this->parentSectorTagId) {
          return true;
        }
        return false;

    }

  public static function clearSectorTreeFromCache() {
    CRM_Core_BAO_Setting::setItem(false, 'nl.pum.cvmutation', 'CRM_Cvmutation_EnhancedTags.sectorTree');
    CRM_Cvmutation_EnhancedTags::singleton(); //reload 
  }

    private function setSectorTree() {
      $sectorTree = CRM_Core_BAO_Setting::getItem('nl.pum.cvmutation', 'CRM_Cvmutation_EnhancedTags.sectorTree');
      if (!empty($sectorTree)) {
        $this->sectorTree = unserialize($sectorTree);
      } else {
        /*
         * first check if tag 'Sector' exists
         */
        try {
          $sectorTagId = civicrm_api3('Tag', 'Getvalue', array(
            'name' => 'Sector',
            'return' => 'id'
          ));
          $this->parentSectorTagId = $sectorTagId;
        } catch (CiviCRM_API3_Exception $ex) {
          throw new Exception(ts('Could not find a Tag called Sector, error from API Tag Getvalue: ') . $ex->getMessage());
        }
        $this->sectorTree[] = $sectorTagId;
        $this->getSectorChildren($sectorTagId);
        CRM_Core_BAO_Setting::setItem(serialize($this->sectorTree), 'nl.pum.cvmutation', 'CRM_Cvmutation_EnhancedTags.sectorTree');
      }
    }

    private function getSectorChildren($sectorTagId) {
        $gotAllChildren = FALSE;
        $levelChildren = array($sectorTagId);
        while ($gotAllChildren == FALSE) {
            foreach ($levelChildren as $levelChildTagId) {
                $childParams = array(
                    'parent_id' => $levelChildTagId,
                    'is_selectable' => 1,
                    'options' => array('limit' => 9999));
                $tagChildren = civicrm_api3('Tag', 'Get', $childParams);
                $gotAllChildren = $this->gotAllSectorChildren($tagChildren['count']);
                if ($tagChildren['count'] > 0) {
                    $levelChildren= $this->addSectorChildren($tagChildren['values']);
                }
            }
        }
    }

    private function addSectorChildren($tagChildren) {
        $return = array();
        foreach ($tagChildren as $tagChild) {
            if (!in_array($tagChild['id'], $this->sectorTree)) {
                $this->sectorTree[] = $tagChild['id'];
                $return[] = $tagChild['id'];
            }
        }
        return $return;
    }

    private function gotAllSectorChildren($count) {
        if ($count == 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

}
