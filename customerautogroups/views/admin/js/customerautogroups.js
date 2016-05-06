$(function(){

   //Changement du type de condition dans le formulaire
   $('#condition_type').change(function(){
       $.ajax({
           type: 'POST',
           url : currentIndex,
           data : {
               'ajax' : true,
               'action' : 'UpdateSelects',
               'token': $('#token').val(),
               'condition_type' : $(this).val()
           },
           complete: function(response){
               var data = $.parseJSON(response.responseText);
               $('#condition_field').html('').html(data.fields);
               $('#condition_operator').html('').html(data.operators);
           }
       })
   });
});

