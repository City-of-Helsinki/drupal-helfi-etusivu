import { StateProvider } from '@appbaseio/reactivesearch';

import FormContainer from './FormContainer';
import ResultsContainer from './ResultsContainer';

// @todo: Remove comments once https://helsinkisolutionoffice.atlassian.net/browse/HDS-1210 is done
const SearchContainer = () => {
  return (
    <div>
      <StateProvider
        render={({ searchState, setSearchState }) => (
          <FormContainer searchState={searchState} setSearchState={setSearchState} />
        )}
      />
      <ResultsContainer />
    </div>
  );
};

export default SearchContainer;
