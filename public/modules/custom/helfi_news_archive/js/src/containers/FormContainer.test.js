import { renderWithStore } from '../test/test-utils';
import FormContainer from './FormContainer';

const mockInitialState = {
  topics: [{ label: 'Kaupunki ja hallinto', value: 'Kaupunki ja hallinto' }],
  neighbourhoods: [],
  groups: [],
};

test('Renders correctly', () => {
  const formContainer = renderWithStore(<FormContainer initialState={mockInitialState} />);

  // Should render submit button
  const submitButton = document.querySelector('button[type="submit"]');
  expect(submitButton.textContent).toEqual('Filter');

  // Should render 3 dropdown with labels
  const labels = document.querySelectorAll('label');
  expect(labels.length).toEqual(3);

  // Should remove selections when clear all -button is pressed
  const removeAll = document.querySelector('.news-form__clear-all button');
  const selectionTag = document.evaluate(
    '//span[text()="Kaupunki ja hallinto"]',
    document,
    null,
    XPathResult.FIRST_ORDERED_NODE_TYPE,
    null
  );
  expect(selectionTag.singleNodeValue).not.toBe(null);
  removeAll.click();
  const removedSelectionTag = document.evaluate(
    '//span[text()="Kaupunki ja hallinto"]',
    document,
    null,
    XPathResult.FIRST_ORDERED_NODE_TYPE,
    null
  );
  expect(removedSelectionTag.singleNodeValue).toBe(null);
});
