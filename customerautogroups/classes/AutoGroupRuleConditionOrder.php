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
            array('id' => 'eq', 'value' => '='),
            array('id' => 'ne', 'value' => '!='),
            array('id' => 'gt', 'value' => '>'),
            array('id' => 'ge', 'value' => '>='),
            array('id' => 'lt', 'value' => '<'),
            array('id' => 'le', 'value' => '<='),
            array('id' => 'LIKE %', 'value' => 'LIKE %'),
            //Nouveaux opérateurs
            array('id' => 'contains_product', 'value' => 'contains product'),
            array('id' => 'sum_orders', 'value' => 'Sum orders'),
            array('id' => 'validated_orders', 'value' => 'validated_orders'),
        );

   /**
     *
     * Vérifie si la règle matche les conditions
    * Fonction additionnelle pour les
     *
     * @param type $rule
     * @param type $obj
     * @return boolean
     */
    public function matchCondition($rule,$obj){

        switch ($rule['condition_operator']) {

                case 'eq':
                    if ($obj->{$rule['condition_field']} == $rule['condition_value']) {
                        return true;
                    }
                    break;

                case 'ne':
                    if ($obj->{$rule['condition_field']} != $rule['condition_value']) {
                        return true;
                    }
                    break;

                case 'gt':
                    if ($obj->{$rule['condition_field']} > $rule['condition_value']) {
                        return true;
                    }
                    break;

                case 'ge':
                    if ($obj->{$rule['condition_field']} >= $rule['condition_value']) {
                        return true;
                    }
                    break;

                case 'lt':
                    if ($obj->{$rule['condition_field']} < $rule['condition_value']) {
                        return true;
                    }
                    break;

                case 'le':
                    if ($obj->{$rule['condition_field']} <= $rule['condition_value']) {
                        return true;
                    }
                    break;

                case 'LIKE %':
                    if ( preg_match('#'.$rule['condition_value'].'#',$obj->{$rule['condition_field']}) ) {
                        return true;
                    }
                    break;
                case 'contains_product':
                    return $this->_containsProducts($rule['condition_value'], $obj);
                case 'sum_orders':
                    return $this->_sumOrders($rule['condition_field'], $rule['condition_value'], $obj);
                case 'validated_orders':
                    return $this->_validatedOrders($rule['condition_value'], $obj);
                default:
                    return false;
                    break;
            }
    }

    /**
     * Vérifie si la commande contient certains produits
     * @param string $products_sku : Liste de produits contenus dans la commande ( separés par des , )
     * @param Order $order
     */
    protected function _containsProducts($products_sku,$order) {

        $productsToFind = explode(',',$products_sku);
        $productsFinds = array();

        $products = $order->getProducts(false);

        foreach( $products as $product) {
            if ( in_array($product['reference'],$productsToFind)) {
                $productsFinds[] = $product['reference'];
            }
        }
        return $productsFinds == $productsToFind;
    }

    /**
     * Vérifie si la somme totale des commandes de l'utilisateur est supérieure à un certain montant
     *
     * @param string $type : Total HT ou TTC
     * @param float $sum : Somme de commandes à dépasser
     * @param Order $order
     * @return boolean
     */
    protected function _sumOrders($type,$sum,$order) {

        $allowedTypes = array('total_paid_tax_excl','total_paid_tax_incl');

        if ( !in_array($type, $allowedTypes))
                return false;

        $id_customer = $order->id_customer;

        $ordersSum = Db::getInstance()->getValue("SELECT SUM(".$type.") FROM "._DB_PREFIX_."orders WHERE validated=1 AND id_customer=".$order->id_customer);

        return ( $ordersSum >= $sum );
    }

    /**
     * Vérifie le nombre de commandes validées de l'utilisateur
     *
     * @param int $orderNumber : Nombre de commandes à valider
     * @param Order $order
     * @return boolean
     */
    protected function _validatedOrders($orderNumber,$order) {
        $customerOrdersNb = Db::getInstance()->getValue("SELECT COUNT(*) FROM "._DB_PREFIX_."orders WHERE validated=1 AND id_customer=".$order->id_customer);
        return ( $customerOrdersNb >= $orderNumber );
    }


}
