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

