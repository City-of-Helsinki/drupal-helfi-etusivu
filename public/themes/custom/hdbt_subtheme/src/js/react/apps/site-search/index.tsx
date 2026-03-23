import React, { Suspense } from 'react';
import ReactDOM from 'react-dom';

import initSentry from '@/react/common/helpers/Sentry';
import { GhostList } from '@/react/common/GhostList';
import AppSettings from './enum/AppSettings';
import SearchContainer from './containers/SearchContainer';

initSentry();

const rootElement: HTMLElement | null = document.getElementById(AppSettings.ROOT_ID);

if (rootElement) {
  ReactDOM.render(
    <React.StrictMode>
      <Suspense fallback={<GhostList count={5} />}>
        <SearchContainer />
      </Suspense>
    </React.StrictMode>,
    rootElement,
  );
}
