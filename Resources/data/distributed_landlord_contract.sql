ALTER TABLE `jzsaas_cs`.`distributed_landlord_contract`   
  CHANGE `cost` `cost` VARCHAR(16) NOT NULL  COMMENT '栋',
  CHANGE `unit` `unit` VARCHAR(16) NOT NULL  COMMENT '单元',
  CHANGE `floor` `floor` VARCHAR(16) NOT NULL  COMMENT '楼层',
  CHANGE `number` `number` VARCHAR(16) NOT NULL  COMMENT '号';
