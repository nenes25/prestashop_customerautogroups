<?php

/**
 * 2007-2016 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    Hennes Hervé <contact@h-hennes.fr>
 *  @copyright 2013-2016 Hennes Hervé
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  http://www.h-hennes.fr/blog/
 */
class AutoGroupRule extends ObjectModel
{

    public $id;
    public $name;
    public $description;
    public $condition_type;
    public $condition_field;
    public $condition_operator;
    public $condition_value;
    public $id_group;
    public $active;
    public $priority;
    public $stop_processing;
    public $default_group;
    public $clean_groups;
    public $date_add;
    public $date_upd;

    public static $definition = array(
        'table' => 'autogroup_rule',
        'primary' => 'id_rule',
        'multilang' => true,
        'fields' => array(
            'condition_type' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml','size' => 40),
            'condition_field' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml',),
            'condition_operator' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml',),
            'condition_value' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 254),
            'id_group' => array('type' => self::TYPE_INT),
            'active' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'priority' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'stop_processing' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'default_group' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'clean_groups' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            // Lang fields
            'name' => array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 254),
            'description' => array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml'),
        ),
    );

}
?>
