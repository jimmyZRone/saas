ALTER TABLE `room` ADD COLUMN `full_name` VARCHAR(60) NULL COMMENT '房间名' AFTER `house_id`; 

ALTER TABLE `room_focus` ADD COLUMN `full_name` VARCHAR(60) NULL COMMENT '房间名称' AFTER `flat_id`; 

update room as r join house as h on r.house_id = h.house_id set r.full_name = concat(h.house_name,if((`r`.`room_type` = 'main'),'主卧',if((`r`.`room_type` = 'guest'),'客卧','次卧')),`r`.`custom_number`,'号');

update room_focus as rf join flat as f on f.flat_id = rf.flat_id set rf.full_name = CONCAT(f.flat_name,rf.floor,'楼',rf.custom_number,'号');