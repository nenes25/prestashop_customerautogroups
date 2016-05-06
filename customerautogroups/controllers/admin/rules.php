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
//@ToDO: Rajouter les classes dans l'autoload
include_once dirname(__FILE__).'/../../classes/AutoGroupRule.php';
include_once dirname(__FILE__).'/../../classes/AutoGroupRuleCondition.php';
include_once dirname(__FILE__).'/../../classes/AutoGroupRuleConditionCustomer.php';
include_once dirname(__FILE__).'/../../classes/AutoGroupRuleConditionAddress.php';
include_once dirname(__FILE__).'/../../classes/AutoGroupRuleConditionOrder.php';

class RulesController extends ModuleAdminController
{
    //Données du champ condition_field
    protected $_conditionFieldDatas;

    //Opérateurs de comparaison
    protected $_operatorsList;

    //Tableau global des rules ( actualisé automatiquement, possibilité d'ajout de conditions via le hook )
    protected $_rulesDatas = array();

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
            'default_group'  => array('title' => $this->l('Default customer Group'), 'active' => 'default_group', 'type' => 'bool', 'align' => 'center'),
            'clean_groups'  => array('title' => $this->l('Delete all others groups'), 'active' => 'clean_groups', 'type' => 'bool', 'align' => 'center'),
            'active' => array('title' => $this->l('Status'), 'active' => 'status', 'type' => 'bool', 'align' => 'center'),
        );

        parent::__construct();
    }


    /**
     * Définition des médias du controller
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addJS(_MODULE_DIR_.'customerautogroups/views/admin/js/customerautogroups.js');
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
     * Initialisation des variables du formulaires
     */
    protected function _initForm()
    {
        //Liste des cas de conditions
        $this->conditionsType = array(
            array('id' => 'customer', 'value' => 'Customer'),
            array('id' => 'address', 'value' => 'Address'),
            array('id' => 'order', 'value' => 'Order'),
        );

        //Liste des champs disponibles pour la classe Customer
        $conditionsCustomer = new AutoGroupRuleConditionCustomer();
        $this->_rulesDatas['customer'] = array(
            'fields' => $conditionsCustomer->getRuleFields(),
            'conditions' => $conditionsCustomer->getOperatorList(),
        );

        //Liste des champs disponibles pour la classe Address
        $conditionsAddress = new AutoGroupRuleConditionAddress();
        $this->_rulesDatas['address'] = array(
            'fields' => $conditionsAddress->getRuleFields(),
            'conditions' => $conditionsAddress->getOperatorList(),
        );

        //Liste des champs disponibles pour la classe Order
        $conditionsOrder = new AutoGroupRuleConditionOrder();
        $this->_rulesDatas['order'] = array(
            'fields' => $conditionsOrder->getRuleFields(),
            'conditions' => $conditionsOrder->getOperatorList(),
        );

        //Gestion de l'affichage des champ "condition_field" et "condition_operator" pour les règles déjà existantes
        if ( Tools::getValue('id_rule')) {

            $rule = new AutoGroupRule(Tools::getValue('id_rule'));
            $this->_conditionFieldDatas = $this->_rulesDatas[$rule->condition_type]['fields'];
            $this->_operatorsList = $this->_rulesDatas[$rule->condition_type]['conditions'];
        }
        else {
            $this->_conditionFieldDatas = $this->_rulesDatas['customer']['fields'];
            $this->_operatorsList = $this->_rulesDatas['customer']['conditions'];
        }
    }

    /**
     * Affichage du formulaire d'édition
     */
    public function renderForm()
    {
        $this->_initForm();

        //Liste des priorités
        $priorities   = array();
        for ($i = 0; $i <= 10; $i++)
            $priorities[] = array('id' => $i, 'value' => $i);

        //Liste des groupes clients
        $customerGroups = Group::getGroups($this->context->language->id);

        //Avec Prestashop < 1.6 le type switch n'existe pas il faut le remplacer par un radio
        if ( _PS_VERSION_ < '1.6') {
            $switch_type = 'radio';
        }
        else {
            $switch_type = 'switch';
        }


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
                    'class' => 'condition_type',
                    'options' => array(
                        'query' => $this->conditionsType,
                        'id' => 'id',
                        'name' => 'value',
                    ),
                    'hint' => $this->l('The condition fields depend from the type')
                ),
                //Le changement du select précédent entraine le changement de celui-ci
                array(
                    'type' => 'select',
                    'label' => $this->l('Condition Field'),
                    'name' => 'condition_field',
                    'class' => 'condition_field',
                    'required' => true,
                    'options' => array(
                        'query' => $this->_conditionFieldDatas,
                        'id' => 'id',
                        'name' => 'value',
                    ),
                ),
                //Les règles de type Commande entraine le chagement de ce champs
                array(
                    'type' => 'select',
                    'label' => $this->l('Condition Operator'),
                    'name' => 'condition_operator',
                    'class' => 'condition_operator',
                    'required' => true,
                    'options' => array(
                        'query' => $this->_operatorsList,
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
                    'type' => $switch_type,
                    'label' => $this->l('Active'),
                    'name' => 'active',
                    'class' => 't',
                    'required' => true,
                    'values' => array(
                        array('id' => 'on', 'value' => 1, 'label' => $this->l('Yes')),
                        array('id' => 'off', 'value' => 0, 'label' => $this->l('No')),
                    )
                ),
                array(
                    'type' => $switch_type,
                    'label' => $this->l('Stop processing further rules'),
                    'name' => 'stop_processing',
                    'class' => 't',
                    'required' => true,
                    'values' => array(
                        array('id' => 'on', 'value' => 1, 'label' => $this->l('Yes')),
                        array('id' => 'off', 'value' => 0, 'label' => $this->l('No')),
                    ),
                    'hint' => $this->l('If enable this rule will be the latest processed')
                ),
                array(
                    'type' => $switch_type,
                    'label' => $this->l('Default customer Group'),
                    'name' => 'default_group',
                    'class' => 't',
                    'required' => true,
                    'values' => array(
                        array('id' => 'on', 'value' => 1, 'label' => $this->l('Yes')),
                        array('id' => 'off', 'value' => 0, 'label' => $this->l('No')),
                    ),
                    'hint' => $this->l('Only works for rule which stop processing')
                ),
                array(
                    'type' => $switch_type,
                    'label' => $this->l('Delete all others groups'),
                    'name' => 'clean_groups',
                    'class' => 't',
                    'required' => true,
                    'values' => array(
                        array('id' => 'on', 'value' => 1, 'label' => $this->l('Yes')),
                        array('id' => 'off', 'value' => 0, 'label' => $this->l('No')),
                    ),
                    'hint' => $this->l('Only works for rule which stop processing')
                ),
                //Token pour action ajax
                array(
                    'type' => 'hidden',
                    'label' => 'token',
                    'name' => 'token',
                    'value' => $this->token,
                    'required'=> false,
                )
            ),
            'submit' => array(
                'title' => $this->l('Save')
            )
        );

        return parent::renderForm();
    }

    /**
     * Ajout du bouton d'ajout dans la toolbar
     */
    public function initPageHeaderToolbar()
    {
        $this->page_header_toolbar_btn['new_rule'] = array(
            'href' => self::$currentIndex.'&addautogroup_rule&token='.$this->token,
            'desc' => $this->l('Add new rule', null, null, false),
            'icon' => 'process-icon-new'
        );


        parent::initPageHeaderToolbar();
    }

    /**
     * Mise à jour ajax des champs
     */
    public function displayAjaxUpdateSelects(){

        $this->_initForm();

        $type = Tools::getValue('condition_type','customer');

        $fields = $this->_rulesDatas[$type]['fields'];
        $fieldsHtml = '';
        foreach ( $fields as $field ) {
            $fieldsHtml .='<option value="'.$field['id'].'">'.$field['value'].'</option>';
        }

        $operators = $this->_rulesDatas[$type]['conditions'];
        $operatorHtml = '';
        foreach ( $operators as $operator ) {
            $operatorHtml .='<option value="'.$operator['id'].'">'.$operator['value'].'</option>';
        }

        $return = array(
            'fields' => $fieldsHtml,
            'operators' => $operatorHtml
        );

        echo json_encode($return);
    }
}
?>
