<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* core/modules/ckeditor/templates/ckeditor-settings-toolbar.html.twig */
class __TwigTemplate_154827da75edddf515e92823bf74d98c65fdd7ffa3c8248e333e68101f522e51 extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->env->getExtension('\Twig\Extension\SandboxExtension');
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 16
        ob_start(function () { return ''; });
        // line 17
        echo "  <fieldset role=\"form\" aria-labelledby=\"ckeditor-button-configuration ckeditor-button-description\">
    <legend id=\"ckeditor-button-configuration\">";
        // line 18
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Toolbar configuration"));
        echo "</legend>
    <div class=\"fieldset-wrapper\">
      <div id=\"ckeditor-button-description\" class=\"fieldset-description\">";
        // line 21
        echo t("Move a button into the <em>Active toolbar</em> to enable it, or into the list of <em>Available buttons</em> to disable it. Buttons may be moved with the mouse or keyboard arrow keys. Toolbar group names are provided to support screen reader users. Empty toolbar groups will be removed upon save.", array());
        // line 24
        echo "</div>
      <div class=\"ckeditor-toolbar-disabled clearfix\">
        ";
        // line 27
        echo "        <div class=\"ckeditor-toolbar-available\">
          <label for=\"ckeditor-available-buttons\">";
        // line 28
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Available buttons"));
        echo "</label>
          <ul id=\"ckeditor-available-buttons\" class=\"ckeditor-buttons clearfix\" role=\"form\" data-drupal-ckeditor-button-sorting=\"source\">
          ";
        // line 30
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["disabled_buttons"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["disabled_button"]) {
            // line 31
            echo "             <li";
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, $context["disabled_button"], "attributes", [], "any", false, false, true, 31), "addClass", [0 => "ckeditor-button"], "method", false, false, true, 31), 31, $this->source), "html", null, true);
            echo ">";
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, $context["disabled_button"], "value", [], "any", false, false, true, 31), 31, $this->source), "html", null, true);
            echo "</li>
          ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['disabled_button'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 33
        echo "          </ul>
        </div>
        ";
        // line 36
        echo "        <div class=\"ckeditor-toolbar-dividers\">
          <label for=\"ckeditor-multiple-buttons\">";
        // line 37
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Button divider"));
        echo "</label>
          <ul id=\"ckeditor-multiple-buttons\" class=\"ckeditor-multiple-buttons\" role=\"form\" data-drupal-ckeditor-button-sorting=\"dividers\">
          ";
        // line 39
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["multiple_buttons"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["multiple_button"]) {
            // line 40
            echo "            <li";
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, $context["multiple_button"], "attributes", [], "any", false, false, true, 40), "addClass", [0 => "ckeditor-multiple-button"], "method", false, false, true, 40), 40, $this->source), "html", null, true);
            echo ">";
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, $context["multiple_button"], "value", [], "any", false, false, true, 40), 40, $this->source), "html", null, true);
            echo "</li>
          ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['multiple_button'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 42
        echo "          </ul>
        </div>
      </div>
      ";
        // line 46
        echo "      <div class=\"clearfix\">
        <label id=\"ckeditor-active-toolbar\">";
        // line 47
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Active toolbar"));
        echo "</label>
      </div>
      <div data-toolbar=\"active\" role=\"form\" class=\"ckeditor-toolbar ckeditor-toolbar-active clearfix\">
        <ul class=\"ckeditor-active-toolbar-configuration\" role=\"presentation\" aria-label=\"";
        // line 50
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("CKEditor toolbar and button configuration."));
        echo "\">
        ";
        // line 51
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["active_buttons"] ?? null));
        $context['_iterated'] = false;
        foreach ($context['_seq'] as $context["_key"] => $context["button_row"]) {
            // line 52
            echo "          <li class=\"ckeditor-row\" role=\"group\" aria-labelledby=\"ckeditor-active-toolbar\">
            <ul class=\"ckeditor-toolbar-groups clearfix\">
            ";
            // line 54
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable($context["button_row"]);
            foreach ($context['_seq'] as $context["group_name"] => $context["button_group"]) {
                // line 55
                echo "              <li class=\"ckeditor-toolbar-group\" role=\"presentation\" data-drupal-ckeditor-type=\"group\" data-drupal-ckeditor-toolbar-group-name=\"";
                echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($context["group_name"], 55, $this->source), "html", null, true);
                echo "\" tabindex=\"0\">
                <h3 class=\"ckeditor-toolbar-group-name\" id=\"ckeditor-toolbar-group-aria-label-for-";
                // line 56
                echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, $context["button_group"], "group_name_class", [], "any", false, false, true, 56), 56, $this->source), "html", null, true);
                echo "\">";
                echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($context["group_name"], 56, $this->source), "html", null, true);
                echo "</h3>
                <ul class=\"ckeditor-buttons ckeditor-toolbar-group-buttons\" role=\"toolbar\" data-drupal-ckeditor-button-sorting=\"target\" aria-labelledby=\"ckeditor-toolbar-group-aria-label-for-";
                // line 57
                echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, $context["button_group"], "group_name_class", [], "any", false, false, true, 57), 57, $this->source), "html", null, true);
                echo "\">
                ";
                // line 58
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, $context["button_group"], "buttons", [], "any", false, false, true, 58));
                foreach ($context['_seq'] as $context["_key"] => $context["active_button"]) {
                    // line 59
                    echo "                  <li";
                    echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, $context["active_button"], "attributes", [], "any", false, false, true, 59), "addClass", [0 => ((twig_get_attribute($this->env, $this->source, $context["active_button"], "multiple", [], "any", false, false, true, 59)) ? ("ckeditor-multiple-button") : ("ckeditor-button"))], "method", false, false, true, 59), 59, $this->source), "html", null, true);
                    echo ">";
                    echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, $context["active_button"], "value", [], "any", false, false, true, 59), 59, $this->source), "html", null, true);
                    echo "</li>
                ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['_key'], $context['active_button'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 61
                echo "                </ul>
              </li>
            ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['group_name'], $context['button_group'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 64
            echo "            </ul>
          </li>
        ";
            $context['_iterated'] = true;
        }
        if (!$context['_iterated']) {
            // line 67
            echo "          <li>
            <ul class=\"ckeditor-buttons\"></ul>
          </li>
        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['button_row'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 71
        echo "        </ul>
      </div>
    </div>
  </fieldset>
";
        $___internal_75d18bb9a3a43cc875b834816158c4f0a752d97b8f5c921272edf65900caeef1_ = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 16
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(twig_spaceless($___internal_75d18bb9a3a43cc875b834816158c4f0a752d97b8f5c921272edf65900caeef1_));
    }

    public function getTemplateName()
    {
        return "core/modules/ckeditor/templates/ckeditor-settings-toolbar.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  197 => 16,  190 => 71,  181 => 67,  174 => 64,  166 => 61,  155 => 59,  151 => 58,  147 => 57,  141 => 56,  136 => 55,  132 => 54,  128 => 52,  123 => 51,  119 => 50,  113 => 47,  110 => 46,  105 => 42,  94 => 40,  90 => 39,  85 => 37,  82 => 36,  78 => 33,  67 => 31,  63 => 30,  58 => 28,  55 => 27,  51 => 24,  49 => 21,  44 => 18,  41 => 17,  39 => 16,);
    }

    public function getSourceContext()
    {
        return new Source("", "core/modules/ckeditor/templates/ckeditor-settings-toolbar.html.twig", "/var/www/html/platforms/drupal-9/core/modules/ckeditor/templates/ckeditor-settings-toolbar.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = array("apply" => 16, "trans" => 21, "for" => 30);
        static $filters = array("t" => 18, "escape" => 31, "spaceless" => 16);
        static $functions = array();

        try {
            $this->sandbox->checkSecurity(
                ['apply', 'trans', 'for'],
                ['t', 'escape', 'spaceless'],
                []
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }
}
