<?php
/**
 * 2007-2018 Hennes Hervé
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to contact@h-hennes.fr so we can send you a copy immediately.
 *
 * @author    Hennes Hervé <contact@h-hennes.fr>
 * @copyright 2007-2018 Hennes Hervé
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * http://www.h-hennes.fr/blog/
 */

include_once dirname(__FILE__).'/../../classes/AutoGroupRule.php';

class RulesController extends ModuleAdminController
{
    //Champs clients exclus de la condition
    protected $_customerExcludedFields = array('id', 'secure_key',
        'ip_registration_newsletter', 'id_default_group', 'last_passwd_gen',
        'last_passwd_gen','passwd', 'definition');

    //Champs clients
    protected $customerFields;

    //Champs addresse exclus de la condition
    protected $_addressExcludedFields = array('force_id', 'id_customer',
        'id_manufacturer', 'id_warehouse', 'id_supplier', 'deleted',
        'definition');

    //Champs adresse
    protected $addressFields = array();

    //Données du champ condition_field
    protected $_conditionFieldDatas;

    //Types de règles
    protected $rules_types = array();

    public function __construct()
    {
        $this->bootstrap  = true;
        $this->table      = 'autogroup_rule';
        $this->identifier = 'id_rule';
        $this->className  = 'AutoGroupRule';
        $this->lang       = true;
        $this->context = Context::getContext();
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->rules_types = array(
            AutoGroupRule::RULE_TYPE_CUSTOMER => $this->l('Customer'),
            AutoGroupRule::RULE_TYPE_ADDRESS => $this->l('Customer Address')
        );

        $this->_select = 'IF ( a.`condition_type` = '.AutoGroupRule::RULE_TYPE_CUSTOMER.' , \''.$this->l('Customer').'\',\''.$this->l('Customer Address').'\') AS condition_type_name,'
                . 'gl.name AS group_name';
        $this->_join = 'LEFT JOIN `'._DB_PREFIX_.'group_lang` gl ON (gl.`id_group` = a.`id_group` AND gl.`id_lang` = '.(int) $this->context->language->id.')';
        $this->_orderWay  = 'ASC';

        $this->bulk_actions = array('delete' => array('text' => $this->l('Delete selected'), 'icon' => 'icon-trash', 'confirm' => $this->l('Delete selected items?')));

        $groupsArray = array();
        $groups = Group::getGroups($this->context->language->id);
        foreach ($groups as $group) {
            $groupsArray[$group['id_group']] = $group['name'];
        }

        $this->fields_list = array(
            'id_rule' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ),
            'name' => array('title' => $this->l('Name')),
            'condition_type_name' => array(
                'title' => $this->l('Condition Type'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'type' => 'select',
                'list' => $this->rules_types,
                'filter_key' => 'condition_type',
                'filter_type' => 'int',
            ),
            'group_name' => array(
                'title' => $this->l('Group'),
                'class' => 'fixed-width-sm',
                'align' => 'center',
                'type' => 'select',
                'list' => $groupsArray,
                'filter_key' => 'a!id_group',
                'filter_type' => 'int',
            ),
            'priority' => array(
                'title' => $this->l('Priority'),
                'align' => 'center',
                'class' => 'fixed-width-sm'
            ),
            'stop_processing' => array(
                'title' => $this->l('Stop processing'),
                'active' => 'stop_processing',
                'type' => 'bool',
                'align' => 'center'
            ),
            'default_group'  => array(
                'title' => $this->l('Default customer Group'),
                'active' => 'default_group',
                'type' => 'bool',
                'align' => 'center'
            ),
            'clean_groups'  => array(
                'title' => $this->l('Delete all others groups'),
                'active' => 'clean_groups',
                'type' => 'bool',
                'align' =>
                'center'
            ),
            'active' => array(
                'title' => $this->l('Status'),
                'active' => 'status',
                'type' => 'bool',
                'align' => 'center'
            ),
        );

        parent::__construct();
    }


    /**
     * Définition des médias du controller
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addJS(_MODULE_DIR_.'customerautogroups/views/js/admin/customerautogroups.js');
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
            array('id' => AutoGroupRule::RULE_TYPE_CUSTOMER, 'value' => 'Customer'),
            array('id' => AutoGroupRule::RULE_TYPE_ADDRESS, 'value' => 'Address'),
        );

        //Liste des champs disponibles pour la classe Customer
        $fields         = get_class_vars('Customer');
        $this->customerFields = array();
        foreach ($fields as $key => $value) {
            if (!in_array($key, $this->_customerExcludedFields)) {
                $this->customerFields[] = array('id' => $key, 'value' => $key);
            }
        }

        //Liste des champs disponibles pour la classe Customer
        $afields       = get_class_vars('Address');
        foreach ($afields as $key => $value) {
            if (!in_array($key, $this->_addressExcludedFields)) {
                $this->addressFields[] = array('id' => $key, 'value' => $key);
            }
        }

        //Gestion de l'affichage du champ "condition_field" pour les règles déjà existantes
        if (Tools::getValue('id_rule')) {
            $rule = new AutoGroupRule(Tools::getValue('id_rule'));
            if ($rule->condition_type == AutoGroupRule::RULE_TYPE_CUSTOMER) {
                $this->_conditionFieldDatas = $this->customerFields;
            } else {
                $this->_conditionFieldDatas = $this->addressFields;
            }
        } else {
            $this->_conditionFieldDatas = $this->customerFields;
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
        for ($i = 0; $i <= 10; $i++) {
            $priorities[] = array('id' => $i, 'value' => $i);
        }

        //Liste des groupes clients
        $customerGroups = Group::getGroups($this->context->language->id);

        //Liste des opérateurs
        $operatorsList = array(
            array('id' => 'eq', 'value' => '='),
            array('id' => 'ne', 'value' => '!='),
            array('id' => 'gt', 'value' => '>'),
            array('id' => 'ge', 'value' => '>='),
            array('id' => 'lt', 'value' => '<'),
            array('id' => 'le', 'value' => '<='),
            array('id' => 'LIKE %', 'value' => 'LIKE %'),
            array('id' => 'NOT LIKE %', 'value' => 'NOT LIKE %'),
            array('id' => 'IN', 'value' => 'IN'),
            array('id' => 'NOT IN', 'value' => 'NOT IN'),
        );

        //Avec Prestashop < 1.6 le type switch n'existe pas il faut le remplacer par un radio
        if (_PS_VERSION_ < '1.6') {
            $switch_type = 'radio';
        } else {
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
     * Mise à jour ajax des champs des règles en fonction du type
     */
    public function displayAjaxUpdateConditionTypeSelect()
    {
        $this->_initForm();

        if (Tools::getValue('condition_type') == AutoGroupRule::RULE_TYPE_CUSTOMER) {
            $fields = $this->customerFields;
        } else {
            $fields = $this->addressFields;
        }

        foreach ($fields as $field) {
            echo '<option value="'.$field['id'].'">'.$field['value'].'</option>';
        }
    }

    /**
     * Surcharge de la fonction de traduction sur PS 1.7 et supérieur.
     * La fonction globale ne fonctionne pas
     * @param type $string
     * @param type $class
     * @param type $addslashes
     * @param type $htmlentities
     * @return type
     */
    protected function l($string, $class = null, $addslashes = false, $htmlentities = true)
    {
        if ( _PS_VERSION_ >= '1.7') {
            return Context::getContext()->getTranslator()->trans($string);
        } else {
            return parent::l($string, $class, $addslashes, $htmlentities);
        }
    }
}
