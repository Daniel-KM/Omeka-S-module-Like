(function ($) {
    $(document).ready(function () {
        $('#🖒-reset').closest('.field')
            .wrapAll('<fieldset id="🖒" class="field-container">');
        $('#🖒')
            .prepend('<legend>' + Omeka.jsTranslate('👍') + '</legend>');
    });
})(jQuery);
