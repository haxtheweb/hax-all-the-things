<?php

/* partials/register.html.twig */
class __TwigTemplate_2571a32f76f56d740e3446886f477aca007982a23b5bed250985091087c18363 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        // line 1
        $this->parent = $this->loadTemplate("partials/base.html.twig", "partials/register.html.twig", 1);
        $this->blocks = [
            'body' => [$this, 'block_body'],
            'instructions' => [$this, 'block_instructions'],
            'form' => [$this, 'block_form'],
        ];
    }

    protected function doGetParent(array $context)
    {
        return "partials/base.html.twig";
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        // line 2
        $context["scope"] = ((($context["scope"] ?? null)) ? (($context["scope"] ?? null)) : ("data."));
        // line 1
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 4
    public function block_body($context, array $blocks = [])
    {
        // line 5
        echo "<body id=\"admin-login-wrapper\">
    <section id=\"admin-login\" class=\"default-glow-shadow ";
        // line 6
        echo twig_escape_filter($this->env, ($context["classes"] ?? null), "html", null, true);
        echo "\">
        <h1>
            ";
        // line 8
        echo twig_escape_filter($this->env, ($context["title"] ?? null), "html", null, true);
        echo "
        </h1>

        ";
        // line 11
        $this->loadTemplate("partials/messages.html.twig", "partials/register.html.twig", 11)->display($context);
        // line 12
        echo "
        ";
        // line 13
        $this->displayBlock('instructions', $context, $blocks);
        // line 14
        echo "
        <form method=\"post\" action=\"";
        // line 15
        echo twig_escape_filter($this->env, ($context["base_url_relative"] ?? null), "html", null, true);
        echo "\">
            <div class=\"padding\">
            ";
        // line 17
        $this->displayBlock('form', $context, $blocks);
        // line 18
        echo "
            ";
        // line 19
        echo $this->env->getExtension('Grav\Common\Twig\TwigExtension')->nonceFieldFunc("form", "form-nonce");
        echo "
            </div>
        </form>
    </section>
</body>
";
    }

    // line 13
    public function block_instructions($context, array $blocks = [])
    {
    }

    // line 17
    public function block_form($context, array $blocks = [])
    {
    }

    public function getTemplateName()
    {
        return "partials/register.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  85 => 17,  80 => 13,  70 => 19,  67 => 18,  65 => 17,  60 => 15,  57 => 14,  55 => 13,  52 => 12,  50 => 11,  44 => 8,  39 => 6,  36 => 5,  33 => 4,  29 => 1,  27 => 2,  11 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("{% extends 'partials/base.html.twig' %}
{% set scope = scope ?: 'data.' %}

{% block body %}
<body id=\"admin-login-wrapper\">
    <section id=\"admin-login\" class=\"default-glow-shadow {{ classes }}\">
        <h1>
            {{ title }}
        </h1>

        {% include 'partials/messages.html.twig' %}

        {% block instructions %}{% endblock %}

        <form method=\"post\" action=\"{{ base_url_relative }}\">
            <div class=\"padding\">
            {% block form %}{% endblock %}

            {{ nonce_field('form', 'form-nonce')|raw }}
            </div>
        </form>
    </section>
</body>
{% endblock %}", "partials/register.html.twig", "/var/www/html/platforms/gravcms/user/plugins/admin/themes/grav/templates/partials/register.html.twig");
    }
}
