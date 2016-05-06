<?php
/**
 * Description of AutoGroupRuleCondition
 *
 * @author advisa
 */
abstract class AutoGroupRuleCondition
{

    /**
     * Conditions de Filtrage
     * @var type
     */
    protected $_operatorsList = array(
            array('id' => '=', 'value' => '='),
            array('id' => '!=', 'value' => '!='),
            array('id' => '>', 'value' => '>'),
            array('id' => '>=', 'value' => '>='),
            array('id' => '<', 'value' => '<'),
            array('id' => '<=', 'value' => '<='),
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
        //Liste des champs disponibles pour la classe Customer
        $fields         = get_class_vars($this->_objectClass);
        //$this->_fields = array();
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

}
