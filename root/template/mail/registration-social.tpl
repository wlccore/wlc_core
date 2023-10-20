{% extends "mail/template.tpl" %}

{% block title %}
    {% trans %}Registration confirmation{% endtrans %}
{% endblock %}

{% block content %}
    <h1 style="font-size: 18px; font-family: Verdana; font-weight: normal; margin: 0;">Greetings, %first_name% %last_name%!</h1>
    <p style="line-height: 22px; color: #112233; font-family: Verdana; font-size: 13px; margin: 5px 0;">You have successfully registered</p>
    <div style="background-color: #edeec7; padding: 20px; margin-top: 30px; display: inline-block;">
        <p style="line-height: 22px; color: #112233; margin: 0; font-family: Verdana; font-size: 16px; margin-bottom: 15px;">Access information</p>
        <p style="line-height: 22px; color: #112233; margin: 0; font-family: Verdana; font-size: 14px; margin-bottom: 5px;">Email: <strong>%email%</strong></p>
        <p style="line-height: 22px; color: #112233; margin: 0; font-family: Verdana; font-size: 14px; margin-bottom: 5px;">Access via social network</p>
    </div>
{% endblock %}