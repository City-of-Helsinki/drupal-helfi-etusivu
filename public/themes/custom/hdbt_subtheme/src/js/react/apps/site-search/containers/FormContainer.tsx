import { Button, ButtonVariant, Checkbox, TextInput } from 'hds-react';
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

  const onSubmit = (event: SyntheticEvent<HTMLFormElement>) => {
    event.preventDefault();
    withBundleFilters ? submitAll() : submitNews();
  };

  const bundleLabels: Record<string, string> = {
    news_item: Drupal.t('News', {}, { context: 'Site search: bundle filter' }),
    page: Drupal.t('Page', {}, { context: 'Site search: bundle filter' }),
    landing_page: Drupal.t('Landing page', {}, { context: 'Site search: bundle filter' }),
  };

  return (
    // biome-ignore lint/a11y/useSemanticElements: We use form with role for now
    <form className='hdbt-search--react__form-container' role='search' onSubmit={onSubmit}>
      <TextInput
        id={withBundleFilters ? 'site-search-input-all' : 'site-search-input-news'}
        label={Drupal.t('Search', {}, { context: 'Site search: input label' })}
        value={inputValue}
        onChange={(e) => setInputValue(e.target.value)}
        className='hdbt-search--react__input'
      />
      {withBundleFilters && (
        <div className='hdbt-search--react__filters'>
          {BUNDLE_OPTIONS.map(({ value }) => (
            <Checkbox
              key={value}
              id={`site-search-bundle-${value}`}
              label={bundleLabels[value]}
              checked={stagedBundles.includes(value)}
              onChange={(e) => toggleBundle(value, e.target.checked)}
            />
          ))}
        </div>
      )}
      <div className='hdbt-search--react__submit'>
        <Button className='hdbt-search--react__submit-button' type='submit' variant={ButtonVariant.Primary}>
          {Drupal.t('Search', {}, { context: 'React search: submit button label' })}
        </Button>
      </div>
    </form>
  );
};

export default FormContainer;
