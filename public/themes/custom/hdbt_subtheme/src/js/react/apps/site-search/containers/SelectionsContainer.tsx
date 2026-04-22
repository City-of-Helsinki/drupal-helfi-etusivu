import { useAtomValue, useSetAtom } from 'jotai';
import FilterButton from '@/react/common/FilterButton';
import SelectionsWrapper from '@/react/common/SelectionsWrapper';
import { committedBundlesAtom, removeBundleAtom, stagedBundlesAtom, submitAllSearchAtom } from '../store';

const BUNDLE_LABELS: Record<string, string> = {
  news_item: Drupal.t('News', {}, { context: 'Site search' }),
  page: Drupal.t('Pages', {}, { context: 'Site search' }),
  landing_page: Drupal.t('Landing pages', {}, { context: 'Site search' }),
};

const SelectionsContainer = () => {
  const committedBundles = useAtomValue(committedBundlesAtom);
  const removeBundle = useSetAtom(removeBundleAtom);
  const setStagedBundles = useSetAtom(stagedBundlesAtom);
  const submitAll = useSetAtom(submitAllSearchAtom);

  const clearAll = () => {
    setStagedBundles([]);
    submitAll();
  };

  return (
    <SelectionsWrapper
      modifierClass='hdbt-search--react__selections--site-search'
      showClearButton={committedBundles.length > 0}
      resetForm={clearAll}
    >
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
