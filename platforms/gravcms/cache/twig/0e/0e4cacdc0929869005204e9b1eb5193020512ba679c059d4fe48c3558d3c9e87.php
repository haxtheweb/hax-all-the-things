<?php

/* forms/fields/text/text.html.twig */
class __TwigTemplate_eafc9bb10ad7171d7868304ba74e979dc7224d93a259c9059835c64136947984 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        // line 1
        $this->parent = $this->loadTemplate("forms/field.html.twig", "forms/fields/text/text.html.twig", 1);
        $this->blocks = [
            'prepend' => [$this, 'block_prepend'],
            'input_attributes' => [$this, 'block_input_attributes'],
            'append' => [$this, 'block_append'],
            'input' => [$this, 'block_input'],
        ];
    }

    protected function doGetParent(array $context)
    {
        return "forms/field.html.twig";
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_prepend($context, array $blocks = [])
    {
        // line 4
        if ($this->getAttribute(($context["field"] ?? null), "prepend", [])) {
            // line 5
            echo "    <div class=\"form-input-addon form-input-prepend\">
        ";
            // line 6
            if ($this->getAttribute($this->getAttribute($this->getAttribute($this->getAttribute(($context["grav"] ?? null), "twig", [], "any", false, true), "twig", [], "any", false, true), "filters", [], "any", false, true), "tu", [], "array", true, true)) {
                // line 7
                echo $this->env->getExtension('Grav\Plugin\Admin\Twig\AdminTwigExtension')->tuFilter($this->getAttribute(($context["field"] ?? null), "prepend", []));
            } else {
                // line 9
                echo $this->env->getExtension('Grav\Common\Twig\TwigExtension')->translate($this->getAttribute(($context["field"] ?? null), "prepend", []));
            }
            // line 11
            echo "    </div>
";
        }
    }

    // line 15
    public function block_input_attributes($context, array $blocks = [])
    {
        // line 16
        echo "    type=\"text\"
    ";
        // line 17
        if ($this->getAttribute(($context["field"] ?? null), "size", [])) {
            echo "size=\"";
            echo twig_escape_filter($this->env, $this->getAttribute(($context["field"] ?? null), "size", []), "html", null, true);
            echo "\"";
        }
        // line 18
        echo "    ";
        if (($this->getAttribute(($context["field"] ?? null), "minlength", [], "any", true, true) || $this->getAttribute($this->getAttribute(($context["field"] ?? null), "validate", [], "any", false, true), "min", [], "any", true, true))) {
            echo "minlength=\"";
            echo twig_escape_filter($this->env, (($this->getAttribute(($context["field"] ?? null), "minlength", [], "any", true, true)) ? (_twig_default_filter($this->getAttribute(($context["field"] ?? null), "minlength", []), $this->getAttribute($this->getAttribute(($context["field"] ?? null), "validate", []), "min", []))) : ($this->getAttribute($this->getAttribute(($context["field"] ?? null), "validate", []), "min", []))), "html", null, true);
            echo "\"";
        }
        // line 19
        echo "    ";
        if (($this->getAttribute(($context["field"] ?? null), "maxlength", [], "any", true, true) || $this->getAttribute($this->getAttribute(($context["field"] ?? null), "validate", [], "any", false, true), "max", [], "any", true, true))) {
            echo "maxlength=\"";
            echo twig_escape_filter($this->env, (($this->getAttribute(($context["field"] ?? null), "maxlength", [], "any", true, true)) ? (_twig_default_filter($this->getAttribute(($context["field"] ?? null), "maxlength", []), $this->getAttribute($this->getAttribute(($context["field"] ?? null), "validate", []), "max", []))) : ($this->getAttribute($this->getAttribute(($context["field"] ?? null), "validate", []), "max", []))), "html", null, true);
            echo "\"";
        }
        // line 20
        echo "    ";
        $this->displayParentBlock("input_attributes", $context, $blocks);
        echo "
";
    }

    // line 23
    public function block_append($context, array $blocks = [])
    {
        // line 24
        echo "    ";
        if ($this->getAttribute(($context["field"] ?? null), "append", [])) {
            // line 25
            echo "        <div class=\"form-input-addon form-input-append\">
            ";
            // line 26
            if ($this->getAttribute($this->getAttribute($this->getAttribute($this->getAttribute(($context["grav"] ?? null), "twig", [], "any", false, true), "twig", [], "any", false, true), "filters", [], "any", false, true), "tu", [], "array", true, true)) {
                // line 27
                echo $this->env->getExtension('Grav\Plugin\Admin\Twig\AdminTwigExtension')->tuFilter($this->getAttribute(($context["field"] ?? null), "append", []));
            } else {
                // line 29
                echo $this->env->getExtension('Grav\Common\Twig\TwigExtension')->translate($this->getAttribute(($context["field"] ?? null), "append", []));
            }
            // line 31
            echo "        </div>
    ";
        }
    }

    // line 35
    public function block_input($context, array $blocks = [])
    {
        // line 36
        if (($this->getAttribute(($context["field"] ?? null), "prepend", []) || $this->getAttribute(($context["field"] ?? null), "append", []))) {
            // line 37
            echo "    ";
            $context["field"] = twig_array_merge(($context["field"] ?? null), ["wrapper_classes" => "form-input-addon-wrapper"]);
        }
        // line 39
        $this->displayParentBlock("input", $context, $blocks);
        echo "
";
    }

    public function getTemplateName()
    {
        return "forms/fields/text/text.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  118 => 39,  114 => 37,  112 => 36,  109 => 35,  103 => 31,  100 => 29,  97 => 27,  95 => 26,  92 => 25,  89 => 24,  86 => 23,  79 => 20,  72 => 19,  65 => 18,  59 => 17,  56 => 16,  53 => 15,  47 => 11,  44 => 9,  41 => 7,  39 => 6,  36 => 5,  34 => 4,  31 => 3,  11 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("{% extends \"forms/field.html.twig\" %}

{% block prepend %}
{% if field.prepend %}
    <div class=\"form-input-addon form-input-prepend\">
        {% if grav.twig.twig.filters['tu'] is defined %}
            {{- field.prepend|tu|raw -}}
        {% else %}
            {{- field.prepend|t|raw -}}
        {% endif %}
    </div>
{% endif %}
{% endblock %}

{% block input_attributes %}
    type=\"text\"
    {% if field.size %}size=\"{{ field.size }}\"{% endif %}
    {% if field.minlength is defined or field.validate.min is defined %}minlength=\"{{ field.minlength | default(field.validate.min) }}\"{% endif %}
    {% if field.maxlength is defined or field.validate.max is defined %}maxlength=\"{{ field.maxlength | default(field.validate.max) }}\"{% endif %}
    {{ parent() }}
{% endblock %}

{% block append %}
    {% if field.append %}
        <div class=\"form-input-addon form-input-append\">
            {% if grav.twig.twig.filters['tu'] is defined %}
                {{- field.append|tu|raw -}}
            {% else %}
                {{- field.append|t|raw -}}
            {% endif %}
        </div>
    {% endif %}
{% endblock %}

{% block input %}
{% if field.prepend or field.append %}
    {% set field = field|merge({'wrapper_classes': 'form-input-addon-wrapper'}) %}
{% endif %}
{{ parent() }}
{% endblock %}
", "forms/fields/text/text.html.twig", "/var/www/html/platforms/gravcms/user/plugins/form/templates/forms/fields/text/text.html.twig");
    }
}
