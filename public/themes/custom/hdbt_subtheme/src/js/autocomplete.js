const LOCATION_OPTION = Drupal.t('Use current Location', {}, { context: 'Helsinki near you' });
const API_URL = 'https://api.hel.fi/servicemap/v2/address/';

const { currentLanguage } = drupalSettings.path;

const locationOptionLabel = `
  <div>
    <span class="hel-icon hel-icon--locate" role="img" aria-hidden="true"></span>
    ${LOCATION_OPTION}
  </div>
`;

// Dont add 'Use current location' option if location not available
const defaultOptions = 'geolocation' in navigator ? [{
  label: locationOptionLabel,
  value: LOCATION_OPTION,
  index: 0,
  item: {
    label: LOCATION_OPTION,
    value: LOCATION_OPTION,
  }
}] : [];

/**
 * Get the most appropriate translation for address.
 * 
 * @param {object} fullName - Translations object
 * @return {string} - the result
 */
const getTranslation = (fullName) => {
  if (fullName[currentLanguage]) {
    return fullName[currentLanguage];
  }

  if (fullName.fi) {
    return fullName.fi;
  }

  return Object.values(fullName)[0];
};

/**
 * Renders automatic location error.
 */
const displayLocationError = () => {
  const errorElement = document.createElement('div');
  errorElement.innerHTML = Drupal.t('Couldn\'t retrieve location. Please type desired address manually', {}, {
    context: 'Helsinki near you'
  });
  const errorArea = document.querySelector('.helfi-etusivu-near-you-form__errors');
  errorArea.innerHTML = '';
  errorArea.appendChild(errorElement);
};

/**
 * Reflect loading and filling location in UI.
 * 
 * @param {object} autocompleteInstance - instance to affect.
 * @param {boolean} state - true to set loading.
 */
const setLoading = (autocompleteInstance, state) => {
  autocompleteInstance.disabled = state;

  if (state) {
    autocompleteInstance.addClasses(
      autocompleteInstance.input,
      autocompleteInstance.options.classes.inputLoading
    );

    return;
  }

  autocompleteInstance.removeClasses(
    autocompleteInstance.input,
    autocompleteInstance.options.classes.inputLoading
  );
  autocompleteInstance.close();
};

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
    const autocomplete = A11yAutocomplete(element, {
      classes: {
        inputLoading: 'loading',
        wrapper: 'helfi-etusivu-autocomplete',
      },
      highlightedAssistiveHint,
      inputAssistiveHint,
      minCharAssistiveHint,
      minChars: 0,
      noResultsAssistiveHint,
      oneResultAssistiveHint,
      someResultsAssistiveHint,
      source: async(searchTerm, results) => {
        if (searchTerm.length < 3) {
          return results(defaultOptions);
        }

        try {
          const response = await fetch(`${autocompleteRoute}?q=${searchTerm}`);
          const data = await response.json();
          results(defaultOptions.concat(data));
        }
        catch (e) {
          // eslint-disable-next-line no-console
          console.error(e);
          results(defaultOptions);
        }
      },
    });
    const autocompleteInstance = autocomplete._internal_object;

    element.addEventListener('autocomplete-select', (event) => {
      if (event.detail.selected.value !== LOCATION_OPTION) {
        return;
      }

      event.preventDefault();
      setLoading(autocompleteInstance, true);
      navigator.geolocation.getCurrentPosition(async(position) => {
        const { coords: { latitude, longitude } } = position;
        
        const params = new URLSearchParams({
          lat: latitude,
          lon: longitude,
        });
        const reqUrl = new URL(API_URL);
        reqUrl.search = params.toString();

        const response = await fetch(reqUrl.toString());
        const json = await response.json();

        event.target.value = getTranslation(json.results[0].full_name);
      });
      setLoading(autocompleteInstance, false);
    });

    element.addEventListener('focus', () => {
      displayLocationError();
      if (autocompleteInstance.input.value === '' && defaultOptions.length) {
        autocompleteInstance.displayResults(defaultOptions);
      }
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
