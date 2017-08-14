{% block title %}
[{{application.id}}] Updated shared files
{% endblock %}

{% block body %}
{% if travis %}
This pull request has been created by {{application.name}} due to a commit on
the [{{travis.repository}} repository](https://github.com/{{travis.repository}}).
{% else %}
This pull request has been created manually by {{application.name}}.
{% endif %}

If the modified files do not meet the requirements of this projects,
**please do not modify them** as your **changes** will be **overwritten** by the next update.

Instead, modify the templates on the _{{application.id}}_ repository.
{% endblock %}
