{% extends "mail/template.tpl" %}

{% block title %}
    {% trans %}Deposit confirmation{% endtrans %}
{% endblock %}

{% block content %}
<div class="mail-deposit-welcome" style="color: green; font-size: 12px;">
{% trans %}Dear{% endtrans %},  {$customer->FirstName} {$customer->LastName}!
</div>
<div>
{% trans %}Deposit success notification. Your balance{% endtrans %}: {{balance}} {{currency}}.
</div>
{% endblock %}
