<?php

class YSRTech_Followup_Block_Adminhtml_Autoresponders_Edit_Tab_Chain extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {
        $current = Mage::registry('current_autoresponder');

        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('page_');

        $fieldset = $form->addFieldset('chain_fieldset', array(
            'legend' => $this->__('Multi-Step Email Chain'),
            'class'  => 'fieldset-wide'
        ));

        $fieldset->addField('chain_note', 'note', array(
            'text' => $this->__('Configure a series of emails to be sent at different intervals. Leave empty to send only a single email (using the template from the Content tab). Each step will add to the base delay configured in Settings tab.')
        ));

        // Get email templates for dropdown
        $templates = Mage::getResourceModel('core/email_template_collection')
            ->load()
            ->toOptionArray();
        array_unshift($templates, array('value' => '', 'label' => $this->__('-- Use Default Template --')));

        // Prepare chain steps data
        $chainSteps = array();
        if ($current && $current->getChainSteps()) {
            $chainSteps = @unserialize($current->getChainSteps());
            if (!is_array($chainSteps)) {
                $chainSteps = array();
            }
        }

        // Add dynamic rows for chain steps
        $fieldset->addField('chain_steps_container', 'note', array(
            'text' => $this->_getChainStepsHtml($chainSteps, $templates)
        ));

        $this->setForm($form);

        if ($current) {
            $form->addValues($current->getData());
        }

        return parent::_prepareForm();
    }

    protected function _getChainStepsHtml($chainSteps, $templates)
    {
        $html = '<div id="chain-steps-container" class="grid">';

        if (empty($chainSteps)) {
            $chainSteps = array(
                array('delay_days' => 1, 'delay_hours' => 0, 'template_id' => '')
            );
        }

        foreach ($chainSteps as $index => $step) {
            $html .= $this->_getStepHtml($index, $step, $templates);
        }

        $html .= '</div>';
        $html .= '<button type="button" id="chain-add-step" class="scalable add" style="margin-top:10px;"><span><span><span>' . $this->__('Add Another Step') . '</span></span></span></button>';
        $html .= $this->_getJavaScript($templates);

        return $html;
    }

    protected function _getStepHtml($index, $step, $templates)
    {
        $stepNum = $index + 1;
        $html = '<div class="chain-step entry-edit" data-step="' . $index . '" style="margin-bottom:15px;">';
        $html .= '<div class="entry-edit-head"><h4 class="icon-head head-customer-group">' . $this->__('Step %s', $stepNum) . '</h4></div>';
        $html .= '<fieldset>';
        $html .= '<table cellspacing="0" class="form-list"><tbody>';
        
        // Delay Days
        $html .= '<tr>';
        $html .= '<td class="label"><label>' . $this->__('Additional Delay (Days)') . '</label></td>';
        $html .= '<td class="value"><input type="number" name="chain_steps[' . $index . '][delay_days]" class="input-text" value="' . (isset($step['delay_days']) ? $step['delay_days'] : 0) . '" min="0" max="365" style="width:80px;" /></td>';
        $html .= '<td class="scope-label"></td>';
        
        // Delay Hours
        $html .= '<td class="label"><label>' . $this->__('Additional Delay (Hours)') . '</label></td>';
        $html .= '<td class="value"><input type="number" name="chain_steps[' . $index . '][delay_hours]" class="input-text" value="' . (isset($step['delay_hours']) ? $step['delay_hours'] : 0) . '" min="0" max="23" style="width:80px;" /></td>';
        $html .= '</tr>';
        
        // Template
        $html .= '<tr>';
        $html .= '<td class="label"><label>' . $this->__('Email Template') . '</label></td>';
        $html .= '<td class="value" colspan="4"><select name="chain_steps[' . $index . '][template_id]" class="select" style="width:350px;">';
        foreach ($templates as $template) {
            $selected = (isset($step['template_id']) && $step['template_id'] == $template['value']) ? ' selected="selected"' : '';
            $html .= '<option value="' . $template['value'] . '"' . $selected . '>' . htmlspecialchars($template['label']) . '</option>';
        }
        $html .= '</select></td>';
        $html .= '</tr>';
        
        $html .= '</tbody></table>';
        $html .= '</fieldset>';
        
        // Remove button
        if ($stepNum > 1) {
            $html .= '<div style="padding:10px;text-align:right;">';
            $html .= '<button type="button" class="scalable delete chain-remove-step" onclick="removeChainStep(this)"><span><span><span>' . $this->__('Remove Step') . '</span></span></span></button>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    protected function _getJavaScript($templates)
    {
        $templatesJson = Mage::helper('core')->jsonEncode($templates);
        
        return <<<HTML
<script type="text/javascript">
var chainStepIndex = document.querySelectorAll('.chain-step').length;
var templateOptions = {$templatesJson};

document.getElementById('chain-add-step').addEventListener('click', function() {
    addChainStep();
});

function addChainStep() {
    var container = document.getElementById('chain-steps-container');
    var stepNum = chainStepIndex + 1;
    
    var stepHtml = '<div class="chain-step entry-edit" data-step="' + chainStepIndex + '" style="margin-bottom:15px;">';
    stepHtml += '<div class="entry-edit-head"><h4 class="icon-head head-customer-group">Step ' + stepNum + '</h4></div>';
    stepHtml += '<fieldset>';
    stepHtml += '<table cellspacing="0" class="form-list"><tbody>';
    
    stepHtml += '<tr>';
    stepHtml += '<td class="label"><label>{$this->__('Additional Delay (Days)')}</label></td>';
    stepHtml += '<td class="value"><input type="number" name="chain_steps[' + chainStepIndex + '][delay_days]" class="input-text" value="0" min="0" max="365" style="width:80px;" /></td>';
    stepHtml += '<td class="scope-label"></td>';
    stepHtml += '<td class="label"><label>{$this->__('Additional Delay (Hours)')}</label></td>';
    stepHtml += '<td class="value"><input type="number" name="chain_steps[' + chainStepIndex + '][delay_hours]" class="input-text" value="0" min="0" max="23" style="width:80px;" /></td>';
    stepHtml += '</tr>';
    
    stepHtml += '<tr>';
    stepHtml += '<td class="label"><label>{$this->__('Email Template')}</label></td>';
    stepHtml += '<td class="value" colspan="4"><select name="chain_steps[' + chainStepIndex + '][template_id]" class="select" style="width:350px;">';
    templateOptions.forEach(function(template) {
        stepHtml += '<option value="' + template.value + '">' + template.label + '</option>';
    });
    stepHtml += '</select></td>';
    stepHtml += '</tr>';
    
    stepHtml += '</tbody></table>';
    stepHtml += '</fieldset>';
    stepHtml += '<div style="padding:10px;text-align:right;">';
    stepHtml += '<button type="button" class="scalable delete chain-remove-step" onclick="removeChainStep(this)"><span><span><span>{$this->__('Remove Step')}</span></span></span></button>';
    stepHtml += '</div>';
    stepHtml += '</div>';
    
    container.insertAdjacentHTML('beforeend', stepHtml);
    chainStepIndex++;
}

function removeChainStep(button) {
    var step = button.closest('.chain-step');
    step.remove();
    renumberSteps();
}

function renumberSteps() {
    var steps = document.querySelectorAll('.chain-step');
    steps.forEach(function(step, index) {
        var header = step.querySelector('.entry-edit-head h4');
        header.textContent = 'Step ' + (index + 1);
    });
}
</script>
HTML;
    }
}
