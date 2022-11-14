import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

import { renderWithStore } from '../test/test-utils';
import ResultsContainer from './ResultsContainer';

it('Renders correctly', () => {
  const { getByTestId } = renderWithStore(<ResultsContainer />);

  // Renders heading
  expect(screen.getByRole('heading')).toHaveTextContent('All news');
});
