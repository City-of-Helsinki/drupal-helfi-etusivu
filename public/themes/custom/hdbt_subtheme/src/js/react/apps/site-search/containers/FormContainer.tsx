import { Accordion, AccordionSize, Button, ButtonVariant, Checkbox, Search } from 'hds-react';
import { defaultCheckboxStyle } from '@/react/common/constants/checkboxStyle';
import { useAtom, useSetAtom } from 'jotai';
import { type SyntheticEvent, useCallback, useState } from 'react';
import { stagedBundlesAtom, stagedQueryAtom, submitAllSearchAtom, submitNewsSearchAtom } from '../store';

type FormContainerProps = {
  withBundleFilters?: boolean;
};

const BUNDLE_OPTIONS = [
  { value: 'news_item' as const },
  { value: 'page' as const },
  { value: 'landing_page' as const },
];

const FormContainer = ({ withBundleFilters = false }: FormContainerProps) => {
  const [inputValue, setInputValue] = useAtom(stagedQueryAtom);
  const [stagedBundles, setStagedBundles] = useAtom(stagedBundlesAtom);
  const submitAll = useSetAtom(submitAllSearchAtom);
  const submitNews = useSetAtom(submitNewsSearchAtom);

  const lang = drupalSettings?.path?.currentLanguage ?? 'fi';
  const aiRegisterUrl = drupalSettings?.helfi_site_search?.ai_register_url;

  const toggleBundle = (value: string, checked: boolean) =>
    setStagedBundles(checked ? [...stagedBundles, value] : stagedBundles.filter((b) => b !== value));

  const handleChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => setInputValue(e.target.value), []);

  const handleSend = useCallback(() => {
    withBundleFilters ? submitAll() : submitNews();
  }, [withBundleFilters, submitAll, submitNews]);

  const onSubmit = (event: SyntheticEvent<HTMLFormElement>) => {
    event.preventDefault();
    handleSend();
  };

  const bundleLabels: Record<string, string> = {
    news_item: Drupal.t('News', {}, { context: 'Site search' }),
    page: Drupal.t('Pages', {}, { context: 'Site search' }),
    landing_page: Drupal.t('Landing pages', {}, { context: 'Site search' }),
  };

  const [searchInputProps] = useState({
    className: 'hdbt-search--react__input hdbt-search__search-input',
    texts: {
      label: Drupal.t('Keyword or a question', {}, { context: 'Site search' }),
    },
  });

  return (
    // biome-ignore lint/a11y/useSemanticElements: We use form with role for now
    <form
      className='hdbt-search--react__form-container hdbt-search--react__form-container--site-search'
      role='search'
      onSubmit={onSubmit}
    >
      <Search {...searchInputProps} onChange={handleChange} onSend={handleSend} value={inputValue} />
      {withBundleFilters && (
        <div className='hdbt-search--react__filters-container hdbt-search--react__filters-container--site-search'>
          <Accordion
            border
            card
            className='hdbt-search--react__filters hdbt-search--react__filters--site-search'
            heading={Drupal.t('Refine search results', {}, { context: 'Site search' })}
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
              {BUNDLE_OPTIONS.map(({ value }) => (
                <Checkbox
                  className='hdbt-search--react__filters__checkbox'
                  key={value}
                  id={`site-search-bundle-${value}`}
                  label={bundleLabels[value]}
                  checked={stagedBundles.includes(value)}
                  onChange={(e) => toggleBundle(value, e.target.checked)}
                  style={defaultCheckboxStyle}
                />
              ))}
            </fieldset>
            <div className='hdbt-search--react__submit'>
              <Button className='hdbt-search--react__submit-button' type='submit' variant={ButtonVariant.Primary}>
                {Drupal.t('Refine search', {}, { context: 'Site search' })}
              </Button>
            </div>
          </Accordion>
          <p className='hdbt-search--react__site-search-disclaimer'>
            {Drupal.t('The search uses artificial intelligence.', {}, { context: 'Site search' })}
            {aiRegisterUrl && (
              <>
                &nbsp;
                <a href={aiRegisterUrl}>
                  {Drupal.t('Read more from the artificial intelligence register.', {}, { context: 'Site search' })}
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
