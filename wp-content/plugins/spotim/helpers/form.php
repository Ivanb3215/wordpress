<?php

abstract class FormHelper extends UtilsHelper {
    public function addField($field, $echo = true) {
        $template;

        switch ($field['type']) {
            case 'select':
                $template =  '<select name="'. $field['group'] .'['. $field['id'] .']">';
                    foreach ($field['options'] as $key => $value) {
                        $selected = $field['value'] === $value ? 'selected="selected"' : '';
                        $template .= '<option value="'. $value .'" '. $selected .'>'. $key .'</option>';
                    }
                $template .=  '</select>';
            break;
            case 'checkbox':
                $field['value']  = !empty($field['value']) ? '1' : '0';
                $template = '<input name="'. $field['group'] .'['. $field['id'] .']" type="'. $field['type'] .'" value="1" '. checked($field['value'], 1, 0) .'  />';
            break;
            case 'text':
            default:
                $template = '<input name="'. $field['group'] .'['. $field['id'] .']" type="'. $field['type'] .'" value="'. $field['value'] .'" />';
            break;
        }

        if (isset($field['description'])) {
            $template .= '<p class="description">'. $field['description'] .'</p>';
        }

        return $this->echoOutput($template, $echo);
    }

    public function addView($view) {
        include_once($view);
    }

    public function addTemplate($template, $data = array(), $echo = true) {
        $template = file_get_contents($template, true);

        if (!empty($data)) {
            $values = array();
            preg_match_all('/\{+(\w+)\}+/', $template, $matches);

            foreach ($matches[1] as $match) {
                if (!empty($data[$match]) || $data[$match] === '0') {
                    array_push($values, $data[$match]);
                }
            }

            $template = str_replace($matches[0], $values, $template);
        }

        return $this->echoOutput(
            $template, $echo
        );
    }
}