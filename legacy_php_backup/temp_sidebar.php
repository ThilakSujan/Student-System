<?php
$current_content = file_get_contents('includes/sidebar.php');

// We will use regular expressions to find nav-sections and wrap the following links in an accordion
$new_content = preg_replace_callback('/<div class="nav-section">(.*?)<\/div>(.*?(?=<div class="nav-section">|<\?php elseif|<\?php endif|<\/div> <!-- end nav items -->|<\/nav>))/is', function($matches) {
    $section_name = trim($matches[1]);
    $links_html = $matches[2];
    $id = 'collapse_' . preg_replace('/[^a-zA-Z0-9]/', '', $section_name);
    
    // Check if any link inside is active
    $is_active = strpos($links_html, 'sidebarActive(') !== false; // Approximation, we will fix active logic dynamically in PHP
    
    // We will generate PHP code that checks if the section should be open
    $php_active_check = "<?php \$is_open = strpos(ob_get_contents() ?? '', 'active') !== false; ?>"; 
    // This is hard to do with regex.
    return $matches[0];
}, $current_content);

// Actually, regex replacement for this is too complex because of PHP tags.
// Let's just output a hardcoded new sidebar.php based on the known structure.
?>
