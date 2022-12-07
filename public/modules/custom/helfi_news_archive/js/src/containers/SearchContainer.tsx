import { StateProvider } from '@appbaseio/reactivesearch';
import { Fragment } from 'react';

import { getInitialValues } from '../helpers/Params';
import FormContainer from './FormContainer';
import ResultsContainer from './ResultsContainer';

const SearchContainer = () => {
  const initialParams = getInitialValues();

  return (
    <div>
      <StateProvider
        render={({ searchState, setSearchState }) => (
          <Fragment>
            <FormContainer initialParams={initialParams} searchState={searchState} setSearchState={setSearchState} />
            <ResultsContainer initialParams={initialParams} searchState={searchState} />
          </Fragment>
        )}
      />
    </div>
  );
};

export default SearchContainer;
