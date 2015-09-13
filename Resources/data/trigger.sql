/*构建分散式所有房源的表并把现有数据插入到表里面*/
DROP TABLE IF EXISTS `house_view`;
CREATE TABLE IF NOT EXISTS `house_view` (
  `house_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '房源ID',
  `record_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '房间ID',
  `occupancy_number` int(5) unsigned NOT NULL DEFAULT '0' COMMENT '可入住人数',
  `detain` int(2) NOT NULL DEFAULT '0' COMMENT '付款方式-押',
  `pay` int(2) NOT NULL DEFAULT '0' COMMENT '付款方式-付',
  `gender_restrictions` varchar(20) NOT NULL DEFAULT '' COMMENT '性别要求',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态,1,未租,2已租,3停用',
  `room_type` varchar(10) NOT NULL DEFAULT '' COMMENT '房屋类型main/主卧/second/次卧/guest/客卧',
  `is_yytz` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否预约退租,只有已租这个才有意义,0否,1是',
  `is_yd` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否有预订',
  `money` double(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '租金',
  `house_name` varchar(100) NOT NULL DEFAULT '' COMMENT '房源名称',
  `rental_way` tinyint(1) unsigned NOT NULL COMMENT '出租方式:1合租/2整租',
  `community_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '小区ID',
  `community_name` varchar(255) NOT NULL DEFAULT '' COMMENT '小区名称',
  `custom_number` varchar(100) NOT NULL DEFAULT '' COMMENT '房源编号',
  `cost` varchar(10) NOT NULL DEFAULT '' COMMENT '房源门牌号-栋',
  `unit` varchar(10) NOT NULL DEFAULT '' COMMENT '房源门牌号-单元',
  `number` varchar(10) NOT NULL DEFAULT '' COMMENT '房源门牌号-号',
  `count` int(3) unsigned NOT NULL DEFAULT '0' COMMENT '房屋户型-室',
  `hall` int(3) unsigned NOT NULL DEFAULT '0' COMMENT '房屋户型-厅',
  `toilet` int(3) unsigned NOT NULL DEFAULT '0' COMMENT '房屋户型-卫',
  `area` double(6,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '房屋面积',
  `total_floor` int(3) unsigned NOT NULL DEFAULT '0' COMMENT '楼层-总',
  `floor` int(3) NOT NULL DEFAULT '0' COMMENT '楼层-当前',
  `public_facilities` varchar(255) NOT NULL DEFAULT '' COMMENT '公共设施',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '详细地址',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间戳',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  `company_id` int(11) NOT NULL DEFAULT '0' COMMENT '公司ID',
  `create_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加人ID',
  `is_delete` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否已经删除',
  `owner_id` int(11) unsigned NOT NULL COMMENT '所有人id'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用于替换视图house_view';
TRUNCATE TABLE house_view;
INSERT INTO house_view(
		house_id,
		record_id,
		occupancy_number,
		detain,
		pay,
		gender_restrictions,
		`status`,
		room_type,
		is_yytz,
		is_yd,
		money,
		house_name,
		rental_way,
		community_id,
		community_name,
		custom_number,
		cost,
		unit,
		number,
		`count`,
		hall,
		toilet,
		area,
		total_floor,
		floor,
		public_facilities,
		address,
		create_time,
		update_time,
		company_id,
		create_uid,
		is_delete,
		owner_id
	)
SELECT
	r.house_id,
	r.room_id as record_id,
	r.occupancy_number,
	r.detain,
	r.pay,
	r.gender_restrictions,
	r.status,
	r.room_type,
	r.is_yytz,
	r.is_yd,
	r.money,
	CONCAT(c.community_name,h.cost,'栋',h.unit,'单元',h.floor,h.number,'号',IF(r.room_type = 'main','主卧',IF(r.room_type = 'guest','客卧','次卧')),r.custom_number) as house_name,
	h.rental_way,
	h.community_id,
	c.community_name,
	CONCAT(h.custom_number,'-',r.custom_number) as custom_number,
	h.cost,
	h.unit,
	h.number,
	h.`count`,
	h.hall,
	h.toilet,
	r.area,
	h.total_floor,
	h.floor,
	h.public_facilities,
	c.address,
	r.create_time,
	r.update_time,
	h.company_id,
	h.create_uid,
	if(r.is_delete = 1,r.is_delete,h.is_delete) as is_delete,
	h.owner_id
FROM room as r
JOIN house as h on r.house_id = h.house_id
JOIN community as c on h.community_id = c.community_id
WHERE (h.rental_way = 1);
INSERT INTO house_view(
		house_id,
		record_id,
		occupancy_number,
		detain,
		pay,
		gender_restrictions,
		`status`,
		room_type,
		is_yytz,
		is_yd,
		money,
		house_name,
		rental_way,
		community_id,
		community_name,
		custom_number,
		cost,
		unit,
		number,
		`count`,
		hall,
		toilet,
		area,
		total_floor,
		floor,
		public_facilities,
		address,
		create_time,
		update_time,
		company_id,
		create_uid,
		is_delete,
		owner_id)
SELECT
	he.house_id,
	0 as record_id,
	he.occupancy_number,
	he.detain,
	he.pay,
	he.gender_restrictions,
	he.status,
	'' as room_type,
	he.is_yytz,
	he.is_yd,
	he.money,
	CONCAT(c.community_name,h.cost,'栋',h.unit,'单元',h.floor,h.number,'号') as house_name,
	h.rental_way,
	h.community_id,
	c.community_name,
	h.custom_number,
	h.cost,
	h.unit,
	h.number,
	h.`count`,
	h.hall,
	h.toilet,
	h.area,
	h.total_floor,
	h.floor,
	h.public_facilities,
	c.address,
	h.create_time,
	h.update_time,
	h.company_id,
	h.create_uid,
	if(he.is_delete = 1,he.is_delete,h.is_delete) as is_delete,
	h.owner_id
FROM house_entirel as he
JOIN house as h on he.house_id = h.house_id
JOIN community as c on h.community_id = c.community_id
WHERE (h.rental_way = 2);


/*========================华丽丽的分割线==============================*/
/*构建分散式和集中式所有房源的表并把现有数据插入到表里面*/
DROP TABLE IF EXISTS `house_focus_view`;
CREATE TABLE IF NOT EXISTS `house_focus_view` (
  `house_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '房源ID',
  `record_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '房间ID',
  `occupancy_number` int(5) unsigned NOT NULL DEFAULT '0' COMMENT '可入住人数',
  `detain` int(2) NOT NULL DEFAULT '0' COMMENT '付款方式-押',
  `pay` int(2) NOT NULL DEFAULT '0' COMMENT '付款方式-付',
  `gender_restrictions` varchar(20) NOT NULL DEFAULT '' COMMENT '性别要求',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态,1,未租,2已租,3停用',
  `room_type` varchar(10) NOT NULL DEFAULT '' COMMENT '房屋类型main/主卧/second/次卧/guest/客卧',
  `is_yytz` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否预约退租,只有已租这个才有意义,0否,1是',
  `is_yd` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否有预订',
  `money` double(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '租金',
  `house_name` varchar(100) NOT NULL DEFAULT '' COMMENT '房源名称',
  `rental_way` tinyint(1) unsigned NOT NULL COMMENT '出租方式:1合租/2整租',
  `community_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '小区ID',
  `community_name` varchar(255) NOT NULL DEFAULT '' COMMENT '小区名称',
  `custom_number` varchar(100) NOT NULL DEFAULT '' COMMENT '房源编号',
  `cost` varchar(10) NOT NULL DEFAULT '' COMMENT '房源门牌号-栋',
  `unit` varchar(10) NOT NULL DEFAULT '' COMMENT '房源门牌号-单元',
  `number` varchar(10) NOT NULL DEFAULT '' COMMENT '房源门牌号-号',
  `count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '房屋户型-室',
  `hall` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '房屋户型-厅',
  `toilet` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '房屋户型-卫',
  `area` double(6,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '房屋面积',
  `total_floor` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '楼层-总',
  `floor` int(11) NOT NULL DEFAULT '0' COMMENT '楼层-当前',
  `public_facilities` varchar(255) NOT NULL DEFAULT '' COMMENT '公共设施',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '详细地址',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间戳',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  `company_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '公司ID',
  `create_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加人ID',
  `is_delete` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否已经删除',
  `owner_id` int(11) unsigned NOT NULL COMMENT '所有人id'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用于替换视图house_focus_view';
TRUNCATE TABLE house_focus_view;
INSERT INTO house_focus_view(
		house_id,
		record_id,
		occupancy_number,
		detain,
		pay,
		gender_restrictions,
		`status`,
		room_type,
		is_yytz,
		is_yd,
		money,
		house_name,
		rental_way,
		community_id,
		community_name,
		custom_number,
		cost,
		unit,
		number,
		`count`,
		hall,
		toilet,
		area,
		total_floor,
		floor,
		public_facilities,
		address,
		create_time,
		update_time,
		company_id,
		create_uid,
		is_delete,
		owner_id
	)
SELECT
	r.house_id,
	r.room_id as record_id,
	r.occupancy_number,
	r.detain,
	r.pay,
	r.gender_restrictions,
	r.status,
	r.room_type,
	r.is_yytz,
	r.is_yd,
	r.money,
	CONCAT(c.community_name,h.cost,'栋',h.unit,'单元',h.floor,h.number,'号',IF(r.room_type = 'main','主卧',IF(r.room_type = 'guest','客卧','次卧')),r.custom_number) as house_name,
	h.rental_way,
	h.community_id,
	c.community_name,
	CONCAT(h.custom_number,'-',r.custom_number) as custom_number,
	h.cost,
	h.unit,
	h.number,
	h.`count`,
	h.hall,
	h.toilet,
	r.area,
	h.total_floor,
	h.floor,
	h.public_facilities,
	c.address,
	r.create_time,
	r.update_time,
	h.company_id,
	h.create_uid,
	if(r.is_delete = 1,r.is_delete,h.is_delete) as is_delete,
	h.owner_id
FROM room as r
JOIN house as h on r.house_id = h.house_id
JOIN community as c on h.community_id = c.community_id
WHERE (h.rental_way = 1);
INSERT INTO house_focus_view(
		house_id,
		record_id,
		occupancy_number,
		detain,
		pay,
		gender_restrictions,
		`status`,
		room_type,
		is_yytz,
		is_yd,
		money,
		house_name,
		rental_way,
		community_id,
		community_name,
		custom_number,
		cost,
		unit,
		number,
		`count`,
		hall,
		toilet,
		area,
		total_floor,
		floor,
		public_facilities,
		address,
		create_time,
		update_time,
		company_id,
		create_uid,
		is_delete,
		owner_id)
SELECT
	he.house_id,
	0 as record_id,
	he.occupancy_number,
	he.detain,
	he.pay,
	he.gender_restrictions,
	he.status,
	'' as room_type,
	he.is_yytz,
	he.is_yd,
	he.money,
	CONCAT(c.community_name,h.cost,'栋',h.unit,'单元',h.floor,h.number,'号') as house_name,
	h.rental_way,
	h.community_id,
	c.community_name,
	h.custom_number,
	h.cost,
	h.unit,
	h.number,
	h.`count`,
	h.hall,
	h.toilet,
	h.area,
	h.total_floor,
	h.floor,
	h.public_facilities,
	c.address,
	h.create_time,
	h.update_time,
	h.company_id,
	h.create_uid,
	if(he.is_delete = 1,he.is_delete,h.is_delete) as is_delete,
	h.owner_id
FROM house_entirel as he
JOIN house as h on he.house_id = h.house_id
JOIN community as c on h.community_id = c.community_id
WHERE (h.rental_way = 2);
INSERT INTO house_focus_view(
		house_id,
		record_id,
		occupancy_number,
		detain,
		pay,
		gender_restrictions,
		`status`,
		room_type,
		is_yytz,
		is_yd,
		money,
		house_name,
		rental_way,
		community_id,
		community_name,
		custom_number,
		cost,
		unit,
		number,
		`count`,
		hall,
		toilet,
		area,
		total_floor,
		floor,
		public_facilities,
		address,
		create_time,
		update_time,
		company_id,
		create_uid,
		is_delete,
		owner_id)
SELECT
	0 AS house_id,
	rf.room_focus_id AS record_id,
	rf.custom_number AS occupancy_number,
	rf.detain,
	rf.pay,
	0 AS gender_restrictions,
	rf.status,
	'main' as room_type,
	rf.is_yytz,
	rf.is_yd,
	rf.money,
	CONCAT(f.flat_name,rf.custom_number) AS house_name,
	f.rental_way,
	0 AS community_id,
	'' AS community_name,
	rf.custom_number,
	0 AS cost,
	0 AS unit,
	0 AS number,
	IF(LENGTH(rf.room_type) < 1,0,CONVERT(SUBSTRING_INDEX(rf.room_type,'t',1),UNSIGNED)) AS `count`,
	rf.room_number AS hall,
	IF(LENGTH(rf.room_type) < 1,0,CONVERT(SUBSTRING_INDEX(rf.room_type,'t',-1),UNSIGNED)) AS toilet,
	rf.area,
	0 AS total_floor,
	0 AS floor,
	rf.room_config AS public_facilities,
	f.address,
	rf.create_time,
	rf.create_time AS update_time,
	rf.company_id,
	rf.create_uid,
	if(rf.is_delete = 1,rf.is_delete,f.is_delete) as is_delete,
	rf.owner_id
FROM room_focus AS rf
JOIN flat AS f ON rf.flat_id = f.flat_id;


/*========================华丽丽的分割线==============================*/
/*分散式房间表触犯器*/
DROP TRIGGER IF EXISTS room_insert;
DELIMITER $$
CREATE TRIGGER `room_insert` AFTER INSERT ON `room` FOR EACH ROW
BEGIN
     INSERT INTO house_view(
     	house_id,record_id,occupancy_number,detain,pay,gender_restrictions,`status`,
     	room_type,is_yytz,is_yd,money,house_name,rental_way,community_id,community_name,
     	custom_number,cost,unit,number,`count`,hall,toilet,area,total_floor,floor,
     	public_facilities,address,create_time,update_time,company_id,create_uid,is_delete,owner_id
	)
	SELECT
		r.house_id,r.room_id AS record_id,r.occupancy_number,r.detain,r.pay,r.gender_restrictions,
		r.status,r.room_type,r.is_yytz,r.is_yd,r.money,
		CONCAT(c.community_name,h.cost,'栋',h.unit,'单元',h.floor,h.number,'号',IF(r.room_type = 'main','主卧',IF(r.room_type = 'guest','客卧','次卧')),r.custom_number) AS house_name,
		h.rental_way,h.community_id,c.community_name,CONCAT(h.custom_number,'-',r.custom_number) AS custom_number,
		h.cost,h.unit,h.number,h.`count`,h.hall,h.toilet,r.area,h.total_floor,h.floor,h.public_facilities,
		c.address,r.create_time,r.update_time,h.company_id,h.create_uid,
		IF(r.is_delete = 1,r.is_delete,h.is_delete) AS is_delete,h.owner_id
	FROM room AS r
	JOIN house AS h ON r.house_id = h.house_id
	JOIN community AS c ON h.community_id = c.community_id
	WHERE (h.rental_way = 1 AND r.room_id = new.room_id) LIMIT 1;
	
	INSERT INTO house_focus_view(house_id,record_id,occupancy_number,detain,pay,gender_restrictions,
		`status`,room_type,is_yytz,is_yd,money,house_name,rental_way,community_id,community_name,
		custom_number,cost,unit,number,`count`,hall,toilet,area,total_floor,floor,public_facilities,
		address,create_time,update_time,company_id,create_uid,is_delete,owner_id
	)
	SELECT
		r.house_id,r.room_id as record_id,r.occupancy_number,r.detain,r.pay,r.gender_restrictions,
		r.status,r.room_type,r.is_yytz,r.is_yd,r.money,
		CONCAT(c.community_name,h.cost,'栋',h.unit,'单元',h.floor,h.number,'号',IF(r.room_type = 'main','主卧',IF(r.room_type = 'guest','客卧','次卧')),r.custom_number) as house_name,
		h.rental_way,h.community_id,c.community_name,CONCAT(h.custom_number,'-',r.custom_number) as custom_number,
		h.cost,h.unit,h.number,h.`count`,h.hall,h.toilet,r.area,h.total_floor,h.floor,h.public_facilities,
		c.address,r.create_time,r.update_time,h.company_id,h.create_uid,
		if(r.is_delete = 1,r.is_delete,h.is_delete) as is_delete,h.owner_id
	FROM room AS r
	JOIN house AS h on r.house_id = h.house_id
	JOIN community AS c on h.community_id = c.community_id
	WHERE (h.rental_way = 1 AND r.room_id = new.room_id) LIMIT 1;
END$$
DELIMITER ;


/*分散式整租表触犯器*/
DROP TRIGGER IF EXISTS house_entirel_insert;
DELIMITER $$
CREATE TRIGGER `house_entirel_insert` AFTER INSERT ON `house_entirel` FOR EACH ROW
BEGIN
    INSERT INTO house_view(
     	house_id,record_id,occupancy_number,detain,pay,gender_restrictions,`status`,
     	room_type,is_yytz,is_yd,money,house_name,rental_way,community_id,community_name,
     	custom_number,cost,unit,number,`count`,hall,toilet,area,total_floor,floor,
     	public_facilities,address,create_time,update_time,company_id,create_uid,is_delete,owner_id
	)
	SELECT
		he.house_id,0 as record_id,he.occupancy_number,he.detain,he.pay,he.gender_restrictions,
		he.status,'' as room_type,he.is_yytz,he.is_yd,he.money,
		CONCAT(c.community_name,h.cost,'栋',h.unit,'单元',h.floor,h.number,'号') as house_name,
		h.rental_way,h.community_id,c.community_name,h.custom_number,h.cost,h.unit,h.number,
		h.`count`,h.hall,h.toilet,h.area,h.total_floor,h.floor,h.public_facilities,c.address,
		h.create_time,h.update_time,h.company_id,h.create_uid,
		if(he.is_delete = 1,he.is_delete,h.is_delete) as is_delete,h.owner_id
	FROM house_entirel AS he
	JOIN house AS h ON he.house_id = h.house_id
	JOIN community AS c ON h.community_id = c.community_id
	WHERE (h.rental_way = 2 AND he.house_entirel_id=new.house_entirel_id) LIMIT 1;
	
	INSERT INTO house_focus_view(house_id,record_id,occupancy_number,detain,pay,gender_restrictions,
		`status`,room_type,is_yytz,is_yd,money,house_name,rental_way,community_id,community_name,
		custom_number,cost,unit,number,`count`,hall,toilet,area,total_floor,floor,public_facilities,
		address,create_time,update_time,company_id,create_uid,is_delete,owner_id
	)
	SELECT
		he.house_id,0 as record_id,he.occupancy_number,he.detain,he.pay,he.gender_restrictions,
		he.status,'' as room_type,he.is_yytz,he.is_yd,he.money,
		CONCAT(c.community_name,h.cost,'栋',h.unit,'单元',h.floor,h.number,'号') as house_name,
		h.rental_way,h.community_id,c.community_name,h.custom_number,h.cost,h.unit,h.number,
		h.`count`,h.hall,h.toilet,h.area,h.total_floor,h.floor,h.public_facilities,c.address,
		h.create_time,h.update_time,h.company_id,h.create_uid,
		if(he.is_delete = 1,he.is_delete,h.is_delete) as is_delete,h.owner_id
	FROM house_entirel AS he
	JOIN house AS h ON he.house_id = h.house_id
	JOIN community AS c ON h.community_id = c.community_id
	WHERE (h.rental_way = 2 AND he.house_entirel_id=new.house_entirel_id) LIMIT 1;
END$$
DELIMITER ;


/*集中式表触犯器*/
DROP TRIGGER IF EXISTS room_focus_insert;
DELIMITER $$
CREATE TRIGGER `room_focus_insert` AFTER INSERT ON `room_focus` FOR EACH ROW
BEGIN
	INSERT INTO house_focus_view(house_id,record_id,occupancy_number,detain,pay,gender_restrictions,
		`status`,room_type,is_yytz,is_yd,money,house_name,rental_way,community_id,community_name,
		custom_number,cost,unit,number,`count`,hall,toilet,area,total_floor,floor,public_facilities,
		address,create_time,update_time,company_id,create_uid,is_delete,owner_id
	)
	SELECT
		0 AS house_id,rf.room_focus_id AS record_id,rf.custom_number AS occupancy_number,rf.detain,
		rf.pay,0 AS gender_restrictions,rf.status,'main' as room_type,rf.is_yytz,rf.is_yd,rf.money,
		CONCAT(f.flat_name,rf.custom_number) AS house_name,f.rental_way,0 AS community_id,'' AS community_name,
		rf.custom_number,0 AS cost,0 AS unit,0 AS number,
		IF(LENGTH(rf.room_type) < 1,0,CONVERT(SUBSTRING_INDEX(rf.room_type,'t',1),UNSIGNED)) AS `count`,
		rf.room_number AS hall,
		IF(LENGTH(rf.room_type) < 1,0,CONVERT(SUBSTRING_INDEX(rf.room_type,'t',-1),UNSIGNED)) AS toilet,
		rf.area,0 AS total_floor,0 AS floor,rf.room_config AS public_facilities,f.address,rf.create_time,
		rf.create_time AS update_time,rf.company_id,rf.create_uid,if(rf.is_delete = 1,rf.is_delete,f.is_delete) as is_delete,
		rf.owner_id
	FROM room_focus AS rf
	JOIN flat AS f ON rf.flat_id = f.flat_id
	WHERE rf.room_focus_id = new.room_focus_id;
END$$
DELIMITER ;


/*========================华丽丽的分割线==============================*/
/*分散式房间表更新触犯器*/
DROP TRIGGER IF EXISTS room_update;
DELIMITER $$
CREATE TRIGGER `room_update` AFTER UPDATE ON `room` FOR EACH ROW
BEGIN
	UPDATE `house_view` AS hv
	JOIN `room` AS r ON hv.`house_id` = r.`house_id` AND hv.`record_id` = r.`room_id`
	JOIN `house` AS h ON r.`house_id` = h.`house_id`
	JOIN `community` AS c ON c.`community_id` = h.`community_id`
	SET
		hv.occupancy_number = r.`occupancy_number`,
		hv.`detain` = r.`detain`,
		hv.`pay` = r.`pay`,
		hv.`gender_restrictions` = r.`gender_restrictions`,
		hv.`status` = r.`status`,
		hv.`room_type` = r.`room_type`,
		hv.`is_yytz` = r.`is_yytz`,
		hv.`is_yd` = r.`is_yd`,
		hv.`money` = r.`money`,
		hv.`house_name` = CONCAT(c.community_name,h.cost,'栋',h.unit,'单元',h.floor,h.number,'号',IF(r.room_type = 'main','主卧',IF(r.room_type = 'guest','客卧','次卧')),r.custom_number),
		hv.`custom_number` = CONCAT(h.custom_number,'-',r.custom_number),
		hv.area = r.`area`,
		hv.create_time = r.`create_time`,
		hv.update_time = r.`update_time`,
		hv.is_delete = if(r.is_delete = 1,r.is_delete,h.is_delete)
	WHERE
		hv.`house_id` = new.house_id AND hv.`record_id` = new.room_id;
	
	UPDATE `house_focus_view` AS hfv
	JOIN `room` AS r ON hfv.`house_id` = r.`house_id` AND hfv.`record_id` = r.`room_id`
	JOIN `house` AS h ON r.`house_id` = h.`house_id`
	JOIN `community` AS c ON c.`community_id` = h.`community_id`
	SET
		hfv.occupancy_number = r.`occupancy_number`,
		hfv.`detain` = r.`detain`,
		hfv.`pay` = r.`pay`,
		hfv.`gender_restrictions` = r.`gender_restrictions`,
		hfv.`status` = r.`status`,
		hfv.`room_type` = r.`room_type`,
		hfv.`is_yytz` = r.`is_yytz`,
		hfv.`is_yd` = r.`is_yd`,
		hfv.`money` = r.`money`,
		hfv.`house_name` = CONCAT(c.community_name,h.cost,'栋',h.unit,'单元',h.floor,h.number,'号',IF(r.room_type = 'main','主卧',IF(r.room_type = 'guest','客卧','次卧')),r.custom_number),
		hfv.`custom_number` = CONCAT(h.custom_number,'-',r.custom_number),
		hfv.area = r.`area`,
		hfv.create_time = r.`create_time`,
		hfv.update_time = r.`update_time`,
		hfv.is_delete = if(r.is_delete = 1,r.is_delete,h.is_delete)
	WHERE
		hfv.`house_id` = new.house_id AND hfv.`record_id` = new.room_id;
	
END$$
DELIMITER ;


/*分散式整租表更新触犯器*/
DROP TRIGGER IF EXISTS house_entirel_update;
DELIMITER $$
CREATE TRIGGER `house_entirel_update` AFTER UPDATE ON `house_entirel` FOR EACH ROW
BEGIN
	UPDATE `house_view` AS hv
	JOIN `house_entirel` AS he ON hv.`house_id` = he.`house_id` AND hv.`record_id` = 0
	JOIN `house` AS h ON he.`house_id` = h.`house_id`
	SET
		hv.occupancy_number = he.occupancy_number,
		hv.detain = he.detain,
		hv.pay = he.pay,
		hv.gender_restrictions = he.gender_restrictions,
		hv.status = he.status,
		hv.is_yytz = he.is_yytz,
		hv.is_yd = he.is_yd,
		hv.money = he.money,
		hv.is_delete = if(he.is_delete = 1,he.is_delete,h.is_delete)
	WHERE
		hv.`house_id` = new.house_id AND hv.`record_id` = 0;
	
	UPDATE `house_focus_view` AS hfv
	JOIN `room` AS r ON hfv.`house_id` = r.`house_id` AND hfv.`record_id` = r.`room_id`
	JOIN `house` AS h ON he.`house_id` = h.`house_id`
	SET
		hfv.occupancy_number = he.occupancy_number,
		hfv.detain = he.detain,
		hfv.pay = he.pay,
		hfv.gender_restrictions = he.gender_restrictions,
		hfv.status = he.status,
		hfv.is_yytz = he.is_yytz,
		hfv.is_yd = he.is_yd,
		hfv.money = he.money,
		hfv.is_delete = if(he.is_delete = 1,he.is_delete,h.is_delete)
	WHERE
		hfv.`house_id` = new.house_id AND hfv.`record_id` = 0;
END$$
DELIMITER ;


/*集中式表更新触犯器*/
DROP TRIGGER IF EXISTS room_focus_update;
DELIMITER $$
CREATE TRIGGER `room_focus_update` AFTER UPDATE ON `room_focus` FOR EACH ROW
BEGIN
	UPDATE `house_focus_view` AS hfv
	JOIN `room_focus` AS rf ON hfv.`house_id` = 0 AND hfv.`record_id` = rf.room_focus_id
	JOIN `flat` AS f ON rf.`flat_id` = f.flat_id
	SET
		hfv.occupancy_number = rf.custom_number,
		hfv.detain = rf.detain,
		hfv.pay = rf.pay,
		hfv.status = rf.status,
		hfv.is_yytz = rf.is_yytz,
		hfv.is_yd = rf.is_yd,
		hfv.money = rf.money,
		hfv.house_name = CONCAT(f.flat_name,rf.custom_number),
		hfv.custom_number = rf.custom_number,
		hfv.`count` = IF(LENGTH(rf.room_type) < 1,0,CONVERT(SUBSTRING_INDEX(rf.room_type,'t',1),UNSIGNED)),
		hfv.`hall` = rf.room_number,
		hfv.`toilet` = IF(LENGTH(rf.room_type) < 1,0,CONVERT(SUBSTRING_INDEX(rf.room_type,'t',-1),UNSIGNED)),
		hfv.area = rf.area,
		hfv.public_facilities = rf.room_config,
		hfv.custom_number = rf.create_time,
		hfv.update_time = rf.create_time,
		hfv.company_id = rf.company_id,
		hfv.create_uid = rf.create_uid,
		hfv.is_delete = if(rf.is_delete = 1,rf.is_delete,f.is_delete),
		hfv.owner_id = rf.owner_id
	WHERE
		hfv.`house_id` = 0 AND hv.`record_id` = new.room_focus_id;
END$$
DELIMITER ;


/*========================华丽丽的分割线==============================*/
/*分散式房间表删除触犯器*/
DROP TRIGGER IF EXISTS room_delete;
DELIMITER $$
CREATE TRIGGER `room_delete` AFTER DELETE ON `room` FOR EACH ROW
BEGIN
     DELETE FROM house_view WHERE house_id = old.house_id AND record_id = old.room_id;
     DELETE FROM house_focus_view WHERE house_id = old.house_id AND record_id = old.room_id;
END$$
DELIMITER ;


/*分散式整租表删除触犯器*/
DROP TRIGGER IF EXISTS house_entirel_delete;
DELIMITER $$
CREATE TRIGGER `house_entirel_delete` AFTER DELETE ON `house_entirel` FOR EACH ROW
BEGIN
    DELETE FROM house_view WHERE house_id = old.house_id AND record_id = 0;
    DELETE FROM house_focus_view WHERE house_id = old.house_id AND record_id = 0;
END$$
DELIMITER ;


/*集中式表删除触犯器*/
DROP TRIGGER IF EXISTS room_focus_delete;
DELIMITER $$
CREATE TRIGGER `room_focus_delete` AFTER DELETE ON `room_focus` FOR EACH ROW
BEGIN
	DELETE FROM house_focus_view WHERE house_id = 0 AND record_id = old.room_focus_id;
END$$
DELIMITER ;


/*========================华丽丽的分割线==============================*/
/*分散式房源更新触犯器*/
DROP TRIGGER IF EXISTS house_update;
DELIMITER $$
CREATE TRIGGER `house_update` AFTER UPDATE ON `house` FOR EACH ROW
BEGIN
	IF new.rental_way = 2/*合租*/
    THEN
    	UPDATE `house_view` AS hv
		JOIN `room` AS r ON hv.`house_id` = r.`house_id` AND hv.`record_id` = `room`.`room_id`
		JOIN `house` AS h ON r.`house_id` = h.`house_id`
		JOIN `community` AS c ON c.`community_id` = h.`community_id`
		SET
			hv.house_name = CONCAT(c.community_name,h.cost,'栋',h.unit,'单元',h.floor,h.number,'号',IF(r.room_type = 'main','主卧',IF(r.room_type = 'guest','客卧','次卧')),r.custom_number),
			hv.rental_way = h.rental_way,
			hv.community_id = h.community_id,
			hv.custom_number = CONCAT(h.custom_number,'-',r.custom_number),
			hv.cost = h.cost,
			hv.unit = h.unit,
			hv.number = h.number,
			hv.`count` = h.`count`,
			hv.hall = h.hall,
			hv.toilet = h.toilet,
			hv.total_floor = h.total_floor,
			hv.floor = h.floor,
			hv.public_facilities = h.public_facilities,
			hv.company_id = h.company_id,
			hv.create_uid = h.create_uid,
			hv.is_delete = if(r.is_delete = 1,r.is_delete,h.is_delete),
			hv.owner_id = h.owner_id
		WHERE
			hv.`house_id` = new.house_id;
		
		UPDATE `house_focus_view` AS hfv
		JOIN `room` AS r ON hfv.`house_id` = r.`house_id` AND hfv.`record_id` = r.`room_id`
		JOIN `house` AS h ON r.`house_id` = h.`house_id`
		JOIN `community` AS c ON c.`community_id` = h.`community_id`
		SET
			hfv.house_name = CONCAT(c.community_name,h.cost,'栋',h.unit,'单元',h.floor,h.number,'号',IF(r.room_type = 'main','主卧',IF(r.room_type = 'guest','客卧','次卧')),r.custom_number),
			hfv.rental_way = h.rental_way,
			hfv.community_id = h.community_id,
			hfv.custom_number = CONCAT(h.custom_number,'-',r.custom_number),
			hfv.cost = h.cost,
			hfv.unit = h.unit,
			hfv.number = h.number,
			hfv.`count` = h.`count`,
			hfv.hall = h.hall,
			hfv.toilet = h.toilet,
			hfv.total_floor = h.total_floor,
			hfv.floor = h.floor,
			hfv.public_facilities = h.public_facilities,
			hfv.company_id = h.company_id,
			hfv.create_uid = h.create_uid,
			hfv.is_delete = if(r.is_delete = 1,r.is_delete,h.is_delete),
			hfv.owner_id = h.owner_id
		WHERE
			hfv.`house_id` = new.house_id;
		
		
    ELSE  /*整租*/
        UPDATE `house_view` AS hv
		JOIN `house_entirel` AS he ON hv.`house_id` = he.`house_id` AND hv.`record_id` = 0
		JOIN `house` AS h ON he.`house_id` = h.`house_id`
		JOIN `community` AS c ON c.`community_id` = h.`community_id`
		SET
			hv.house_name = CONCAT(c.community_name,h.cost,'栋',h.unit,'单元',h.floor,h.number,'号'),
			hv.rental_way = h.rental_way,
			hv.community_id = h.community_id,
			hv.custom_number = h.custom_number,
			hv.cost = h.cost,
			hv.unit = h.unit,
			hv.number = h.number,
			hv.`count` = h.`count`,
			hv.hall = h.hall,
			hv.toilet = h.toilet,
			hv.area = h.area,
			hv.total_floor = h.total_floor,
			hv.floor = h.floor,
			hv.public_facilities = h.public_facilities,
			hv.create_time = h.create_time,
			hv.update_time = h.update_time,
			hv.company_id = h.company_id,
			hv.create_uid = h.create_uid,
			hv.is_delete = if(he.is_delete = 1,he.is_delete,h.is_delete),
			hv.owner_id = h.owner_id
		WHERE
			hv.`house_id` = new.house_id;
			
		UPDATE `house_focus_view` AS hfv
		JOIN `house_entirel` AS he ON hfv.`house_id` = he.`house_id` AND hv.`record_id` = 0
		JOIN `house` AS h ON he.`house_id` = h.`house_id`
		JOIN `community` AS c ON c.`community_id` = h.`community_id`
		SET
			hfv.house_name = CONCAT(c.community_name,h.cost,'栋',h.unit,'单元',h.floor,h.number,'号'),
			hfv.rental_way = h.rental_way,
			hfv.community_id = h.community_id,
			hfv.custom_number = h.custom_number,
			hfv.cost = h.cost,
			hfv.unit = h.unit,
			hfv.number = h.number,
			hfv.`count` = h.`count`,
			hfv.hall = h.hall,
			hfv.toilet = h.toilet,
			hfv.area = h.area,
			hfv.total_floor = h.total_floor,
			hfv.floor = h.floor,
			hfv.public_facilities = h.public_facilities,
			hfv.create_time = h.create_time,
			hfv.update_time = h.update_time,
			hfv.company_id = h.company_id,
			hfv.create_uid = h.create_uid,
			hfv.is_delete = if(he.is_delete = 1,he.is_delete,h.is_delete),
			hfv.owner_id = h.owner_id
		WHERE
			hfv.`house_id` = new.house_id;
    END IF;
END$$
DELIMITER ;


/*集中式公寓更新触犯器*/
DROP TRIGGER IF EXISTS flat_update;
DELIMITER $$
CREATE TRIGGER `flat_update` AFTER UPDATE ON `flat` FOR EACH ROW
BEGIN
	UPDATE `house_focus_view` AS hfv
	JOIN `room_focus` AS rf ON hfv.`house_id` = 0 AND hfv.`record_id` = rf.room_focus_id
	JOIN `flat` AS f ON rf.`flat_id` = f.flat_id
	SET
		hfv.rental_way = f.rental_way,
		hfv.is_delete = if(rf.is_delete = 1,rf.is_delete,f.is_delete)
	WHERE
		f.flat_id = new.flat_id;
END$$
DELIMITER ;