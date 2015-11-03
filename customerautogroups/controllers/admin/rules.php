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
include_once dirname(__FILE__).'/../../classes/AutoGroupRule.php';

class RulesController extends ModuleAdminController
{

    public function __construct()
    {
        $this->bootstrap  = true;
        $this->table      = 'autogroup_rule';
        $this->identifier = 'id_rule';
        $this->className  = 'AutoGroupRule';
        $this->lang       = true;
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->_orderWay  = 'ASC';

        $this->bulk_actions = array('delete' => array('text' => $this->l('Delete selected'), 'icon' => 'icon-trash', 'confirm' => $this->l('Delete selected items?')));

        $this->rules_types = array(1 => $this->l('Customer'), 2 => $this->l('Customer Address'));

        $this->fields_list = array(
            'id_rule' => array('title' => $this->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'),
            'name' => array('title' => $this->l('Name')),
            'condition_type' => array(
                'title' => $this->l('Condition Type'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'type' => 'select',
                'list' => $this->rules_types,
                'filter_key' => 'condition_type',
                'filter_type' => 'int',
            ),
            'id_group' => array('title' => $this->l('Id group'), 'class' => 'fixed-width-sm', 'align' => 'center',),
            'priority' => array('title' => $this->l('Priority'), 'align' => 'center', 'class' => 'fixed-width-sm'),
            'stop_processing' => array('title' => $this->l('Stop processing'), 'active' => 'stop_processing', 'type' => 'bool', 'align' => 'center'),
            'active' => array('title' => $this->l('Status'), 'active' => 'status', 'type' => 'bool', 'align' => 'center'),
        );

        parent::__construct();
    }

    /**
     * Affichage de la liste
     */
    public function renderList()
    {
        $this->tpl_list_vars['condition_type'] = $this->rules_types;
        return parent::renderList();
    }

    /**
     * Affichage du formulaire d'édition
     */
    public function renderForm()
    {
        //Liste des priorités
        $priorities   = array();
        for ($i = 0; $i <= 10; $i++)
            $priorities[] = array('id' => $i, 'value' => $i);

        //Liste des groupes clients
        $customerGroups = Group::getGroups($this->context->language->id);

        //Liste des cas de conditions
        $conditionsType = array(
            array('id' => '1' , 'value' => 'Customer'),
            array('id' => '2' , 'value' => 'Address'),
        );

        //Liste des opérateurs
        $operatorsList = array(
            array('id' => '=' , 'value' => '='),
            array('id' => '!=' , 'value' => '!='),
            array('id' => '>' , 'value' => '>'),
            array('id' => '>=' , 'value' => '>='),
            array('id' => '<' , 'value' => '<'),
            array('id' => '<=' , 'value' => '<='),
        );

        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Edit Rule'),
                'icon' => 'icon-cog'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('name'),
                    'name' => 'name',
                    'lang' => true,
                    'required' => true
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('description'),
                    'name' => 'description',
                    'lang' => true,
                    'required' => true
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Condition type'),
                    'name' => 'condition_type',
                    'required' => true,
                    'options' => array(
                        'query' => $conditionsType,
                        'id' => 'id',
                        'name' => 'value',
                        ),
                    'hint' => $this->l('The condition fields depend from the type')
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Condition Field'),
                    'name' => 'condition_field',
                    'required' => true
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Condition Operator'),
                    'name' => 'condition_operator',
                    'required' => true,
                     'options' => array(
                        'query' => $operatorsList,
                        'id' => 'id',
                        'name' => 'value',
                        )
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Condition Value'),
                    'name' => 'condition_value',
                    'required' => true
                ),
                //Bug sur la récupération du groupe
                array(
                    'type' => 'select',
                    'label' => $this->l('Customer Group'),
                    'name' => 'id_group',
                    'required' => true,
                    'options' => array(
                        'query' => $customerGroups,
                        'id' => 'id_group',
                        'name' => 'name',
                    ),
                    'hint' => $this->l('Select the group in which the customer will be added')
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Priority'),
                    'name' => 'priority',
                    'required' => true,
                    'options' => array(
                        'query' => $priorities,
                        'id' => 'id',
                        'name' => 'value',
                    )
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Active'),
                    'name' => 'active',
                    'required' => true,
                    'values' => array(
                        array('id' => 'on', 'value' => 1, 'label' => $this->l('Yes')),
                        array('id' => 'off', 'value' => 0, 'label' => $this->l('No')),
                    )
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Stop processing further rules'),
                    'name' => 'stop_processing',
                    'required' => true,
                    'values' => array(
                        array('id' => 'on', 'value' => 1, 'label' => $this->l('Yes')),
                        array('id' => 'off', 'value' => 0, 'label' => $this->l('No')),
                    ),
                    'hint' => $this->l('If enable this rule will be the latest processed')
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save')
            )
        );

        return parent::renderForm();
    }
}
?>
