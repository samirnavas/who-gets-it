<?php
/**
 * Form Helper Functions
 * Provides utilities for creating responsive, accessible forms
 */

/**
 * Generate a form input field with responsive styling and validation
 */
function renderFormInput($options = []) {
    $defaults = [
        'type' => 'text',
        'name' => '',
        'id' => '',
        'value' => '',
        'placeholder' => '',
        'label' => '',
        'floating_label' => false,
        'required' => false,
        'disabled' => false,
        'readonly' => false,
        'class' => '',
        'help_text' => '',
        'validation_state' => '', // 'error', 'success', 'warning'
        'validation_message' => '',
        'attributes' => []
    ];
    
    $options = array_merge($defaults, $options);
    
    // Generate unique ID if not provided
    if (empty($options['id'])) {
        $options['id'] = 'field_' . uniqid();
    }
    
    // Build CSS classes
    $fieldClasses = ['form-field'];
    if ($options['floating_label']) {
        $fieldClasses[] = 'form-field-floating';
    }
    if (!empty($options['validation_state'])) {
        $fieldClasses[] = 'has-' . $options['validation_state'];
    }
    
    $inputClasses = ['form-input'];
    if (!empty($options['class'])) {
        $inputClasses[] = $options['class'];
    }
    
    // Build attributes
    $attributes = [
        'type' => $options['type'],
        'name' => $options['name'],
        'id' => $options['id'],
        'class' => implode(' ', $inputClasses),
        'value' => htmlspecialchars($options['value']),
    ];
    
    if (!empty($options['placeholder'])) {
        $attributes['placeholder'] = $options['placeholder'];
    }
    
    if ($options['required']) {
        $attributes['required'] = 'required';
        $attributes['aria-required'] = 'true';
    }
    
    if ($options['disabled']) {
        $attributes['disabled'] = 'disabled';
    }
    
    if ($options['readonly']) {
        $attributes['readonly'] = 'readonly';
    }
    
    // Add custom attributes
    $attributes = array_merge($attributes, $options['attributes']);
    
    // Build attribute string
    $attrString = '';
    foreach ($attributes as $key => $value) {
        if ($value !== null && $value !== false) {
            $attrString .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
    }
    
    // Start output
    $output = '<div class="' . implode(' ', $fieldClasses) . '">';
    
    // Traditional label (if not floating)
    if (!$options['floating_label'] && !empty($options['label'])) {
        $labelClass = 'form-label';
        if ($options['required']) {
            $labelClass .= ' form-label-required';
        }
        $output .= '<label for="' . $options['id'] . '" class="' . $labelClass . '">';
        $output .= htmlspecialchars($options['label']);
        $output .= '</label>';
    }
    
    // Input field
    $output .= '<input' . $attrString . '>';
    
    // Floating label (if enabled)
    if ($options['floating_label'] && !empty($options['label'])) {
        $output .= '<label for="' . $options['id'] . '" class="form-label-floating">';
        $output .= htmlspecialchars($options['label']);
        $output .= '</label>';
    }
    
    // Help text
    if (!empty($options['help_text'])) {
        $output .= '<div class="form-help-text">' . htmlspecialchars($options['help_text']) . '</div>';
    }
    
    // Validation message
    if (!empty($options['validation_message'])) {
        $messageClass = 'form-validation-message';
        if (!empty($options['validation_state'])) {
            $messageClass .= ' form-validation-' . $options['validation_state'];
        }
        $output .= '<div class="' . $messageClass . '">' . htmlspecialchars($options['validation_message']) . '</div>';
    }
    
    $output .= '</div>';
    
    return $output;
}

/**
 * Generate a textarea field with responsive styling
 */
function renderFormTextarea($options = []) {
    $defaults = [
        'name' => '',
        'id' => '',
        'value' => '',
        'placeholder' => '',
        'label' => '',
        'rows' => 4,
        'required' => false,
        'disabled' => false,
        'readonly' => false,
        'class' => '',
        'help_text' => '',
        'validation_state' => '',
        'validation_message' => '',
        'attributes' => []
    ];
    
    $options = array_merge($defaults, $options);
    
    // Generate unique ID if not provided
    if (empty($options['id'])) {
        $options['id'] = 'field_' . uniqid();
    }
    
    // Build CSS classes
    $fieldClasses = ['form-field'];
    if (!empty($options['validation_state'])) {
        $fieldClasses[] = 'has-' . $options['validation_state'];
    }
    
    $textareaClasses = ['form-textarea'];
    if (!empty($options['class'])) {
        $textareaClasses[] = $options['class'];
    }
    
    // Build attributes
    $attributes = [
        'name' => $options['name'],
        'id' => $options['id'],
        'class' => implode(' ', $textareaClasses),
        'rows' => $options['rows'],
    ];
    
    if (!empty($options['placeholder'])) {
        $attributes['placeholder'] = $options['placeholder'];
    }
    
    if ($options['required']) {
        $attributes['required'] = 'required';
        $attributes['aria-required'] = 'true';
    }
    
    if ($options['disabled']) {
        $attributes['disabled'] = 'disabled';
    }
    
    if ($options['readonly']) {
        $attributes['readonly'] = 'readonly';
    }
    
    // Add custom attributes
    $attributes = array_merge($attributes, $options['attributes']);
    
    // Build attribute string
    $attrString = '';
    foreach ($attributes as $key => $value) {
        if ($value !== null && $value !== false) {
            $attrString .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
    }
    
    // Start output
    $output = '<div class="' . implode(' ', $fieldClasses) . '">';
    
    // Label
    if (!empty($options['label'])) {
        $labelClass = 'form-label';
        if ($options['required']) {
            $labelClass .= ' form-label-required';
        }
        $output .= '<label for="' . $options['id'] . '" class="' . $labelClass . '">';
        $output .= htmlspecialchars($options['label']);
        $output .= '</label>';
    }
    
    // Textarea field
    $output .= '<textarea' . $attrString . '>';
    $output .= htmlspecialchars($options['value']);
    $output .= '</textarea>';
    
    // Help text
    if (!empty($options['help_text'])) {
        $output .= '<div class="form-help-text">' . htmlspecialchars($options['help_text']) . '</div>';
    }
    
    // Validation message
    if (!empty($options['validation_message'])) {
        $messageClass = 'form-validation-message';
        if (!empty($options['validation_state'])) {
            $messageClass .= ' form-validation-' . $options['validation_state'];
        }
        $output .= '<div class="' . $messageClass . '">' . htmlspecialchars($options['validation_message']) . '</div>';
    }
    
    $output .= '</div>';
    
    return $output;
}

/**
 * Generate a select field with responsive styling
 */
function renderFormSelect($options = []) {
    $defaults = [
        'name' => '',
        'id' => '',
        'value' => '',
        'label' => '',
        'options' => [],
        'required' => false,
        'disabled' => false,
        'class' => '',
        'help_text' => '',
        'validation_state' => '',
        'validation_message' => '',
        'attributes' => []
    ];
    
    $options = array_merge($defaults, $options);
    
    // Generate unique ID if not provided
    if (empty($options['id'])) {
        $options['id'] = 'field_' . uniqid();
    }
    
    // Build CSS classes
    $fieldClasses = ['form-field'];
    if (!empty($options['validation_state'])) {
        $fieldClasses[] = 'has-' . $options['validation_state'];
    }
    
    $selectClasses = ['form-select'];
    if (!empty($options['class'])) {
        $selectClasses[] = $options['class'];
    }
    
    // Build attributes
    $attributes = [
        'name' => $options['name'],
        'id' => $options['id'],
        'class' => implode(' ', $selectClasses),
    ];
    
    if ($options['required']) {
        $attributes['required'] = 'required';
        $attributes['aria-required'] = 'true';
    }
    
    if ($options['disabled']) {
        $attributes['disabled'] = 'disabled';
    }
    
    // Add custom attributes
    $attributes = array_merge($attributes, $options['attributes']);
    
    // Build attribute string
    $attrString = '';
    foreach ($attributes as $key => $value) {
        if ($value !== null && $value !== false) {
            $attrString .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
    }
    
    // Start output
    $output = '<div class="' . implode(' ', $fieldClasses) . '">';
    
    // Label
    if (!empty($options['label'])) {
        $labelClass = 'form-label';
        if ($options['required']) {
            $labelClass .= ' form-label-required';
        }
        $output .= '<label for="' . $options['id'] . '" class="' . $labelClass . '">';
        $output .= htmlspecialchars($options['label']);
        $output .= '</label>';
    }
    
    // Select field
    $output .= '<select' . $attrString . '>';
    
    foreach ($options['options'] as $value => $text) {
        $selected = ($value == $options['value']) ? ' selected' : '';
        $output .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>';
        $output .= htmlspecialchars($text);
        $output .= '</option>';
    }
    
    $output .= '</select>';
    
    // Help text
    if (!empty($options['help_text'])) {
        $output .= '<div class="form-help-text">' . htmlspecialchars($options['help_text']) . '</div>';
    }
    
    // Validation message
    if (!empty($options['validation_message'])) {
        $messageClass = 'form-validation-message';
        if (!empty($options['validation_state'])) {
            $messageClass .= ' form-validation-' . $options['validation_state'];
        }
        $output .= '<div class="' . $messageClass . '">' . htmlspecialchars($options['validation_message']) . '</div>';
    }
    
    $output .= '</div>';
    
    return $output;
}

/**
 * Generate a checkbox field with responsive styling
 */
function renderFormCheckbox($options = []) {
    $defaults = [
        'name' => '',
        'id' => '',
        'value' => '1',
        'checked' => false,
        'label' => '',
        'required' => false,
        'disabled' => false,
        'class' => '',
        'help_text' => '',
        'validation_state' => '',
        'validation_message' => '',
        'attributes' => []
    ];
    
    $options = array_merge($defaults, $options);
    
    // Generate unique ID if not provided
    if (empty($options['id'])) {
        $options['id'] = 'field_' . uniqid();
    }
    
    // Build CSS classes
    $fieldClasses = ['form-field'];
    if (!empty($options['validation_state'])) {
        $fieldClasses[] = 'has-' . $options['validation_state'];
    }
    
    $checkboxClasses = ['form-checkbox'];
    if (!empty($options['class'])) {
        $checkboxClasses[] = $options['class'];
    }
    
    // Build attributes
    $attributes = [
        'type' => 'checkbox',
        'name' => $options['name'],
        'id' => $options['id'],
        'class' => implode(' ', $checkboxClasses),
        'value' => htmlspecialchars($options['value']),
    ];
    
    if ($options['checked']) {
        $attributes['checked'] = 'checked';
    }
    
    if ($options['required']) {
        $attributes['required'] = 'required';
        $attributes['aria-required'] = 'true';
    }
    
    if ($options['disabled']) {
        $attributes['disabled'] = 'disabled';
    }
    
    // Add custom attributes
    $attributes = array_merge($attributes, $options['attributes']);
    
    // Build attribute string
    $attrString = '';
    foreach ($attributes as $key => $value) {
        if ($value !== null && $value !== false) {
            $attrString .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
    }
    
    // Start output
    $output = '<div class="' . implode(' ', $fieldClasses) . '">';
    $output .= '<div class="form-checkbox-container">';
    
    // Checkbox input
    $output .= '<input' . $attrString . '>';
    
    // Label
    if (!empty($options['label'])) {
        $output .= '<label for="' . $options['id'] . '" class="form-checkbox-label">';
        $output .= htmlspecialchars($options['label']);
        $output .= '</label>';
    }
    
    $output .= '</div>';
    
    // Help text
    if (!empty($options['help_text'])) {
        $output .= '<div class="form-help-text">' . htmlspecialchars($options['help_text']) . '</div>';
    }
    
    // Validation message
    if (!empty($options['validation_message'])) {
        $messageClass = 'form-validation-message';
        if (!empty($options['validation_state'])) {
            $messageClass .= ' form-validation-' . $options['validation_state'];
        }
        $output .= '<div class="' . $messageClass . '">' . htmlspecialchars($options['validation_message']) . '</div>';
    }
    
    $output .= '</div>';
    
    return $output;
}

/**
 * Generate form buttons with responsive styling
 */
function renderFormButtons($buttons = []) {
    if (empty($buttons)) {
        return '';
    }
    
    $output = '<div class="form-button-group">';
    
    foreach ($buttons as $button) {
        $defaults = [
            'type' => 'button',
            'text' => 'Button',
            'style' => 'primary', // 'primary' or 'secondary'
            'class' => '',
            'disabled' => false,
            'attributes' => []
        ];
        
        $button = array_merge($defaults, $button);
        
        $buttonClasses = ['form-button-' . $button['style']];
        if (!empty($button['class'])) {
            $buttonClasses[] = $button['class'];
        }
        
        $attributes = [
            'type' => $button['type'],
            'class' => implode(' ', $buttonClasses),
        ];
        
        if ($button['disabled']) {
            $attributes['disabled'] = 'disabled';
        }
        
        // Add custom attributes
        $attributes = array_merge($attributes, $button['attributes']);
        
        // Build attribute string
        $attrString = '';
        foreach ($attributes as $key => $value) {
            if ($value !== null && $value !== false) {
                $attrString .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
            }
        }
        
        $output .= '<button' . $attrString . '>';
        $output .= htmlspecialchars($button['text']);
        $output .= '</button>';
    }
    
    $output .= '</div>';
    
    return $output;
}
