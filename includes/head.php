<?php
/**
 * Shared <head> content: Tailwind CSS (CDN), brand colors, custom styles.
 * Expects $documentTitle to be set by the including layout.
 */
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($documentTitle) ?> | <?= e(APP_NAME) ?></title>
<script>
    // Apply the saved sidebar state before the page paints, to avoid a flicker.
    try {
        if (localStorage.getItem('el_sidebar') === 'collapsed') {
            document.documentElement.setAttribute('data-sidebar', 'collapsed');
        }
    } catch (e) {}
</script>
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary:   '#E8A0B5',
                    secondary: '#B85C7A',
                    surface:   '#FFF7FA',
                    ink:       '#29232A',
                    muted:     '#77717A',
                    line:      '#F0E3E9'
                },
                fontFamily: {
                    sans: ['ui-sans-serif', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'],
                    serif: ['Georgia', 'Cambria', 'Times New Roman', 'serif']
                }
            }
        }
    };
</script>
<link rel="stylesheet" href="<?= e(url('assets/css/custom.css')) ?>">
