<?php

/**
 * Description of AutoGroupRuleConditionOrder
 *
 * @author advisa
 */
class AutoGroupRuleConditionOrder extends AutoGroupRuleCondition
{

    //Champs order exclus de la condition
    protected $_excludedFields = array(
        'force_id',
        'id',
        'id_customer',
        'id_shop',
        'id_currency',
        'id_lang',
        'secure_key',
        'current_state',
        'id_cart',
        'id_address_delivery',
        'id_address_invoice',
        'id_shop_group',
        'invoice_number',
        'delivery_numbert',
        'definition',
        'date_add',
        'date_upd');

    protected $_objectClass = 'Order';

     /**
     * Nouvelles conditions de filtrages
     */
    protected $_operatorsList = array(
            array('id' => '=', 'value' => '='),
            array('id' => '!=', 'value' => '!='),
            array('id' => '>', 'value' => '>'),
            array('id' => '>=', 'value' => '>='),
            array('id' => '<', 'value' => '<'),
            array('id' => '<=', 'value' => '<='),
            array('id' => 'LIKE %', 'value' => 'LIKE %'),
            //Nouveaux opérateurs
            array('id' => 'contains_product', 'value' => 'contains product'),
            array('id' => 'sum_orders', 'value' => 'Sum orders'),
            array('id' => 'validated_orders', 'value' => 'validated_orders'),
        );

}
