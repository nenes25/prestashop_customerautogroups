<?php
/**
 * Description of AutoGroupRuleConditionCustomer
 *
 * @author advisa
 */
class AutoGroupRuleConditionCustomer extends AutoGroupRuleCondition
{
    /** Champs exclus de la condition */
    protected $_excludedFields = array(
        'id',
        'secure_key',
        'ip_registration_newsletter',
        'id_default_group',
        'id_guest',
        'id_shop_list',
        'force_id',
        'last_passwd_gen',
        'last_passwd_gen',
        'passwd',
        'definition',
        'date_add',
        'date_upd'
        );

    protected $_objectClass = 'Customer';

}
