<?php
/** Language switcher: include after i18n.php. Uses $LANG, t(), lang_url(). */
$target_lang = ($LANG === 'en' ? 'ar' : 'en');
$target_label = ($LANG === 'en' ? 'العربية' : 'English');
?>
<div class="lang-switcher-container">
    <a href="<?php echo htmlspecialchars(lang_url($target_lang)); ?>" class="lang-switch-btn">
        <span class="lang-icon">🌐</span>
        <span class="lang-label"><?php echo $target_label; ?></span>
    </a>
</div>
