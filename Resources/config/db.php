<?php
//数据库连接配置
$config = array(
	'default'=>array(//默认链接
		'driver'=>'Pdo',
		'username'=>'root',
		'password'=>'123456',
		'dsn'=>'mysql:dbname=jzsaas_cs;host=192.168.199.203',
		'driver_options'=>array(
			\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8'
		),
// 		'aserver'=>array(//从库
// 			array(
// 				'driver'=>'Pdo',
// 				'username'=>'root',
// 				'password'=>'123456',
// 				'dsn'=>'mysql:dbname=jzsaas_cs;host=192.168.199.203',
// 				'driver_options'=>array(
// 						\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8'
// 				)
// 			)
// 		)
	),
	'jooozo'=>array(//ERP用户库
		'driver'=>'Pdo',
		'username'=>'root',
		'password'=>'123456',
		'dsn'=>'mysql:dbname=jooozo;host=192.168.199.203',
		'driver_options'=>array(
				\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8'
		)
	),
	'jooozo_erp_url'=>array(
		'login'=>'http://erp.jooozo.cn/login'
	),
	'log'=>array(//日志
		'driver'=>'Pdo',
		'username'=>'root',
		'password'=>'123456',
		'dsn'=>'mysql:dbname=jzsaas_cs;host=192.168.199.203',
		'driver_options'=>array(
				\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8'
		)
	),
	'plugins'=>array(//插件
		'driver'=>'Pdo',
		'username'=>'root',
		'password'=>'123456',
		'dsn'=>'mysql:dbname=plugins;host=192.168.199.203',
		'driver_options'=>array(
				\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8'
		)
	),
	'jz_user'=>array(//租客端库
		'driver'=>'Pdo',
		'username'=>'root',
		'password'=>'123456',
		'dsn'=>'mysql:dbname=jz_user;host=192.168.199.203',
		'driver_options'=>array(
				\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8'
		)
	),
	'box'=>array(//数辑盒
		'driver'=>'Pdo',
		'username'=>'root',
		'password'=>'123456',
		'dsn'=>'mysql:dbname=jzmanage;host=192.168.199.203',
		'driver_options'=>array(
				\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8'
		)
	),
	'snapshot'=>array(//快照
		'driver'=>'Pdo',
		'username'=>'root',
		'password'=>'123456',
		'dsn'=>'mysql:dbname=snapshot;host=192.168.199.203',
		'driver_options'=>array(
				\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8'
		)
	),
);
$config['erp'] = $config['default'];
return $config;