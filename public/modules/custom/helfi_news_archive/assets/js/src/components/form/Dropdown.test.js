import { render } from '@testing-library/react';

import Dropdown from './Dropdown';

const mockAggregations = {
  topics: {
    buckets: [{ key: 'Interesting topic', doc_count: 12 }],
  },
};

test('Renders correctly', () => {
  render(
    <Dropdown
      aggregations={mockAggregations}
      label='Topic filter'
      indexKey='indexKey'
      setQuery={() => null}
      setValue={() => null}
      value={['Interesting topic']}
    />
  );
});
