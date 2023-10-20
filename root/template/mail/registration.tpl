{% extends "mail/template.tpl" %}

{% block title %}{% trans %}Welcome to{% endtrans %} {{site_name}}{% endblock %}

{% block content %}
    <p class="mail-header" style="line-height: 22px; color: #112233; font-size: 20px; font-weight: bold;">
        {% trans %}Thanks for registration on the website!{% endtrans %}
    </p>
    <p style="font-size: 18px; line-height: 22px; color: #112233;">
        {% trans %}Login{% endtrans %}:&nbsp;
        <a style="" href="mailto:{{email}}">{{email}}</a>
    </p>
    <p style="font-size: 18px; line-height: 22px; color: #112233;">
        {% trans %}Password{% endtrans %}:&nbsp;
        <span style="">{{password}}</span>
    </p>
    <p style="font-size: 18px; line-height: 22px; color: #112233;">{% trans %}Please click on the link below to finish registration:{% endtrans %}</p>
    <p style="font-size: 18px; line-height: 22px; color: #112233;">
        <a href="{{site_url}}/{{language}}/register-complete?message=COMPLETE_REGISTRATION&code=%code%" style="font-size:16px;" target="_blank">{% trans %}Finish registration link{% endtrans %}.</a>
    </p>
{% endblock %}
