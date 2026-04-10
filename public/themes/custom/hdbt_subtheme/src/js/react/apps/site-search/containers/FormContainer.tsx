import { Accordion, AccordionSize, Button, ButtonVariant, Checkbox, SearchInput } from 'hds-react';
import { useAtom, useSetAtom } from 'jotai';
import type { SyntheticEvent } from 'react';
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

  const toggleBundle = (value: string, checked: boolean) =>
    setStagedBundles(checked ? [...stagedBundles, value] : stagedBundles.filter((b) => b !== value));

  const handleSubmit = () => {
    withBundleFilters ? submitAll() : submitNews();
  };

  const onSubmit = (event: SyntheticEvent<HTMLFormElement>) => {
    event.preventDefault();
    handleSubmit();
  };

  const bundleLabels: Record<string, string> = {
    news_item: Drupal.t('News', {}, { context: 'Site search' }),
    page: Drupal.t('Pages', {}, { context: 'Site search' }),
    landing_page: Drupal.t('Landing pages', {}, { context: 'Site search' }),
  };

  return (
    // biome-ignore lint/a11y/useSemanticElements: We use form with role for now
    <form className='hdbt-search--react__form-container' role='search' onSubmit={onSubmit}>
      <SearchInput
        label={Drupal.t('Keyword or a question', {}, { context: 'Site search' })}
        value={inputValue}
        onChange={setInputValue}
        onSubmit={handleSubmit}
        className='hdbt-search--react__input'
      />
      {withBundleFilters && (
        <div>
          <Accordion
            border
            card
            className='hdbt-search--react__filters'
            heading={Drupal.t('Refine search results', {}, { context: 'Site search' })}
            headingLevel={2}
            initiallyOpen={false}
            language={window.drupalSettings.path.currentLanguage || 'fi'}
            size={AccordionSize.Small}
          >
            <fieldset className='hdbt-search--react__filters__checkboxes'>
              <legend className='hdbt-search--react__filters__checkboxes-legend'>
                {Drupal.t('Show only', {}, { context: 'Site search' })}
              </legend>
              {BUNDLE_OPTIONS.map(({ value }) => (
                <Checkbox
                  key={value}
                  id={`site-search-bundle-${value}`}
                  label={bundleLabels[value]}
                  checked={stagedBundles.includes(value)}
                  onChange={(e) => toggleBundle(value, e.target.checked)}
                />
              ))}
            </fieldset>
            <div className='hdbt-search--react__submit'>
              <Button className='hdbt-search--react__submit-button' type='submit' variant={ButtonVariant.Primary}>
                {Drupal.t('Refine search', {}, { context: 'Site search' })}
              </Button>
            </div>
          </Accordion>
          <p>
            {Drupal.t('The search uses artificial intelligence.', {}, { context: 'Site search' })}&nbsp;
            <a href='https://www.hel.fi'>
              {Drupal.t('Read more from the artificial intelligence register.', {}, { context: 'Site search' })}
            </a>
          </p>
        </div>
      )}
    </form>
  );
};

export default FormContainer;
