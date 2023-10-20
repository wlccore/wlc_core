{% extends "index.tpl" %}

{% block content %}
<iframe id="content-frame" src="{{site}}/content/{{language}}/{{route}}"></iframe>
{% endblock %}