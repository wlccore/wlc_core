{% extends "mail/template.tpl" %}

{% block title %}
    {% trans %}Social registration confirmation{% endtrans %}
{% endblock %}

{% block content %}
    <h1 style="font-size: 18px; font-family: Verdana; font-weight: normal; margin: 0;">{% trans %}Greeting.{% endtrans %}</h1>
    <p style="line-height: 22px; color: #112233; font-family: Verdana; font-size: 13px; margin: 5px 0;">{% trans %}You asked to link new social account to your current account{% endtrans %}</p>
    <p style="line-height: 22px; color: #112233; font-family: Verdana; font-size: 13px; margin: 5px 0;">{% trans %}To finish linking your accounts, please use this link:{% endtrans %} <a href="{{url}}/{{code}}">{{url}}/{{code}}</a></p>
{% endblock %}
