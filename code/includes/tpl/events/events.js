<?php return <<<JS

function toggleEvent(){
    var div = document.getElementById('msg-form-wrapper');
    var toggle = document.getElementById('msgr-toggle');
    if (div){
        if (div.style.display){
            div.style.display='';
            toggle.innerHTML = '';
        }else{
            div.style.display='none';
        }
    }
}

JS;
?>
