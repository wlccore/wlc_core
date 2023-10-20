{% extends "mail/template.tpl" %}

{% block title %}
    {% trans %}Welcome to{% endtrans %} {{site_name}}
{% endblock %}

{% block content %}
    <h1 style="font-size: 18px; font-family: Verdana; font-weight: normal; margin: 0;">{% trans %}Welcome to{% endtrans %} {{site_name}}</h1>
    <p>{% trans %}Congratulations,{% endtrans %}<br/><br/>
    {% trans %}You have taken the first step to experience an amazing gaming journey
    with{% endtrans %} {{site_name}}. {% trans %}Thank you for validating your account. {% endtrans %}</p>
{% endblock %}