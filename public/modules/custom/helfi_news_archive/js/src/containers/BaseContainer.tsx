import { ReactiveBase } from '@appbaseio/reactivesearch';

import Settings from '../enum/Settings';

type Props = {
  children: React.ReactElement;
};

const BaseContainer = ({ children }: Props) => {
  const proxyUrl = drupalSettings?.helfi_news_archive?.elastic_proxy_url;

  if (!proxyUrl && !process.env.REACT_APP_ELASTIC_URL) {
    return null;
  }

  return (
    <ReactiveBase
      app={Settings.INDEX}
      url={proxyUrl || process.env.REACT_APP_ELASTIC_URL}
      theme={{
        colors: {
          primaryColor: 'inherit',
        },
        typography: {
          fontFamily: 'inherit',
        },
      }}
      // Param props set only to prevent functionality
      getSearchParams={() => ''}
      setSearchParams={() => ''}
    >
      {children}
    </ReactiveBase>
  );
};

export default BaseContainer;
