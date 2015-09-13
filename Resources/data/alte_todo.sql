ALTER TABLE `todo`   
  ADD COLUMN `house_id` INT(11) DEFAULT 0  NOT NULL  COMMENT '房源ID' AFTER `entity_id`,
  ADD COLUMN `flat_id` INT(11) DEFAULT 0  NOT NULL  COMMENT '集中式ID' AFTER `house_id`;