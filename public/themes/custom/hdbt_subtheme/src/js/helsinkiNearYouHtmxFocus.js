((Drupal) => {
  Drupal.behaviors.helsinkiNearYouHtmxFocus = {
    attach(context) {
      if (context !== document) {
        return;
      }

      // After address form submit the page reloads with ghost cards. Move focus
      // to the "Searching for results..." heading so screen reader users hear
      // that a search is in progress.
      const searchingTitle = context.querySelector('.hdbt-search__results__title--ghost-title');
      if (searchingTitle) {
        searchingTitle.focus({ preventScroll: true });
        searchingTitle.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }

      // `once: true` is correct — each page load has exactly one ghost-to-results
      // swap on these dedicated search pages.
      document.addEventListener(
        'htmx:afterSwap',
        () => {
          // Pager clicks cause a full page reload and add ?page to the URL.
          // Focus the first card link on pager pages, results title on new searches.
          const isPagerNavigation = new URL(window.location.href).searchParams.has('page');
          const firstCard = document.querySelector('.hdbt-search__results .card__link');

          if (isPagerNavigation && firstCard) {
            firstCard.focus({ preventScroll: true });
          } else {
            const resultsTitle = document.querySelector('.hdbt-search__results__title');
            if (resultsTitle) {
              resultsTitle.setAttribute('tabindex', '-1');
              resultsTitle.focus({ preventScroll: true });
            }
          }
        },
        { once: true },
      );
    },
  };
})(Drupal);
