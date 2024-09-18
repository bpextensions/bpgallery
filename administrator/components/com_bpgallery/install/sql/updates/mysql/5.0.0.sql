-- Altera tables
ALTER TABLE `#__bpgallery_images`
    MODIFY COLUMN `checked_out_time` DATETIME DEFAULT NULL;
ALTER TABLE `#__bpgallery_images`
    MODIFY COLUMN `publish_up` DATETIME DEFAULT NULL;
ALTER TABLE `#__bpgallery_images`
    MODIFY COLUMN `publish_down` DATETIME DEFAULT NULL;
ALTER TABLE `#__bpgallery_images`
    MODIFY COLUMN `created` DATETIME NOT NULL;
ALTER TABLE `#__bpgallery_images`
    MODIFY COLUMN `modified` DATETIME NOT NULL;

-- Altera data
UPDATE `#s`
SET `checked_out_time` = NULL
WHERE `checked_out_time` = '0000-00-00 00:00:00';

UPDATE `#__bpgallery_images`
SET `publish_up` = NULL
WHERE `publish_up` = '0000-00-00 00:00:00';

UPDATE `#__bpgallery_images`
SET `publish_down` = NULL
WHERE `publish_down` = '0000-00-00 00:00:00';

UPDATE `#__bpgallery_images`
SET `created` = NOW()
WHERE `created` = '0000-00-00 00:00:00';

UPDATE `#__bpgallery_images`
SET `modified` = NOW()
WHERE `modified` = '0000-00-00 00:00:00';