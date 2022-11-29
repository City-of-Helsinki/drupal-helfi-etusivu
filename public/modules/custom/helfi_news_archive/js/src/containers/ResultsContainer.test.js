import { StateProvider } from '@appbaseio/reactivesearch';
import '@testing-library/jest-dom';

import { renderWithStore as render } from '../test/test-utils';
import ResultsContainer from './ResultsContainer';

test('renders', () => {
  const initialParams = {
    page: 1,
  };

  const resultsContainer = render(
    <StateProvider
      render={({ searchState }) => <ResultsContainer initialParams={initialParams} searchState={searchState} />}
    />
  );
});

test('displays heading', () => {
  const initialParams = {
    page: 1,
  };

  const resultsContainer = render(
    <StateProvider
      render={({ searchState }) => <ResultsContainer initialParams={initialParams} searchState={searchState} />}
    />
  );

  const heading = document.querySelector('h3');
  expect(heading.innerHTML).toEqual('All news items');
});
