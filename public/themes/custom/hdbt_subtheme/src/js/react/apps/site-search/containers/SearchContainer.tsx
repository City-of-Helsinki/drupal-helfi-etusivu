import { useAtomValue } from 'jotai';
import { useRef } from 'react';
import type { SelectionFocusRefs } from '../hooks/useSelectionFocus';
import { committedBundlesAtom } from '../store';
import FormContainer from './FormContainer';
import ResultsContainer from './ResultsContainer';
import SelectionsContainer from './SelectionsContainer';

const SearchContainer = () => {
  const committedBundles = useAtomValue(committedBundlesAtom);
  const allTabBundle = committedBundles.length > 0 ? committedBundles.join(',') : undefined;
  const tagRefs = useRef(new Map<string, HTMLElement>());
  const filtersRef = useRef<HTMLDivElement>(null);
  const selectionFocusRefs: SelectionFocusRefs = { tags: tagRefs, filters: filtersRef };

  return (
    <div className='component__container'>
      <FormContainer withBundleFilters filtersRef={filtersRef} />
      <SelectionsContainer tagRefs={tagRefs} />
      <ResultsContainer bundle={allTabBundle} selectionFocusRefs={selectionFocusRefs} />
    </div>
  );
};

export default SearchContainer;
