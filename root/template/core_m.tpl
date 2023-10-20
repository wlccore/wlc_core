{% block html %}
<!DOCTYPE html>
<html>
{% endblock %}
<head>
    <meta charset="utf-8" />
    {% block head %}
    <title>{{seo.title}}</title>
    <meta name="description" content="{{seo.description}}" />
    <meta name="keywords" content="{{seo.keywords}}" />
    <meta name="robots" content="{% if cfg.env == 'prod' %}index, follow{% else %}noindex, nofollow{% endif %}" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <meta name="Author" content="Egamings, http://www.egamings.com" />
    <meta name="Generator" content="Softgamings, http://www.softgamings.com" />

    <link rel="shortcut icon" href="{{img}}/favicon.ico" type="image/x-icon" />
    {% endblock %}
    
    {% block css %}    
        
    {% endblock %}
    
    {% block js %}
        
    {% endblock %}    
</head>
<body data-role="state scrolling">

<div data-role="page" class="wrp {{page}} {{language}}" data-theme="e">

{% block header %}
    {% include "header.tpl" %}
{% endblock %} 

{% block center %}
    {% include "default.tpl" %}
{% endblock %}    

{% block footer %}
    {% include "footer.tpl" %}
{% endblock %}

</div>

{% include "defer_load.tpl" ignore missing %}
{% block defer_load %}
{% endblock %}

{% include "live_chat.tpl" ignore missing %}
{% block live_chat %}
{% endblock %}

</body>
</html>