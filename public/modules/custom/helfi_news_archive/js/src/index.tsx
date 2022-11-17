import React from 'react';
import ReactDOM from 'react-dom';

import BaseContainer from './containers/BaseContainer';
import SearchContainer from './containers/SearchContainer';

const rootSelector: string = 'helfi-etusivu-news-search';
const rootElement: HTMLElement | null = document.getElementById(rootSelector);

if (rootElement) {
  // // Fire an event when pushing state so we may listen for it
  // window.history.pushState = new Proxy(window.history.pushState, {
  //   apply: (target, thisArg, argArray: [data: any, unused: string, url?: string | URL | null | undefined]) => {
  //     const event = new Event('pushstate');
  //     window.dispatchEvent(event);
  //     return target.apply(thisArg, argArray);
  //   }
  // });

  ReactDOM.render(
    <React.StrictMode>
      <BaseContainer>
        <SearchContainer />
      </BaseContainer>
    </React.StrictMode>,
    rootElement
  );
}
