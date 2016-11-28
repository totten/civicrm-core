{* file to handle db changes in 4.7.15 during upgrade *}

-- CRM-19690: Add template_type and template_options
ALTER TABLE civicrm_mailing
  ADD COLUMN `template_type` varchar(64)  COMMENT 'The language/processing system used for email templates.',
  ADD COLUMN `template_options` longtext  COMMENT 'Advanced options used by the email templating system. (JSON encoded)';
