import { useAtomValue, useSetAtom } from 'jotai';
import type { RefObject } from 'react';
import FilterButton from '@/react/common/FilterButton';
import SelectionsWrapper from '@/react/common/SelectionsWrapper';
import {
  clearSelectionsAtom,
  committedBundlesAtom,
  QUERY_TAG_KEY,
  queryAtom,
  removeBundleAtom,
  removeQueryAtom,
} from '../store';

const BUNDLE_LABELS: Record<string, string> = {
  news: Drupal.t('News', {}, { context: 'Site search' }),
  page: Drupal.t('Pages', {}, { context: 'Site search' }),
  landing_page: Drupal.t('Landing pages', {}, { context: 'Site search' }),
  others: Drupal.t('Other content', {}, { context: 'Site search' }),
};

type SelectionsContainerProps = {
  tagRefs: RefObject<Map<string, HTMLElement>>;
};

const SelectionsContainer = ({ tagRefs }: SelectionsContainerProps) => {
  const query = useAtomValue(queryAtom);
  const committedBundles = useAtomValue(committedBundlesAtom);
  const removeBundle = useSetAtom(removeBundleAtom);
  const removeQuery = useSetAtom(removeQueryAtom);
  const clearSelections = useSetAtom(clearSelectionsAtom);

  const registerTag = (key: string) => (el: HTMLElement | null) => {
    if (el) {
      tagRefs.current.set(key, el);
    }
    return () => {
      tagRefs.current.delete(key);
    };
  };

  return (
    <SelectionsWrapper
      modifierClass='hdbt-search--react__selections--site-search'
      showClearButton={Boolean(query) || committedBundles.length > 0}
      resetForm={() => clearSelections()}
    >
      {query && (
        <FilterButton
          key={`query-${query}`}
          ref={registerTag(QUERY_TAG_KEY)}
          value={query}
          clearSelection={() => removeQuery()}
        />
      )}
      {committedBundles.map((bundle) => (
        <FilterButton
          key={bundle}
          ref={registerTag(bundle)}
          value={BUNDLE_LABELS[bundle] ?? bundle}
          clearSelection={() => removeBundle(bundle)}
        />
      ))}
    </SelectionsWrapper>
  );
};

export default SelectionsContainer;
