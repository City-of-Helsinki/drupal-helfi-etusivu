import { useAtomValue, useSetAtom } from 'jotai';
import type { RefObject } from 'react';
import FilterButton from '@/react/common/FilterButton';
import SelectionsWrapper from '@/react/common/SelectionsWrapper';
import { clearBundlesAtom, committedBundlesAtom, removeBundleAtom } from '../store';

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
  const committedBundles = useAtomValue(committedBundlesAtom);
  const removeBundle = useSetAtom(removeBundleAtom);
  const clearBundles = useSetAtom(clearBundlesAtom);

  return (
    <SelectionsWrapper
      modifierClass='hdbt-search--react__selections--site-search'
      showClearButton={committedBundles.length > 0}
      resetForm={() => clearBundles()}
    >
      {committedBundles.map((bundle) => (
        <FilterButton
          key={bundle}
          ref={(el) => {
            if (el) {
              tagRefs.current.set(bundle, el);
            }
            return () => {
              tagRefs.current.delete(bundle);
            };
          }}
          value={BUNDLE_LABELS[bundle] ?? bundle}
          clearSelection={() => removeBundle(bundle)}
        />
      ))}
    </SelectionsWrapper>
  );
};

export default SelectionsContainer;
