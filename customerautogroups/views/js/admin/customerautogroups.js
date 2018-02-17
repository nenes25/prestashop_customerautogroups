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
$(function(){

   //Changement du type de condition dans le formulaire
   $('#condition_type').change(function(){
       $.ajax({
           type: 'POST',
           url : currentIndex,
           data : {
               'ajax' : true,
               'action' : 'UpdateConditionTypeSelect',
               'token': $('#token').val(),
               'condition_type' : $(this).val()
           },
           complete: function(msg){
               $('#condition_field').html('').html(msg.responseText);
           }
       })
   });
});

