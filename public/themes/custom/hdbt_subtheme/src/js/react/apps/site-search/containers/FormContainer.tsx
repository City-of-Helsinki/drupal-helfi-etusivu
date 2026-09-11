import { Accordion, AccordionSize, Button, ButtonVariant, Checkbox, Search } from 'hds-react';
import { useAtom, useSetAtom } from 'jotai';
import { type SyntheticEvent, useCallback, useEffect, useLayoutEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { defaultCheckboxStyle } from '@/react/common/constants/checkboxStyle';
import useSearchSuggestions from '../hooks/useSearchSuggestions';
import { stagedBundlesAtom, stagedQueryAtom, submitAllSearchAtom, submitNewsSearchAtom } from '../store';

type FormContainerProps = {
  withBundleFilters?: boolean;
};

const BUNDLE_OPTIONS = [
  {
    value: 'news',
    label: Drupal.t('News', {}, { context: 'Site search' }),
  },
  {
    value: 'others',
    label: Drupal.t('Other content', {}, { context: 'Site search' }),
  },
];

const FormContainer = ({ withBundleFilters = false }: FormContainerProps) => {
  const [inputValue, setInputValue] = useAtom(stagedQueryAtom);
  const [stagedBundles, setStagedBundles] = useAtom(stagedBundlesAtom);
  const submitAll = useSetAtom(submitAllSearchAtom);
  const submitNews = useSetAtom(submitNewsSearchAtom);

  const lang = drupalSettings?.path?.currentLanguage ?? 'fi';
  const aiRegisterUrl = drupalSettings?.helfi_site_search?.ai_register_url;

  const { data: suggestions } = useSearchSuggestions(lang);
  const [suggestionsOpen, setSuggestionsOpen] = useState(false);
  const formRef = useRef<HTMLFormElement>(null);
  const inputWrapperRef = useRef<HTMLDivElement>(null);
  const [suggestionsAnchor, setSuggestionsAnchor] = useState<HTMLElement | null>(null);

  // Keeps the latest input value available to the focus listener below,
  // which is set up only once and would otherwise always see the value
  // from when it was attached.
  const inputValueRef = useRef(inputValue);
  useEffect(() => {
    inputValueRef.current = inputValue;
  }, [inputValue]);

  // Search hides its real <input>, so we grab it once it's rendered. We use
  // its parent element to position the suggestions list, and attach our own
  // focus listener directly to it.
  useLayoutEffect(() => {
    const input = inputWrapperRef.current?.querySelector<HTMLInputElement>('input[type="search"]');
    const anchor = input?.parentElement ?? null;
    if (anchor) {
      anchor.style.position = 'relative';
    }
    setSuggestionsAnchor(anchor);

    if (!input) return;
    const handleFocus = () => {
      // Suggestions only make sense for an empty input, whether that's on
      // focus, after clearing, or after typing something and deleting it.
      if (inputValueRef.current === '') {
        setSuggestionsOpen(true);
      }
    };
    input.addEventListener('focus', handleFocus);
    return () => input.removeEventListener('focus', handleFocus);
  }, []);

  const toggleBundle = (value: string, checked: boolean) =>
    setStagedBundles(checked ? [...stagedBundles, value] : stagedBundles.filter((b) => b !== value));

  const handleChange = useCallback(
    (e: React.ChangeEvent<HTMLInputElement>) => {
      if (!e.target.value && !e.nativeEvent) return;
      setInputValue(e.target.value);
      // Not autocomplete: typing closes the suggestions, clearing the
      // input back to empty (or focusing an empty one) shows them again.
      setSuggestionsOpen(e.target.value === '');
    },
    [setInputValue],
  );

  const handleSend = useCallback(() => {
    setSuggestionsOpen(false);
    withBundleFilters ? submitAll() : submitNews();
  }, [withBundleFilters, submitAll, submitNews]);

  const selectSuggestion = useCallback(
    (term: string) => {
      setInputValue(term);
      handleSend();
    },
    [setInputValue, handleSend],
  );

  // Close suggestions once focus leaves the form, mirroring hdbt's vanilla
  // searchSuggestions.js behavior. The small delay lets focus land on
  // whatever was clicked before.
  useEffect(() => {
    const form = formRef.current;
    if (!form) return;
    const handleFocusOut = () => {
      setTimeout(() => {
        if (!form.contains(document.activeElement)) {
          setSuggestionsOpen(false);
        }
      }, 10);
    };
    form.addEventListener('focusout', handleFocusOut);
    return () => form.removeEventListener('focusout', handleFocusOut);
  }, []);

  const onSubmit = (event: SyntheticEvent<HTMLFormElement>) => {
    event.preventDefault();
    handleSend();
  };

  const handleKeyDown = useCallback(
    (event: React.KeyboardEvent<HTMLFormElement>) => {
      const isSearchField = (event.target as HTMLElement | null)?.getAttribute('type') === 'search';

      if (event.key !== 'Enter' || !isSearchField || inputValue.trim()) {
        return;
      }

      event.preventDefault();
      handleSend();
    },
    [inputValue, handleSend],
  );

  const [searchInputProps] = useState({
    className: 'hdbt-search--react__input hdbt-search__search-input',
    texts: {
      language: lang,
      label: Drupal.t('Search term or question', {}, { context: 'Site search' }),
      searchPlaceholder: undefined,
    },
  });

  return (
    // biome-ignore lint/a11y/useSemanticElements: We use form with role for now
    <form
      className='hdbt-search--react__form-container hdbt-search--react__form-container--site-search'
      role='search'
      onSubmit={onSubmit}
      onKeyDown={handleKeyDown}
      ref={formRef}
    >
      <div className='hdbt-search--react__input-wrapper' ref={inputWrapperRef}>
        <Search {...searchInputProps} onChange={handleChange} onSend={handleSend} value={inputValue} />
      </div>
      {suggestionsAnchor &&
        suggestionsOpen &&
        suggestions &&
        suggestions.length > 0 &&
        createPortal(
          <ul className='hdbt-search-suggestions'>
            {suggestions.map(({ id, term }) => (
              <li key={id} className='hdbt-search-suggestions__option'>
                <button
                  type='button'
                  className='hdbt-search-suggestions__option__button'
                  onClick={() => selectSuggestion(term)}
                >
                  {term}
                </button>
              </li>
            ))}
          </ul>,
          suggestionsAnchor,
        )}
      {withBundleFilters && (
        <div className='hdbt-search--react__filters-container hdbt-search--react__filters-container--site-search'>
          <Accordion
            border
            card
            className='hdbt-search--react__filters hdbt-search--react__filters--site-search'
            heading={Drupal.t('Filter search results', {}, { context: 'Site search' })}
            headingLevel={2}
            initiallyOpen={false}
            language={lang}
            size={AccordionSize.Small}
            theme={{
              '--padding-horizontal': 'var(--spacing-s)',
              '--header-outline-color-focus': 'var(--color-black-90)',
            }}
          >
            <fieldset className='hdbt-search--react__filters__fieldset'>
              <legend className='hdbt-search--react__filters__fieldset-legend'>
                {Drupal.t('Show only', {}, { context: 'Site search' })}
              </legend>
              {BUNDLE_OPTIONS.map(({ value, label }) => (
                <Checkbox
                  className='hdbt-search--react__filters__checkbox'
                  key={value}
                  id={`site-search-bundle-${value}`}
                  label={label}
                  checked={stagedBundles.includes(value)}
                  onChange={(e) => toggleBundle(value, e.target.checked)}
                  style={defaultCheckboxStyle}
                />
              ))}
            </fieldset>
            <div className='hdbt-search--react__submit'>
              <Button className='hdbt-search--react__submit-button' type='submit' variant={ButtonVariant.Primary}>
                {Drupal.t('Filter search results', {}, { context: 'Site search submit' })}
              </Button>
            </div>
          </Accordion>
          <p className='hdbt-search--react__site-search-disclaimer'>
            {Drupal.t('The search uses artificial intelligence.', {}, { context: 'Site search' })}
            {aiRegisterUrl && (
              <>
                &nbsp;
                <a href={aiRegisterUrl}>
                  {Drupal.t('Read about the use of AI in search.', {}, { context: 'Site search' })}
                </a>
              </>
            )}
          </p>
        </div>
      )}
    </form>
  );
};

export default FormContainer;
