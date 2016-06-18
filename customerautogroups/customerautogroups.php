<?php

/**
 * 2007-2014 PrestaShop
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
 *  @copyright 2013-2015 Hennes Hervé
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  http://www.h-hennes.fr/blog/
 */
class customerautogroups extends Module
{

    public function __construct()
    {
        $this->author        = 'hhennes';
        $this->name          = 'customerautogroups';
        $this->tab           = 'hhennes';
        $this->version       = '0.3.2';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('Customers Auto groups');
        $this->description = $this->l('Add automaticaly customers to groups depending from params after registration');
    }

    /**
     * Installation du module
     */
    public function install()
    {
        if (!parent::install() || !$this->registerHook('actionCustomerAccountAdd'))
        {
            return false;
        }

        //Création d'une tab prestashop
        $tab             = new Tab();
        $tab->class_name = 'Rules';
        $tab->module     = $this->name;
        $tab->id_parent = Tab::getIdFromClassName('AdminParentCustomer');
        $languages       = Language::getLanguages();
        foreach ($languages as $lang) {
            $tab->name[$lang['id_lang']] = 'Customer Auto Groups';
        }
        try {
            $tab->save();
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }

        if ( !$this->_installSql())
            return false;

        return true;
    }

    /**
     * Installation des tables du module
     * @return boolean
     */
    protected function _installSql()
    {
        $sqlRule = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."autogroup_rule` (
                    `id_rule` int(11) NOT NULL AUTO_INCREMENT,
                    `condition_type` tinyint(2) NOT NULL,
                    `condition_field` varchar(255) NOT NULL,
                    `condition_operator` varchar(10) NOT NULL,
                    `condition_value` varchar(255) NOT NULL,
                    `id_group` int(11) NOT NULL,
                    `active` tinyint(1) unsigned NOT NULL,
                    `priority` tinyint(2) unsigned NOT NULL,
                    `stop_processing` tinyint(1) unsigned NOT NULL,
                    `default_group` tinyint(1) unsigned NOT NULL,
                    `clean_groups` tinyint(1) unsigned NOT NULL,
                    `date_add` datetime NOT NULL,
                    `date_upd` datetime NOT NULL,
                    PRIMARY KEY (`id_rule`)
                  ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;";

        $sqlRuleLang =  "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."autogroup_rule_lang` (
                        `id_rule` int(11) NOT NULL,
                        `id_lang` int(11) NOT NULL,
                        `name` varchar(255) DEFAULT NULL,
                        `description` text,
                        PRIMARY KEY (`id_rule`,`id_lang`)
                      ) ENGINE=InnoDB DEFAULT CHARSET=latin1;" ;

        if ( !Db::getInstance()->Execute($sqlRule) || !Db::getInstance()->Execute($sqlRuleLang))
            return false;

        return true;
    }

    /**
     * Désinstalation du module
     */
    public function uninstall()
    {
        //Suppression de la tab admin
        $id_tab = Tab::getIdFromClassName('rules');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            $tab->delete();
        }

        Db::getInstance("DROP TABLE IF EXISTS `"._DB_PREFIX_."autogroup_rule`");
        Db::getInstance("DROP TABLE IF EXISTS `"._DB_PREFIX_."autogroup_rule_lang`");

        return parent::uninstall();
    }

    /**
     * Hook Exécuté après la création d'un compte client
     * @param array $params : informations du compte client créé
     */
    public function hookActionCustomerAccountAdd($params)
    {
        $this->_processGroupRules($params['newCustomer']);
    }

    /**
     * Traitement des règles
     * @param Customer $customer
     */
    protected function _processGroupRules(Customer $customer)
    {

        //Inclusion de la classe des règles
        include_once _PS_MODULE_DIR_.'/customerautogroups/classes/AutoGroupRule.php';

        //Nombres d'adresses du client
        $customer_addresses = Customer::getAddressesTotalById($customer->id);

        //Si le client n'a pas d'adresse on ne peut pas traiter les règles liées aux données d'adresses.
        if (!$customer_addresses) {
            $sqlCond = ' AND condition_type = 1 ';
        } else {
            $sqlCond = '';
        }

        //Récupération des règles applicables au client
        $rules = Db::getInstance()->ExecuteS("SELECT * FROM "._DB_PREFIX_."autogroup_rule WHERE active=1 ".$sqlCond." ORDER BY priority");

        $customerGroups = array();
        foreach ($rules as $rule) {

            //Traitement des règles de type "Client"
            if ($rule['condition_type'] == AutoGroupRule::RULE_TYPE_CUSTOMER) {
                $obj = $customer;
            }
            //Traitement des règles de type Adresse
            else if ($rule['condition_type'] == AutoGroupRule::RULE_TYPE_ADDRESS ) {
                //Normalement vu que le client vient d'être créé il ne peut avoir qu'une adresse
                $id_address = Db::getInstance()->getValue("SELECT id_address FROM "._DB_PREFIX_."address WHERE id_customer=".$customer->id);
                $obj        = new Address($id_address);
            }
            //Type Inconnu : non traité
            else {
                //echo $this->l('Error : rule type unknow');
                continue;
            }

            //Il faut que la propriété de l'objet existe
            if (!property_exists($obj, $rule['condition_field'])) {
                //echo sprintf($this->l('Error : Unknow proprerty %s for class %'), $rule['condition_field'], get_class($obj));
                continue;
            }

            //On teste la conditon
            $ruleApplied = false;
            $defaultGroup = false;
            $cleanGroups = false;

            switch ($rule['condition_operator']) {

                case 'eq':
                    if ($obj->{$rule['condition_field']} == $rule['condition_value']) $ruleApplied = true;
                    break;

                case 'ne':
                    if ($obj->{$rule['condition_field']} != $rule['condition_value']) $ruleApplied = true;
                    break;

                case 'gt':
                    if ($obj->{$rule['condition_field']} > $rule['condition_value']) $ruleApplied = true;
                    break;

                case 'ge':
                    if ($obj->{$rule['condition_field']} >= $rule['condition_value']) $ruleApplied = true;
                    break;

                case 'lt':
                    if ($obj->{$rule['condition_field']} < $rule['condition_value']) $ruleApplied = true;
                    break;

                case 'le':
                    if ($obj->{$rule['condition_field']} <= $rule['condition_value']) $ruleApplied = true;
                    break;

                case 'LIKE %':
                    if ( preg_match('#'.$rule['condition_value'].'#',$obj->{$rule['condition_field']}) )
                        $ruleApplied = true;
                    break;
            }

            if ($ruleApplied) {
                //echo sprintf( $this->l('Rule %d applied for customer'),$rule['id_rule']).'<br />';
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

            //Si le flag de suppression des groupes
            if ( $cleanGroups )
                $customer->cleanGroups();

            //Application du groupe par défaut
            if ( $defaultGroup ) {
                $customer->id_default_group = $defaultGroup;
                try {
                    $customer->save();
                } catch (PrestaShopException $e) {
                    echo $e->getMessage();
                }
            }

            //Suppression des doublons
            $customerGroups = array_unique($customerGroups);

            //Ajout du client aux groups nécessaires
            $customer->addGroups($customerGroups);
        }

    }
}
