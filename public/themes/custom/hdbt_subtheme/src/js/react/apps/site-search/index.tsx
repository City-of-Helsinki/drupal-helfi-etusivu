import React, { Suspense } from 'react';
import ReactDOM from 'react-dom';
import { GhostList } from '@/react/common/GhostList';
import initSentry from '@/react/common/helpers/Sentry';
import SearchContainer from './containers/SearchContainer';
import AppSettings from './enum/AppSettings';

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
