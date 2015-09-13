<?php
namespace Common\Model\Erp;

class ModularAuth extends \Common\Model\Erp
{
	/**
	 * 模块级别
	 * @var unknown
	 */
	const LEVEL_MODULAR = 1;
	/**
	 * 组级别
	 * @var unknown
	 */
	const LEVEL_GROUP = 2;
	/**
	 * 用户级别
	 * @var unknown
	 */
	const LEVEL_USER = 3;
	/**
	 * 增
	 * @var unknown
	 */
	const AUTH_INSERT = 1;
	/**
	 * 删除
	 * @var unknown
	 */
	const AUTH_DELETE = 2;
	/**
	 * 改
	 * @var unknown
	 */
	const AUTH_UPDATE = 3;
	/**
	 * 查
	 * @var unknown
	 */
	const AUTH_SELECT = 4;
}