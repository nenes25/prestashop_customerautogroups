<?php

/**
 * Tests du bon fonctionnement du module CustomerAutoGroups
 *
 * @author hhennes <contact@h-hennes.fr>
 */
//@ToDo : Fixé en dur pour tests locaux, rendre dynamique
include_once '/var/www/public/prestashop/prestashop_1-6-1-1/config/config.inc.php';
include_once _PS_MODULE_DIR_ . '/customerautogroups/classes/AutoGroupRule.php';

class CustomerAutoGroupsTest extends PHPUnit_Framework_TestCase {

    /**
     * Avant l'exécution de la classe
     * Mise à jour de la config + suppression des données existantes
     */
    public static function setUpBeforeClass() {
        self::initConfig();
        self::cleanAllCustomCustomersGroup();
        self::cleanAutoGroupsRules();
    }

    /**
     * Test de creation d'une règle
     * @param array $rule
     * @dataProvider getAutoGroupRules
     */
    public function testcreateAutoGroupRule($rule) {

        //Création de la nouvelle règle
        $ruleModel = new AutoGroupRule();

        $languages = Language::getLanguages(true);
        foreach ($languages as $lang) {
            $ruleModel->name[$lang['id_lang']] = $rule['name'];
            $ruleModel->description[$lang['id_lang']] = $rule['description'];
        }

        $ruleModel->condition_type = $rule['condition_type'];
        $ruleModel->condition_field = $rule['condition_field'];
        $ruleModel->condition_operator = $rule['condition_operator'];
        $ruleModel->condition_value = $rule['condition_value'];
        $ruleModel->priority = $rule['priority'];
        $ruleModel->active = $rule['active'];
        $ruleModel->stop_processing = $rule['stop_processing'];
        $ruleModel->default_group = $rule['default_group'];
        $ruleModel->clean_groups = $rule['clean_groups'];

        //Gestion de la creation du groupe de destination
        $ruleModel->id_group = $this->_getCustomerGroupId($rule['customer_group_name']);

        //Sauvegarde de la nouvelle règle
        try {
            $ruleModel->save();
        } catch (PrestaShopException $e) {
            $this->fail('Erreur sauvegarde règle ' . $e->getMessage());
            return;
        }

        //La règle est enregistrée, on vérifie que les champs sont ok
        $this->assertEquals($ruleModel->name[1], $rule['name']);
        $this->assertEquals($ruleModel->description[1], $rule['description']);
        $this->assertEquals($ruleModel->condition_type, $rule['condition_type']);
        $this->assertEquals($ruleModel->condition_field, $rule['condition_field']);
        $this->assertEquals($ruleModel->condition_operator, $rule['condition_operator']);
        $this->assertEquals($ruleModel->condition_value, $rule['condition_value']);
        $this->assertEquals($ruleModel->priority, $rule['priority']);
        $this->assertEquals($ruleModel->active, $rule['active']);
        $this->assertEquals($ruleModel->stop_processing, $rule['stop_processing']);
        $this->assertEquals($ruleModel->default_group, $rule['default_group']);
        $this->assertEquals($ruleModel->clean_groups, $rule['clean_groups']);
    }

    /**
     * Dataprovider des données de test pour les règle
     */
    public function getAutoGroupRules() {

        return array(
            array('rule_us' => array(
                    'name' => 'US group auto', // Nom de la règle
                    'description' => 'Auto groups for us customers', //Description de la règle
                    'condition_type' => 2, // Type de condition 1 customer / 2 addresse
                    'condition_field' => 'id_country', //Champ condition
                    'condition_operator' => '=', // Operateur
                    'condition_value' => '21', // Valeur du champ
                    'customer_group_name' => 'us_group', // Groupe a assigner à l'utilisateur ( créé automatiquement )
                    'priority' => 0, //Priorité règle 0 Haute 10 Basse
                    'active' => 1, //Règle active 1 / Inactive 0
                    'stop_processing' => 1, //Arrêter de traiter les règles suivantes 1 Oui / 0 Non
                    'default_group' => 1, // Définir comme group par défaut pour l'utilisateur 1 Oui / 0 Non
                    'clean_groups' => 1, // Supprimer tous les autres groupes de l'utilisateur 1 Oui / 0 Non
                )),
            array('rule_fr' => array(
                    'name' => 'FR group auto',
                    'description' => 'Auto groups for french customers',
                    'condition_type' => 2, //1 customer , 2 addresse
                    'condition_field' => 'id_country',
                    'condition_operator' => '=',
                    'condition_value' => '8',
                    'customer_group_name' => 'fr_group',
                    'priority' => 0,
                    'active' => 1,
                    'stop_processing' => 1,
                    'default_group' => 1,
                    'clean_groups' => 1,
                )),
            array('male_users' => array(
                    'name' => 'Males user',
                    'description' => 'Auto groups for male users',
                    'condition_type' => 1, //1 customer , 2 addresse
                    'condition_field' => 'id_gender',
                    'condition_operator' => '=',
                    'condition_value' => '1',
                    'customer_group_name' => 'male_users',
                    'priority' => 1,
                    'active' => 1,
                    'stop_processing' => 0,
                    'default_group' => 0,
                    'clean_groups' => 0,
                )),
            
        );
    }

    
    /**
     * Tests de la bonne assignation
     * @dataProvider getCustomers
     * @param array $customerDatas 
     */
    public function testAutoAssignCustomerToGroup($customerDatas) {
        
        //Création du nouveau client ( et adresse si nécessaire )
        $customer = $this->_createCustomer($customerDatas);
        
        //Exécution du hook dans lequel les données sont traitées
        HookCore::exec('actionCustomerAccountAdd', array('newCustomer' => $customer));
        
        //On récupère les identifiants des groupes dans lequel le client doit etre présent
        $customerGroups = array();
        foreach( $customerDatas['expected_groups'] as $group ) {
            if ( $group == 'default'){
                $customerGroups[] = 3;
            }
            else {
                $id_group = $this->_getCustomerGroupId($group);
                $customerGroups[]= $id_group;
            }
        }
        
        //On récupère les groupes du clients
        $groups = $customer->getGroups();
        
        //On s'assure que les groupes du client correspondent à ceux choisis
        $this->assertEquals($groups,$customerGroups);
    }
    
