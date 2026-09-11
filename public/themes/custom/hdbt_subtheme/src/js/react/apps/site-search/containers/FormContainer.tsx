import { Accordion, AccordionSize, Button, ButtonVariant, Checkbox, Search } from 'hds-react';
import { useAtom, useSetAtom } from 'jotai';
import { type SyntheticEvent, useCallback, useEffect, useId, useLayoutEffect, useRef, useState } from 'react';
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
  const inputWrapperRef = useRef<HTMLDivElement>(null);
  const inputElRef = useRef<HTMLInputElement | null>(null);
  const [suggestionsAnchor, setSuggestionsAnchor] = useState<HTMLElement | null>(null);
  const suggestionsId = useId();

  // Keeps the latest input value available to the focus listener below,
  // which is set up only once and would otherwise always see the value
  // from when it was attached.
  const inputValueRef = useRef(inputValue);
  useEffect(() => {
    inputValueRef.current = inputValue;
  }, [inputValue]);

  // Search hides its real <input>, so we grab it once it's rendered. Its
  // parent element positions the suggestions list and also contains the
  // input itself, so it doubles as the "are we still inside this feature"
  // boundary for closing suggestions on blur.
  useLayoutEffect(() => {
    const input = inputWrapperRef.current?.querySelector<HTMLInputElement>('input[type="search"]');
    const anchor = input?.parentElement ?? null;
    if (anchor) {
      anchor.style.position = 'relative';
    }
    setSuggestionsAnchor(anchor);

    if (!input || !anchor) return;

    inputElRef.current = input;
    input.setAttribute('aria-haspopup', 'true');
    input.setAttribute('aria-controls', suggestionsId);
    input.setAttribute('aria-expanded', 'false');

    const handleFocus = () => {
      // Suggestions only make sense for an empty input, whether that's on
      // focus, after clearing, or after typing something and deleting it.
      if (inputValueRef.current === '') {
        setSuggestionsOpen(true);
      }
    };
    input.addEventListener('focus', handleFocus);

    // Close once focus leaves both the input and the suggestions list
    // (portaled into anchor), not just when it leaves the whole form.
    const handleFocusOut = () => {
      setTimeout(() => {
        if (!anchor.contains(document.activeElement)) {
          setSuggestionsOpen(false);
        }
      }, 10);
    };
    anchor.addEventListener('focusout', handleFocusOut);

    return () => {
      input.removeEventListener('focus', handleFocus);
      anchor.removeEventListener('focusout', handleFocusOut);
    };
  }, []);

  // Reflect open/closed state to assistive tech.
  useEffect(() => {
    inputElRef.current?.setAttribute('aria-expanded', suggestionsOpen ? 'true' : 'false');
  }, [suggestionsOpen]);

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

  const onSubmit = (event: SyntheticEvent<HTMLFormElement>) => {
    event.preventDefault();
    handleSend();
  };

  const handleKeyDown = useCallback(
    (event: React.KeyboardEvent<HTMLFormElement>) => {
      if (event.key === 'Escape' && suggestionsOpen) {
        setSuggestionsOpen(false);
        event.stopPropagation();
        return;
      }

      const isSearchField = (event.target as HTMLElement | null)?.getAttribute('type') === 'search';

      if (event.key !== 'Enter' || !isSearchField || inputValue.trim()) {
        return;
      }

      event.preventDefault();
      handleSend();
    },
    [inputValue, handleSend, suggestionsOpen],
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
    >
      <div className='hdbt-search--react__input-wrapper' ref={inputWrapperRef}>
        <Search {...searchInputProps} onChange={handleChange} onSend={handleSend} value={inputValue} />
      </div>
      {suggestionsAnchor &&
        suggestionsOpen &&
        suggestions &&
        suggestions.length > 0 &&
        createPortal(
          // Clicking blank space in the list (not a suggestion, not the
          // scrollbar) closes it, so it doesn't stay open over whatever
          // is behind it.
          // biome-ignore lint/a11y/useSemanticElements: fieldset doesn't fit a floating suggestions list
          <ul
            id={suggestionsId}
            className='hdbt-search-suggestions'
            role='group'
            aria-label={Drupal.t('Search suggestions', {}, { context: 'Site search' })}
            onMouseDown={(e) => {
              const target = e.target as HTMLElement;
              const clickedScrollbar = target === e.currentTarget && e.nativeEvent.offsetX >= target.clientWidth;
              if (clickedScrollbar) {
                // Keep the input focused so Search doesn't close on this click.
                e.preventDefault();
                return;
              }
              if (!target.closest('button')) {
                setSuggestionsOpen(false);
              }
            }}
          >
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
