{% extends "mail/template.tpl" %}

{% block title %}
    {% trans %}Password restore{% endtrans %}
{% endblock %}

{% block content %}
    <p style="font-size: 18px; line-height: 22px; font-weight: bold; color: #D6B25E; margin: 0 5% 40px;">{% trans %}Hello{% endtrans %} {{first_name}}!</p>
    <p style="font-size: 14px; line-height: 22px; color: #fff; margin: 0 5% 0;">{% trans %}Restore your password by clicking the link below.{% endtrans %}</p>
    <p style="font-size: 14px; line-height: 22px; color: #D6B25E; margin: 10px 5%;">%code%</p>
    <p style="font-size: 14px; line-height: 22px; color: #fff; margin: 0 5% 40px;">{% trans %}Link will be available for 30 minutes.{% endtrans %}</p>
{% endblock %}

