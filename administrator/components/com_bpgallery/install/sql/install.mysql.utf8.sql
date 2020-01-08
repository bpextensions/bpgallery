-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Table `#__bpgallery_images`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__bpgallery_images` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(400) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NOT NULL DEFAULT '',
  `alias` VARCHAR(400) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_bin' NOT NULL DEFAULT '',
  `alt` VARCHAR(400) NULL DEFAULT '',
  `filename` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Original filename',
  `catid` INT(10) NULL COMMENT 'Category ID',
  `state` TINYINT(3) NOT NULL DEFAULT '0',
  `intro` VARCHAR(2048) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NOT NULL COMMENT 'Short description',
  `description` TEXT CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NOT NULL COMMENT 'Long description',
  `ordering` INT(11) NOT NULL DEFAULT '0',
  `params` TEXT CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NOT NULL,
  `metadata` TEXT CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NOT NULL,
  `checked_out` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `checked_out_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_up` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `created_by_alias` VARCHAR(255) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NOT NULL DEFAULT '',
  `modified` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `language` CHAR(7) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NOT NULL DEFAULT '*',
  `access` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  INDEX `idx_state` (`state` ASC),
  INDEX `idx_image_catid` (`catid` ASC),
  INDEX `idx_language` (`language` ASC),
  CONSTRAINT `fk_bpgallery-image-category`
    FOREIGN KEY (`catid`)
    REFERENCES `#__categories` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
