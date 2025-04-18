((Drupal, once) => {
  /**
   * Initialize autocomplete.
   *
   * @param {HTMLSelectElement} element Select element.
   */
  const init = element => {
    // eslint-disable-next-line no-undef
    if (!A11yAutocomplete) {
      throw new Error('A11yAutocomplete object not found. Make sure the library is loaded.');
    }

    if (!drupalSettings.helsinki_near_you_form) {
      throw new Error('Helsinki near you form object not found. Configuration cannot be loaded for autocomplete.');
    }

    const {
      autocompleteRoute,
      noResultsAssistiveHint,
      someResultsAssistiveHint,
      oneResultAssistiveHint,
      highlightedAssistiveHint,
      inputAssistiveHint,
      minCharAssistiveHint
    } = drupalSettings.helsinki_near_you_form;

    // eslint-disable-next-line no-undef
    A11yAutocomplete(element, {
      classes: {
        wrapper: 'helfi-etusivu-autocomplete'
      },
      source: async(searchTerm, results) => {
        if (searchTerm.length < 3) {
          return results([]);
        }

        try {
          const response = await fetch(`${autocompleteRoute}?q=${searchTerm}`, {});
          const data = await response.json();
          results(data);
        }
        catch (e) {
          // eslint-disable-next-line no-console
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
    attach(context) {
      once(
        'a11y_autocomplete_element',
        '[data-helfi-etusivu-autocomplete]',
        context,
      ).forEach(init);
    },
  };
})(Drupal, once);
