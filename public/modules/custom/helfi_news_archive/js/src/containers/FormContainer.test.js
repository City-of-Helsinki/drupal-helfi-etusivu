import { StateProvider } from '@appbaseio/reactivesearch';

import { renderWithStore as render } from '../test/test-utils';
import FormContainer from './FormContainer';

const initialParams = {
  topics: [{ label: 'Kaupunki ja hallinto', value: 'Kaupunki ja hallinto' }],
  neighbourhoods: [],
  groups: [],
};

test('Renders correctly', () => {
  const formContainer = render(
    <StateProvider render={(props) => <FormContainer initialParams={initialParams} {...props} />} />
  );

  // Should render submit button
  const submitButton = document.querySelector('button[type="submit"]');
  expect(submitButton.textContent).toEqual('Filter');

  // Should render 3 dropdown with labels
  const labels = document.querySelectorAll('label');
  expect(labels.length).toEqual(3);
});