    /**
     * Dataprovider des données de test pour les clients
     * (Statique)
     */
    public function getCustomers(){
        
        return array(
            array('customer_us' => array(
                'id_gender' => 1,
                'firstname' => 'herve',
                'lastname' => 'herve US',
                'email' => sprintf("test%s@test.com",time()),
                'password' => 'test2015',
                'add_address' => 1,
                'address_firstname' => 'herve',
                'address_lastname' => 'herve',
                'address_address1' => 'Manathan',
                'address_address2' => '',
                'address_postcode' => '20000',
                'address_city' => 'New York',
                'address_id_country' => 21,
                'address_id_state' => 0,
                'address_phone' => '0836656565',
                'expected_groups' => array('us_group'),
            )),
            array('customer_fr' => array(
                'id_gender' => 1,
                'firstname' => 'herve',
                'lastname' => 'herve FR',
                'email' => sprintf("test%s@test.com",time()),
                'password' => 'test2015',
                'add_address' => 1,
                'address_firstname' => 'herve',
                'address_lastname' => 'herve',
                'address_address1' => '16 rue des tests',
                'address_address2' => '',
                'address_postcode' => '67000',
                'address_city' => 'Strasbourg',
                'address_id_country' => 8,
                'address_id_state' => 0,
                'address_phone' => '0836656565',
                'expected_groups' => array('fr_group'),
            )),
            array('customer_male' => array(
                'id_gender' => 1,
                'firstname' => 'herve',
                'lastname' => 'male',
                'email' => sprintf("testmale%s@test.com",time()),
                'password' => 'test2015',
                'add_address' => 1,
                'address_firstname' => 'herve',
                'address_lastname' => 'herve',
                'address_address1' => '16 rue des tests',
                'address_address2' => '',
                'address_postcode' => '67000',
                'address_city' => 'Strasbourg',
                'address_id_country' => 15, //Pas france , ni us
                'address_id_state' => 0,
                'address_phone' => '0836656565',
                'expected_groups' => array('default','male_users'),
            ))
        );
        
    }
    
    /**
     * Création d'un client
     * @param array $datas
     */
    protected function _createCustomer($datas) {

        $customer = new Customer();
        $customer->firstname = $datas['firstname'];
        $customer->lastname = $datas['lastname'];
        $customer->id_gender = $datas['id_gender'];
        $customer->email = $datas['email'];
        $customer->passwd = ToolsCore::encrypt($datas['password']);
        //Données par défaut
        $customer->id_default_group = 3;

        try {
            $customer->save();
        } catch (PrestaShopException $e) {
            echo $e->getMessage();
        }

        //Création de l'adresse si spécifié
        if ($datas['add_address'] == 1) {
            $address = new Address();
            $address->firstname = $datas['address_firstname'];
            $address->lastname = $datas['address_lastname'];
            $address->address1 = $datas['address_address1'];
            $address->address2 = $datas['address_address2'];
            $address->postcode = $datas['address_postcode'];
            $address->city = $datas['address_city'];
            $address->phone = $datas['address_phone'];
            $address->id_country = $datas['address_id_country'];
            $address->id_state = $datas['address_id_state'];
            $address->id_customer = $customer->id;
            $address->alias = 'Automatic address';

            try {
                $address->save();
            } catch (PrestaShopException $e) {
                echo $e->getMessage();
            }
        }
        
        return $customer;
    }

    /**
     * Récupération de l'identifiant du groupe client
     * ( Création d'un groupe si nécessaire )
     * @param string $name : Nom du groupe
     * @return int Identifiant prestashop du groupe
     */
    protected function _getCustomerGroupId($name) {

        $group = Group::searchByName($name);

        if (!$group) {
            $newgroup = new Group();
            $languages = Language::getLanguages(true);
            foreach ($languages as $lang)
                $newgroup->name[$lang['id_lang']] = $name;
            $newgroup->reduction = 0;
            $newgroup->price_display_method = 1;
            $newgroup->show_prices = 1;

            try {
                $newgroup->save();
            } catch (PrestaShopException $e) {
                $this->fail('Erreur creation du groupe ' . $e->getMessage());
                exit();
            }
            return (int)$newgroup->id;
        } else {
            return (int)$group['id_group'];
        }
    }

    /**
     * Mise en place de la configuration nécessaire au module
     */
    public static function initConfig() {
        //Activation de l'inscription avec les adresses
        if (!Configuration::get('PS_REGISTRATION_PROCESS_TYPE') == 1)
            Configuration::set('PS_REGISTRATION_PROCESS_TYPE', 1);
    }


    
    /**
     * Suppression de tous les groupes clients non standards
     */
    public static function cleanAllCustomCustomersGroup() {

        $groups = Group::getGroups(1);
        foreach ($groups as $group) {
            if ($group['id_group'] > 3) {
                $groupModel = new Group($group['id_group']);
                try {
                    $groupModel->delete();
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
            }
        }
    }
    
    /**
     * Suppression de toutes les règles autoGroups
     */
    public static function cleanAutoGroupsRules(){
        Db::getInstance()->execute("TRUNCATE "._DB_PREFIX_."autogroup_rule");
        Db::getInstance()->execute("TRUNCATE "._DB_PREFIX_."autogroup_rule_lang");
    }
}
