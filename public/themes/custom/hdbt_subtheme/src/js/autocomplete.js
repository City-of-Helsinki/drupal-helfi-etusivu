/**
 * @file
 * Initiates autocomplete with support for remote options.
 */

'use strict';

((Drupal, once) => {
  /**
   * Initialize autocomplete.
   *
   * @param {HTMLSelectElement} element Select element.
   */
  const init = element => {
    if (!A11yAutocomplete) {
      throw new Error('A11yAutocomplete object not found. Make sure the library is loaded.');
    }

    if (!drupalSettings.helsinki_near_you_form) {
      throw new Error('Helsinki near you form object not found. Configuration cannot be loaded for autocomplete.');
    }

    const {
      noResultsAssistiveHint,
      someResultsAssistiveHint,
      oneResultAssistiveHint,
      highlightedAssistiveHint,
      inputAssistiveHint,
      minCharAssistiveHint
    } = drupalSettings.helsinki_near_you_form;

    A11yAutocomplete(element, {
      classes: {
        wrapper: 'helfi-etusivu-autocomplete'
      },
      source: async (searchTerm, results) => {
        if (searchTerm.length < 3) {
          return results([]);
        }

        try {
          const response = await fetch(`/helsinki-near-you/results/autocomplete?q=${searchTerm}`, {});
          const data = await response.json();
          results(data);
        }
        catch (e) {
          console.error(e);
          results([]);
        }
      },
      minChars: 3,
      minCharAssistiveHint,
      inputAssistiveHint,
      noResultsAssistiveHint,
      someResultsAssistiveHint,
      oneResultAssistiveHint,
      highlightedAssistiveHint,
    });
  };

  Drupal.behaviors.helfi_etusivu_autocomplete = {
    attach: function (context, settings) {
        once(
          'a11y_autocomplete_element',
          '[data-helfi-etusivu-autocomplete]',
          context,
        ).forEach(init);

        console.log(Drupal.t('No address suggestions were found', {}, {context: 'Front page autocomplete'}));
    },
  };
})(Drupal, once);
