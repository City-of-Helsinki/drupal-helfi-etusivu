import { useAtomValue } from 'jotai';
import { committedBundlesAtom } from '../store';
import FormContainer from './FormContainer';
import ResultsContainer from './ResultsContainer';

const SearchContainer = () => {
  const committedBundles = useAtomValue(committedBundlesAtom);
  const allTabBundle = committedBundles.length > 0 ? committedBundles.join(',') : undefined;

  return (
    <div className='component__container'>
      <FormContainer withBundleFilters />
      <ResultsContainer bundle={allTabBundle} />
    </div>
  );
};

export default SearchContainer;
