<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Class CRM_Contact_BAO_RelationshipVortex.
 */
class CRM_Contact_BAO_RelationshipVortex extends CRM_Contact_DAO_RelationshipVortex {

  /**
   * This trigger runs whenever a "civicrm_relationship" record is inserted or updated.
   *
   * Goal: Ensure that every relationship record has two corresponding entries in the
   * vortex, the forward relationship (A=>B) and reverse relationship (B=>A).
   */
  const UPDATE_RELATIONSHIP_TRIGGER = "
        DECLARE name_a_b_ VARCHAR(64);
        DECLARE name_b_a_ VARCHAR(64);

        SELECT name_a_b, name_b_a INTO name_a_b_, name_b_a_ FROM civicrm_relationship_type WHERE id = NEW.relationship_type_id;

        INSERT INTO civicrm_relationship_vtx (relationship_id, relationship_type_id, orientation, near_type, near_contact_id, far_type, far_contact_id)
        VALUES (NEW.id, NEW.relationship_type_id, 0, name_b_a_, NEW.contact_id_a, name_a_b_, NEW.contact_id_b)
        ON DUPLICATE KEY UPDATE near_type = name_b_a_, near_contact_id = NEW.contact_id_a, far_type = name_a_b_, far_contact_id = NEW.contact_id_b;

        INSERT INTO civicrm_relationship_vtx (relationship_id, relationship_type_id, orientation, near_type, near_contact_id, far_type, far_contact_id)
        VALUES (NEW.id, NEW.relationship_type_id, 1, name_a_b_, NEW.contact_id_b, name_b_a_, NEW.contact_id_a)
        ON DUPLICATE KEY UPDATE near_type = name_a_b_, near_contact_id = NEW.contact_id_b, far_type = name_b_a_, far_contact_id = NEW.contact_id_a;
";

  /**
   * This trigger runs whenever a "civicrm_relationship_type" record is updated.
   *
   * Goal: Ensure that the denormalized fields ("name_b_a"/"name_a_b" <=> "near_type"/"far_type") remain current.
   */
  const UPDATE_RELATIONSHIP_TYPE_TRIGGER = "
    IF (OLD.name_a_b != NEW.name_a_b COLLATE utf8_bin OR OLD.name_b_a != NEW.name_b_a COLLATE utf8_bin) THEN

      UPDATE civicrm_relationship_vtx
      SET near_type = NEW.name_b_a, far_type = NEW.name_a_b
      WHERE relationship_type_id = NEW.id AND orientation = 0;

      UPDATE civicrm_relationship_vtx
      SET near_type = NEW.name_a_b, far_type = NEW.name_b_a
      WHERE relationship_type_id = NEW.id AND orientation = 1;

    END IF;
  ";

  /**
   * Read all records from civicrm_relationship and populate the vortex.
   * Each ordinary relationship in `civicrm_relationship` becomes two
   * distinct records in the vortex (one for A=>B relations; and one for B=>A).
   *
   * This method is primarily written (a) for manual testing and (b) in case
   * a broken DBMS, screwy import, buggy code, etc causes a corruption.
   *
   * NOTE: This is closely related to FiveTwentyEight::populateRelationshipVortex(),
   * except that the upgrader users pagination.
   */
  public static function rebuild() {
    CRM_Core_DAO::executeQuery('TRUNCATE civicrm_relationship_vtx');

    CRM_Core_DAO::executeQuery('
      INSERT INTO civicrm_relationship_vtx (relationship_id, relationship_type_id, orientation, near_type, near_contact_id, far_type, far_contact_id)
      SELECT rel.id, rel.relationship_type_id, 0, reltype.name_b_a, rel.contact_id_a, reltype.name_a_b, rel.contact_id_b
      FROM civicrm_relationship rel
      INNER JOIN civicrm_relationship_type reltype ON rel.relationship_type_id = reltype.id
    ');

    CRM_Core_DAO::executeQuery('
      INSERT INTO civicrm_relationship_vtx (relationship_id, relationship_type_id, orientation, near_type, near_contact_id, far_type, far_contact_id)
      SELECT rel.id, rel.relationship_type_id, 1, reltype.name_a_b, rel.contact_id_b, reltype.name_b_a, rel.contact_id_a
      FROM civicrm_relationship rel
      INNER JOIN civicrm_relationship_type reltype ON rel.relationship_type_id = reltype.id
    ');
  }

}
