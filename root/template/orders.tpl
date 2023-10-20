{% extends "index.tpl" %}

{% block content %}
<div style="padding:100px;">
    ID User: <input type="text" placeholder="" name="IDUser" id="id_user" style="border:1px solid #AAA; margin:2px 0; width:100px"/> 
    <input type="button" value="Информация" onclick="getInfo()" style="border:1px solid #AAA; background:#EEE; width:150px" />
    <div style="width:100%; padding:10px 0;"><small>Response:</small> <span id="req_status"></span><br/></div>
    <div style="width:100%; padding:10px 0;"><small>Order:</small><div></div><span id="order_status"></span><br/></div>
                    
    <br/><br/>
    
    {% for v in store %}
    <div style="border-bottom:1px dashed #CCC; margin-bottom:30px; padding-bottom:20px;">
        <div style="float:left; width:600px; margin-right:40px;">
            {{ v.Name[language] }}<br/>
            {{ v.Description[language] }}<br/>

            {% if v.Img[language] %}
            <img src="{{ v.Img[language] }}" />
            {% endif %}
        </div>
        <div style="float:left">
            Price: {{ v.Price }} <br/>
            Qnt: {{ v.Quantity }} <br/>
            <input type="button" style="border:1px solid #CCC; pading:5px 15px;" onclick="buy({{ v.ID }})" value="Buy" />
        </div>
        <div style="clear:both;"></div>
     </div>
    {% endfor %}
    
</div>

<script type="text/javascript">

function buy(id) {
    var query = {
            type: 'POST',
            data: {
                control: 'store_buy',
                IDUser: $('#id_user').val(),
                IDItem: id,
            },
            async: (ecommpayAsPopup==1?false:true),
            success: function(answer) {
                    try {
                        answ = eval('('+answer+')');
                    } catch(er) {
                        $('#order_status').html('<font color="#F00">'+answer+'</font>');
                        return '';
                    }
                    
                    
                
                    data = new Array();
                    for(k in answ) {
                        data.push('<div style="float:left; width:120px">'+k+':</div>'+answ[k]+'<div style="clear:both"></div>');
                    }
                    $('#order_status').html(data.join("\n"));
                        
                    
           }
           
    }
    
    ajax(query);
    
    return false;
}

function getInfo(el) {
    var query = {
            type: 'POST',
            data: {
                control: 'loyalty',
                IDUser: $('#id_user').val(),
            },
            async: (ecommpayAsPopup==1?false:true),
            success: function(answer) {
                    try {
                        answ = eval('('+answer+')');
                    } catch(er) {
                        $('#req_status').html('<font color="#F00">'+answer+'</font>');
                        return '';
                    }
                    
                    
                    if(typeof(answ[0])!='undefined') {
                        data = new Array();
                        for(k in answ[0]) {
                            if(k=='Balance') {
                                $('#req_status').html('Balance: '+answ[0][k]);
                                return;
                            }
                        }
                    }
                    
                    $('#req_status').html(answ);
           }
           
    }
    
    ajax(query);
    
    return false;
}

</script>
{% endblock %}

