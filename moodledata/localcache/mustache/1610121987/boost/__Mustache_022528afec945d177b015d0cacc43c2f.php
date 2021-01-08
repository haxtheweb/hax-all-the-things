<?php

class __Mustache_022528afec945d177b015d0cacc43c2f extends Mustache_Template
{
    private $lambdaHelper;

    public function renderInternal(Mustache_Context $context, $indent = '')
    {
        $this->lambdaHelper = new Mustache_LambdaHelper($this->mustache, $context);
        $buffer = '';

        $buffer .= $indent . '<div id="goto-top-link">
';
        $buffer .= $indent . '    <a class="btn btn-light" role="button" href="#">
';
        $buffer .= $indent . '        ';
        // 'pix' section
        $value = $context->find('pix');
        $buffer .= $this->section155364841d3ebe432360139dc4743c5b($context, $indent, $value);
        $buffer .= '
';
        $buffer .= $indent . '    </a>
';
        $buffer .= $indent . '</div>
';
        $buffer .= $indent . '<footer id="page-footer" class="py-3 bg-dark text-light">
';
        $buffer .= $indent . '    <div class="container">
';
        $buffer .= $indent . '        <div id="course-footer">';
        $value = $this->resolveValue($context->findDot('output.course_footer'), $context);
        $buffer .= $value;
        $buffer .= '</div>
';
        $buffer .= $indent . '
';
        // 'output.page_doc_link' section
        $value = $context->findDot('output.page_doc_link');
        $buffer .= $this->section5649438ff40dfe06c6313a0ccc9ea99f($context, $indent, $value);
        $buffer .= $indent . '
';
        $buffer .= $indent . '        ';
        $value = $this->resolveValue($context->findDot('output.login_info'), $context);
        $buffer .= $value;
        $buffer .= '
';
        $buffer .= $indent . '        <div class="tool_usertours-resettourcontainer"></div>
';
        $buffer .= $indent . '        ';
        $value = $this->resolveValue($context->findDot('output.home_link'), $context);
        $buffer .= $value;
        $buffer .= '
';
        $buffer .= $indent . '        <nav class="nav navbar-nav d-md-none" aria-label="';
        // 'str' section
        $value = $context->find('str');
        $buffer .= $this->sectionB2d5335613e625f97c91fba6125c780d($context, $indent, $value);
        $buffer .= '">
';
        // 'output.custom_menu_flat' section
        $value = $context->findDot('output.custom_menu_flat');
        $buffer .= $this->section46b3ffb39342cde5c532f0c19de9640a($context, $indent, $value);
        $buffer .= $indent . '        </nav>
';
        $buffer .= $indent . '        ';
        $value = $this->resolveValue($context->findDot('output.standard_footer_html'), $context);
        $buffer .= $value;
        $buffer .= '
';
        $buffer .= $indent . '        ';
        $value = $this->resolveValue($context->findDot('output.standard_end_of_body_html'), $context);
        $buffer .= $value;
        $buffer .= '
';
        $buffer .= $indent . '    </div>
';
        $buffer .= $indent . '</footer>
';

        return $buffer;
    }

    private function section6a4b2fe1599a285b5e49ba74521706f7(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = ' totop, theme_boost ';
            $result = call_user_func($value, $source, $this->lambdaHelper);
            if (strpos($result, '{{') === false) {
                $buffer .= $result;
            } else {
                $buffer .= $this->mustache
                    ->loadLambda((string) $result)
                    ->renderInternal($context);
            }
        } elseif (!empty($value)) {
            $values = $this->isIterable($value) ? $value : array($value);
            foreach ($values as $value) {
                $context->push($value);
                
                $buffer .= ' totop, theme_boost ';
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function section155364841d3ebe432360139dc4743c5b(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = ' i/up, core, {{#str}} totop, theme_boost {{/str}}';
            $result = call_user_func($value, $source, $this->lambdaHelper);
            if (strpos($result, '{{') === false) {
                $buffer .= $result;
            } else {
                $buffer .= $this->mustache
                    ->loadLambda((string) $result)
                    ->renderInternal($context);
            }
        } elseif (!empty($value)) {
            $values = $this->isIterable($value) ? $value : array($value);
            foreach ($values as $value) {
                $context->push($value);
                
                $buffer .= ' i/up, core, ';
                // 'str' section
                $value = $context->find('str');
                $buffer .= $this->section6a4b2fe1599a285b5e49ba74521706f7($context, $indent, $value);
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function section5649438ff40dfe06c6313a0ccc9ea99f(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = '
            <p class="helplink">{{{ output.page_doc_link }}}</p>
        ';
            $result = call_user_func($value, $source, $this->lambdaHelper);
            if (strpos($result, '{{') === false) {
                $buffer .= $result;
            } else {
                $buffer .= $this->mustache
                    ->loadLambda((string) $result)
                    ->renderInternal($context);
            }
        } elseif (!empty($value)) {
            $values = $this->isIterable($value) ? $value : array($value);
            foreach ($values as $value) {
                $context->push($value);
                
                $buffer .= $indent . '            <p class="helplink">';
                $value = $this->resolveValue($context->findDot('output.page_doc_link'), $context);
                $buffer .= $value;
                $buffer .= '</p>
';
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function sectionB2d5335613e625f97c91fba6125c780d(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = 'custommenu, admin';
            $result = call_user_func($value, $source, $this->lambdaHelper);
            if (strpos($result, '{{') === false) {
                $buffer .= $result;
            } else {
                $buffer .= $this->mustache
                    ->loadLambda((string) $result)
                    ->renderInternal($context);
            }
        } elseif (!empty($value)) {
            $values = $this->isIterable($value) ? $value : array($value);
            foreach ($values as $value) {
                $context->push($value);
                
                $buffer .= 'custommenu, admin';
                $context->pop();
            }
        }
    
        return $buffer;
    }

    private function section46b3ffb39342cde5c532f0c19de9640a(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = '
                <ul class="list-unstyled pt-3">
                    {{> theme_boost/custom_menu_footer }}
                </ul>
            ';
            $result = call_user_func($value, $source, $this->lambdaHelper);
            if (strpos($result, '{{') === false) {
                $buffer .= $result;
            } else {
                $buffer .= $this->mustache
                    ->loadLambda((string) $result)
                    ->renderInternal($context);
            }
        } elseif (!empty($value)) {
            $values = $this->isIterable($value) ? $value : array($value);
            foreach ($values as $value) {
                $context->push($value);
                
                $buffer .= $indent . '                <ul class="list-unstyled pt-3">
';
                if ($partial = $this->mustache->loadPartial('theme_boost/custom_menu_footer')) {
                    $buffer .= $partial->renderInternal($context, $indent . '                    ');
                }
                $buffer .= $indent . '                </ul>
';
                $context->pop();
            }
        }
    
        return $buffer;
    }

}
