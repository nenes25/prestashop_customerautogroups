<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AutoGroupRuleConditionAddress
 *
 * @author advisa
 */
class AutoGroupRuleConditionAddress extends AutoGroupRuleCondition
{

    //Champs clients exclus de la condition
    protected $_excludedFields  = array(
        'id',
        'secure_key',
        'ip_registration_newsletter',
        'id_default_group',
        'last_passwd_gen',
        'last_passwd_gen',
        'passwd',
        'definition',
        'date_add',
        'date_upd'
        );

    protected $_objectClass = 'Address';
}
