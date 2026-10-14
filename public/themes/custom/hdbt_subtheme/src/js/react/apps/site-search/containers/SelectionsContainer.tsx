import { useAtomValue, useSetAtom } from 'jotai';
import FilterButton from '@/react/common/FilterButton';
import SelectionsWrapper from '@/react/common/SelectionsWrapper';
import {
  committedBundlesAtom,
  queryAtom,
  queryResetCountAtom,
  removeBundleAtom,
  removeQueryAtom,
  stagedBundlesAtom,
  stagedQueryAtom,
  submitAllSearchAtom,
} from '../store';

const BUNDLE_LABELS: Record<string, string> = {
  news: Drupal.t('News', {}, { context: 'Site search' }),
  page: Drupal.t('Pages', {}, { context: 'Site search' }),
  landing_page: Drupal.t('Landing pages', {}, { context: 'Site search' }),
  others: Drupal.t('Other content', {}, { context: 'Site search' }),
};

const SelectionsContainer = () => {
  const query = useAtomValue(queryAtom);
  const committedBundles = useAtomValue(committedBundlesAtom);
  const removeBundle = useSetAtom(removeBundleAtom);
  const removeQuery = useSetAtom(removeQueryAtom);
  const setStagedQuery = useSetAtom(stagedQueryAtom);
  const setStagedBundles = useSetAtom(stagedBundlesAtom);
  const setQueryResetCount = useSetAtom(queryResetCountAtom);
  const submitAll = useSetAtom(submitAllSearchAtom);

  const clearAll = () => {
    setStagedQuery('');
    setQueryResetCount((count) => count + 1);
    setStagedBundles([]);
    submitAll();
  };

  return (
    <SelectionsWrapper
      modifierClass='hdbt-search--react__selections--site-search'
      showClearButton={Boolean(query) || committedBundles.length > 0}
      resetForm={clearAll}
    >
      {query && <FilterButton key={`query-${query}`} value={query} clearSelection={() => removeQuery()} />}
      {committedBundles.map((bundle) => (
        <FilterButton
          key={bundle}
          value={BUNDLE_LABELS[bundle] ?? bundle}
          clearSelection={() => removeBundle(bundle)}
        />
      ))}
    </SelectionsWrapper>
  );
};

export default SelectionsContainer;
