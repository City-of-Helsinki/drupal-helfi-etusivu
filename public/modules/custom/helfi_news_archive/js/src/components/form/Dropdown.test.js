import { StateProvider } from '@appbaseio/reactivesearch';

import { renderWithStore as render } from '../../test/test-utils';
import Dropdown from './Dropdown';

const mockAggregations = {
  topics: {
    buckets: [{ key: 'Interesting topic', doc_count: 12 }],
  },
};

test('Renders correctly', () => {
  render(
    <StateProvider
      render={(props) => (
        <Dropdown
          aggregations={mockAggregations}
          componentId='mockId'
          initialize={() => null}
          initialValue={['Interesting topic']}
          label='Topic filter'
          indexKey='indexKey'
          setQuery={() => null}
          setValue={() => null}
          value={['Interesting topic']}
          {...props}
        />
      )}
    />
  );
});
