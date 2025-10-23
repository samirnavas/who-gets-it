<?php
/**
 * Responsive Layout Wrapper Component
 * Provides consistent responsive containers and layout patterns
 */

/**
 * Generate responsive container classes
 * @param string $size Container size variant (sm, md, lg, xl, full)
 * @param string $padding Padding variant (none, sm, md, lg)
 * @return string CSS classes
 */
function getContainerClasses($size = 'lg', $padding = 'md') {
    $baseClasses = 'container-responsive';
    
    $sizeClasses = [
        'sm' => 'max-w-2xl',
        'md' => 'max-w-4xl', 
        'lg' => 'max-w-7xl',
        'xl' => 'max-w-screen-2xl',
        'full' => 'max-w-none'
    ];
    
    $paddingClasses = [
        'none' => '',
        'sm' => 'py-4',
        'md' => 'py-8 md:py-12',
        'lg' => 'py-12 md:py-16 lg:py-20'
    ];
    
    $classes = $baseClasses;
    
    if (isset($sizeClasses[$size])) {
        $classes .= ' ' . $sizeClasses[$size];
    }
    
    if (isset($paddingClasses[$padding])) {
        $classes .= ' ' . $paddingClasses[$padding];
    }
    
    return $classes;
}

/**
 * Generate responsive grid classes
 * @param array $columns Columns per breakpoint ['sm' => 1, 'md' => 2, 'lg' => 3]
 * @param string $gap Gap size (xs, sm, md, lg, xl)
 * @return string CSS classes
 */
function getGridClasses($columns = ['sm' => 1, 'md' => 2, 'lg' => 3], $gap = 'md') {
    $baseClasses = 'grid-responsive';
    
    $gapClasses = [
        'xs' => 'spacing-xs',
        'sm' => 'spacing-sm', 
        'md' => 'spacing-md',
        'lg' => 'spacing-lg',
        'xl' => 'spacing-xl'
    ];
    
    $classes = $baseClasses;
    
    foreach ($columns as $breakpoint => $colCount) {
        $classes .= " cols-{$breakpoint}-{$colCount}";
    }
    
    if (isset($gapClasses[$gap])) {
        $classes .= ' ' . $gapClasses[$gap];
    }
    
    return $classes;
}

/**
 * Generate responsive card classes
 * @param string $variant Card style variant (default, elevated, bordered)
 * @param bool $hover Enable hover effects
 * @return string CSS classes
 */
function getCardClasses($variant = 'default', $hover = true) {
    $baseClasses = 'card-responsive';
    
    $variantClasses = [
        'default' => '',
        'elevated' => 'shadow-lg',
        'bordered' => 'border border-gray-200'
    ];
    
    $classes = $baseClasses;
    
    if (isset($variantClasses[$variant])) {
        $classes .= ' ' . $variantClasses[$variant];
    }
    
    if ($hover) {
        $classes .= ' animate-scale-hover';
    }
    
    return $classes;
}

/**
 * Generate responsive button classes
 * @param string $size Button size (sm, md, lg)
 * @param string $variant Button variant (primary, secondary, outline)
 * @return string CSS classes
 */
function getButtonClasses($size = 'md', $variant = 'primary') {
    $baseClasses = 'btn-responsive';
    
    $sizeClasses = [
        'sm' => 'px-3 py-2 text-sm',
        'md' => 'px-4 py-2 text-base',
        'lg' => 'px-6 py-3 text-lg'
    ];
    
    $variantClasses = [
        'primary' => 'bg-blue-600 text-white hover:bg-blue-700',
        'secondary' => 'bg-gray-600 text-white hover:bg-gray-700',
        'outline' => 'border-2 border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white'
    ];
    
    $classes = $baseClasses;
    
    if (isset($sizeClasses[$size])) {
        $classes .= ' ' . $sizeClasses[$size];
    }
    
    if (isset($variantClasses[$variant])) {
        $classes .= ' ' . $variantClasses[$variant];
    }
    
    return $classes;
}

/**
 * Generate responsive form input classes
 * @param string $size Input size (sm, md, lg)
 * @param bool $error Error state
 * @return string CSS classes
 */
function getInputClasses($size = 'md', $error = false) {
    $baseClasses = 'input-responsive';
    
    $sizeClasses = [
        'sm' => 'text-sm py-2 px-3',
        'md' => 'text-base py-3 px-4', 
        'lg' => 'text-lg py-4 px-5'
    ];
    
    $classes = $baseClasses;
    
    if (isset($sizeClasses[$size])) {
        $classes .= ' ' . $sizeClasses[$size];
    }
    
    if ($error) {
        $classes .= ' border-red-500 focus:border-red-500';
    }
    
    return $classes;
}

/**
 * Start a responsive section
 * @param string $containerSize Container size
 * @param string $padding Padding variant
 * @param array $attributes Additional HTML attributes
 */
function startResponsiveSection($containerSize = 'lg', $padding = 'md', $attributes = []) {
    $classes = getContainerClasses($containerSize, $padding);
    
    $attrString = '';
    foreach ($attributes as $key => $value) {
        $attrString .= " {$key}=\"" . htmlspecialchars($value) . "\"";
    }
    
    echo "<section class=\"{$classes}\"{$attrString}>";
}

/**
 * End a responsive section
 */
function endResponsiveSection() {
    echo "</section>";
}

/**
 * Start a responsive grid
 * @param array $columns Columns configuration
 * @param string $gap Gap size
 * @param array $attributes Additional HTML attributes
 */
function startResponsiveGrid($columns = ['sm' => 1, 'md' => 2, 'lg' => 3], $gap = 'md', $attributes = []) {
    $classes = getGridClasses($columns, $gap);
    
    $attrString = '';
    foreach ($attributes as $key => $value) {
        $attrString .= " {$key}=\"" . htmlspecialchars($value) . "\"";
    }
    
    echo "<div class=\"{$classes}\"{$attrString}>";
}

/**
 * End a responsive grid
 */
function endResponsiveGrid() {
    echo "</div>";
}
?>