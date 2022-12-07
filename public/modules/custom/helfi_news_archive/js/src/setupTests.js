import 'intersection-observer';

import { server } from './test/test-utils';

// Mock Drupal.t for tests.
global.Drupal = {
  t: (str, args, options) => str,
};

global.drupalSettings = {
  path: {
    currentLanguage: 'fi',
  },
};

beforeAll(() => {
  server.listen();
});
afterEach(() => server.resetHandlers());
afterAll(() => server.close());
