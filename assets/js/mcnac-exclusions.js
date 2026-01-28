/**
 * MCNAC Exclusions Admin Script
 *
 * @package MCNAC_N8N_Chat
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        // Get translated string from localized data.
        var helpText = (typeof mcnacExclusionsL10n !== 'undefined' && mcnacExclusionsL10n.multiselectHelp)
            ? mcnacExclusionsL10n.multiselectHelp
            : 'Hold Ctrl (Cmd on Mac) + click to select or deselect multiple items.';

        // Add helper text for multiselect fields.
        $('.mcnac-exclusions-wrap select[multiple]').each(function () {
            var $select = $(this);
            if (!$select.next('.mcnac-multiselect-help').length) {
                $select.after('<p class="mcnac-multiselect-help">' + helpText + '</p>');
            }
        });
    });

})(jQuery);
