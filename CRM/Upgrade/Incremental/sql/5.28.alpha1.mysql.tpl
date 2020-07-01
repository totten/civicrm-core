{* file to handle db changes in 5.28.alpha1 during upgrade *}

-- https://github.com/civicrm/civicrm-core/pull/17579
ALTER TABLE `civicrm_navigation` CHANGE `has_separator`
`has_separator` tinyint   DEFAULT 0 COMMENT 'Place a separator either before or after this menu item.';

-- https://github.com/civicrm/civicrm-core/pull/17450
ALTER TABLE `civicrm_activity` CHANGE `activity_date_time` `activity_date_time` datetime NULL  DEFAULT CURRENT_TIMESTAMP COMMENT 'Date and time this activity is scheduled to occur. Formerly named scheduled_date_time.';
ALTER TABLE `civicrm_activity` CHANGE `created_date` `created_date` timestamp NULL  DEFAULT CURRENT_TIMESTAMP COMMENT 'When was the activity was created.';

-- The RelationshipVortex is a high-level index/cache for querying relationships.
DROP TABLE IF EXISTS `civicrm_relationship_vtx`;
CREATE TABLE `civicrm_relationship_vtx` (
     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Relationship ID',
     `relationship_id` int unsigned NOT NULL   COMMENT 'id of the relationship',
     `relationship_type_id` int unsigned NOT NULL   COMMENT 'id of the relationship',
     `orientation` int unsigned NOT NULL  DEFAULT 0 COMMENT 'The vortex record is a permutation of the original relationship record. The orientation indicates whether it is forward (0; A/B) or reverse (1; B/A) relationship.',
     `near_type` varchar(64)    COMMENT 'name for relationship of near_contact to far_contact.',
     `near_contact_id` int unsigned NOT NULL   COMMENT 'id of the first contact',
     `far_type` varchar(64)    COMMENT 'name for relationship of far_contact to far_contact.',
     `far_contact_id` int unsigned NOT NULL   COMMENT 'id of the second contact' ,
        PRIMARY KEY (`id`),
        UNIQUE INDEX `UI_relationship`(relationship_id, orientation),
        INDEX `index_nearid_neartype`(near_contact_id, near_type),
        INDEX `index_neartype`(near_type),
        INDEX `index_nearid_fartype`(near_contact_id, far_type),
        CONSTRAINT FK_civicrm_relationship_vtx_relationship_id FOREIGN KEY (`relationship_id`) REFERENCES `civicrm_relationship`(`id`) ON DELETE CASCADE,
        CONSTRAINT FK_civicrm_relationship_vtx_relationship_type_id FOREIGN KEY (`relationship_type_id`) REFERENCES `civicrm_relationship_type`(`id`) ON DELETE CASCADE,
        CONSTRAINT FK_civicrm_relationship_vtx_near_contact_id FOREIGN KEY (`near_contact_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE,
        CONSTRAINT FK_civicrm_relationship_vtx_far_contact_id FOREIGN KEY (`far_contact_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE  
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;


-- https://github.com/civicrm/civicrm-core/pull/17548
ALTER TABLE civicrm_contact_type CHANGE name  name varchar(64) not null comment 'Internal name of Contact Type (or Subtype).';
ALTER TABLE civicrm_contact_type CHANGE is_active is_active tinyint DEFAULT 1  COMMENT 'Is this entry active?';
ALTER TABLE civicrm_contact_type CHANGE is_reserved is_reserved tinyint DEFAULT 0  COMMENT 'Is this contact type a predefined system type';
UPDATE civicrm_contact_type SET is_active = 1 WHERE is_active IS NULL;
UPDATE civicrm_contact_type SET is_reserved = 0 WHERE is_reserved IS NULL;
