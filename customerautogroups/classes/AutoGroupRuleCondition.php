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
abstract class AutoGroupRuleCondition
{

    /**
     * Conditions de Filtrage
     * @var type
     */
    protected $_operatorsList = array(
            array('id' => 'eq', 'value' => '='),
            array('id' => 'ne', 'value' => '!='),
            array('id' => 'gt', 'value' => '>'),
            array('id' => 'ge', 'value' => '>='),
            array('id' => 'lt', 'value' => '<'),
            array('id' => 'le', 'value' => '<='),
            array('id' => 'LIKE %', 'value' => 'LIKE %'),
        );

    /** Champs de la conditions */
    protected $_fields = array();

    /** Champs exclus de la condition */
    protected $_excludedFields = array();

    /** Classe de l'objet à récupérer pour la condition */
    protected $_objectClass = false;

    /**
     * Récupération des champs de la règle
     */
    public function getRuleFields(){

        if ( !$this->_objectClass ) {
            throw new PrestaShopExceptionCore("Error please give an object class to get fields");
            return false;
        }
        //Liste des champs disponibles pour la classe
        $fields         = get_class_vars($this->_objectClass);

        foreach ($fields as $key => $value) {
            if (!in_array($key, $this->_excludedFields)) {
                $this->_fields[] = array('id' => $key, 'value' => $key);
            }
        }
        return $this->_fields;
    }


    /**
     * Récupération des conditions possible
     * Surcharger la classe enfant pour ajouter des conditions
     */
    public function getOperatorList() {

        return $this->_operatorsList;
    }

    /**
     *
     * Vérifie si la règle matche les conditions
     *
     * @param type $rule
     * @param type $obj
     * @return boolean
     */
    public function matchCondition($rule,$obj){

        switch ($rule['condition_operator']) {

                case '=':
                    if ($obj->{$rule['condition_field']} == $rule['condition_value']) {
                        return true;
                    }
                    break;

                case '!=':
                    if ($obj->{$rule['condition_field']} != $rule['condition_value']) {
                        return true;
                    }
                    break;

                case '>':
                    if ($obj->{$rule['condition_field']} > $rule['condition_value']) {
                        return true;
                    }
                    break;

                case '>=':
                    if ($obj->{$rule['condition_field']} >= $rule['condition_value']) {
                        return true;
                    }
                    break;

                case '<':
                    if ($obj->{$rule['condition_field']} < $rule['condition_value']) {
                        return true;
                    }
                    break;

                case '<=':
                    if ($obj->{$rule['condition_field']} <= $rule['condition_value']) {
                        return true;
                    }
                    break;

                case 'LIKE %':
                    if ( preg_match('#'.$rule['condition_value'].'#',$obj->{$rule['condition_field']}) ) {
                        return true;
                    }
                    break;
                default:
                    return false;
            }
    }

}
