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

class AutoGroupRuleProcessor {


    protected $_rules = array();

    /** Instance du client */
    protected $_customer;


    /**
     * Instanciation de la classe avec le client
     * @param Customer $customer Instance du client à traiter
     */
    public function __construct(Customer $customer) {
        $this->_customer = $customer;
    }

    /**
     * Traitement des règles liées aux commandes
     * @param array $params
     */
    public function processOrderRules($params) {

        $order = $params['order'];

        //Récupération des règles applicables à la commande
        $this->_getRules('order');

        $customerGroups = array();
        $defaultGroup = false;
        $cleanGroups = false;

        foreach ( $this->_rules as $rule ) {

            //Il faut que la propriété de l'objet existe
            if (!property_exists($order, $rule['condition_field'])) {
                 continue;
            }

            if ( $this->_ruleMatch($rule, $order ,'order')) {
                $customerGroups[] = $rule['id_group'];

                //Si la règle doit être la dernière à être traitée, on sors de la boucle
                if ($rule['stop_processing'] == 1) {
                    if ($rule['default_group'] == 1) {
                        $defaultGroup = $rule['id_group'];
                    }
                    if ($rule['clean_groups'] == 1) {
                        $cleanGroups = true;
                    }
                    break;
                }
            }

        }
        //Ajout du client aux groupes nécessaires
        if ( sizeof($customerGroups)) {
           $this->_addCustomerGroups($customerGroups, $cleanGroups, $defaultGroup);
        }

    }

    /**
     * Récupérations des règles applicables
     * @param string || array $types
     */
    protected function _getRules($type){

        $rules = Db::getInstance()->ExecuteS("SELECT * "
                . "FROM "._DB_PREFIX_."autogroup_rule "
                . "WHERE active=1 AND condition_type IN ('".$type."')"
                . "ORDER BY priority");

        $this->_rules = $rules;
    }

    /**
     * Vérifie si la rule matche la condition
     * @param array $rule
     * @param Object $obj
     * @param string $type
     */
    protected function _ruleMatch($rule,$obj,$type = ''){

        //Appel de la classe des conditions du type pour gérer le match
        $conditionType = 'AutoGroupRuleCondition'.ucfirst($type);
        $condition = new $conditionType();
        return $condition->matchCondition($rule,$obj);
    }

    /**
     * Ajout du client aux groupes
     * @param array $customerGroups Groupes du client
     * @param boolean $cleanGroups Flag pour supprimer les autres groupes
     * @param boolean||int $defaultGroup Groupe par défaut du client
     */
    protected function _addCustomerGroups($customerGroups,$cleanGroups = false,$defaultGroup = false) {

        //Si le flag de suppression des groupes
            if ( $cleanGroups )
                $this->_customer->cleanGroups();

            //Application du groupe par défaut
            if ( $defaultGroup ) {
                $this->_customer->id_default_group = $defaultGroup;
                try {
                    $this->_customer->save();
                } catch (PrestaShopException $e) {
                    echo $e->getMessage();
                }
            }

            //Suppression des doublons
            $customerGroups = array_unique($customerGroups);

            //Ajout du client aux groups nécessaires
            $this->_customer->addGroups($customerGroups);
        }

    }
