<?php return <<<JS

function msgToggle(id){
    var msg = document.getElementById('msg-body-'+id);
    var toggle = document.getElementById('msg-tog-'+id);
    if (msg){
        //alert('got message div');
        if (msg.style.display){
            msg.style.display='';
            toggle.innerHTML = 'Hide';
        }else{
            msg.style.display='none';
            toggle.innerHTML = 'Show';
        }
    }
}

JS;
?>
