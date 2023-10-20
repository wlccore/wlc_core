{% block html %}
<!DOCTYPE html>
<html>
{% endblock %}
<head>
    {% block head %}
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{seo.title}}</title>
    <meta name="description" content="{{seo.description}}" />
    <meta name="keywords" content="{{seo.keywords}}" />
    <meta name="robots" content="{% if env == 'prod' %}INDEX, FOLLOW{% else %}NOINDEX, NOFOLLOW{% endif %}" />
    <meta name="Author" content="Egamings, http://www.egamings.com" />
    <meta name="Generator" content="Softgamings, http://www.softgamings.com" />

    <link rel="shortcut icon" href="{{site}}/favicon.ico" type="image/x-icon" />
    {% endblock %}
    
    {% block css %}    
        
    {% endblock %}
    
    {% block js %}
        
    {% endblock %}    
</head>

{% block body %}
<body>
{% endblock %}

{% block header %}
    {% include "header.tpl" %}
{% endblock %} 

{% block center %}
    {% include "default.tpl" %}
{% endblock %}    

{% block footer %}
    {% include "footer.tpl" %}
{% endblock %}

{% include "defer_load.tpl" ignore missing %}
{% block defer_load %}
{% endblock %}

{% include "live_chat.tpl" ignore missing %}
{% block live_chat %}
{% endblock %}

<iframe style="display:none" src="{{site}}/{{language}}/caching"></iframe> 
</body>
</html>