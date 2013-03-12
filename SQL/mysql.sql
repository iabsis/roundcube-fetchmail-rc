CREATE  TABLE IF NOT EXISTS `ROUNDCUBE_fetchmail_rc` (
  `fetchmail_rc_id` MEDIUMINT(9) NOT NULL AUTO_INCREMENT ,
  `fk_user` INT(10) UNSIGNED NOT NULL ,
  `mail_host` VARCHAR(80) CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci' NOT NULL ,
  `mail_username` VARCHAR(80) CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci' NOT NULL ,
  `mail_password` VARCHAR(80) CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci' NOT NULL ,
  `mail_enabled` TINYINT(1) NOT NULL ,
  `mail_arguments` VARCHAR(120) CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci' NOT NULL ,
  `mail_ssl` TINYINT(1) NOT NULL ,
  `mail_protocol` ENUM('AUTO','POP2','POP3','APOP','RPOP','KPOP','SDPS','IMAP','ETRN','OMDR') CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci' NOT NULL ,
  `mail_date_last_retrieve` DATETIME NOT NULL ,
  `count_error` MEDIUMINT(9) NOT NULL ,
  `label_error` VARCHAR(256) CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci' NOT NULL ,
  PRIMARY KEY (`fetchmail_rc_id`) ,
  INDEX `fk_ROUNDCUBE_fetchmail_rc_ROUNDCUBE_users_idx` (`fk_user` ASC) ,
  CONSTRAINT `fk_ROUNDCUBE_fetchmail_rc_ROUNDCUBE_users`
    FOREIGN KEY (`fk_user` )
    REFERENCES `ROUNDCUBE_users` (`user_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_unicode_ci;
