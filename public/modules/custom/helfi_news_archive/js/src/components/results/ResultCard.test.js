import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

import Result from '../../types/Result';
import ResultCard from './ResultCard';

const mockResult: Result = {
  field_main_image: ['https://helfi-etusivu.docker.so/fi/123.jpg'],
  title: ['News item'],
  published_at: [1651839151],
  url: ['https://helfi-etusivu.docker.so/fi/123'],
};

test('Renders correctly', () => {
  render(<ResultCard {...mockResult} />);

  // Renders title correctly
  expect(screen.getByRole('heading')).toHaveTextContent(mockResult.title[0]);

  // Handles time formatting correctly
  const time = document.querySelector('time');
  expect(time.dateTime).toEqual('2022-05-06T12:12');
  expect(time.childNodes[1].textContent).toEqual('6.5.2022 12:12');

  // Sets href value correctly
  const href = document.querySelector('a').href;
  expect(href).toEqual(mockResult.url[0]);
});
