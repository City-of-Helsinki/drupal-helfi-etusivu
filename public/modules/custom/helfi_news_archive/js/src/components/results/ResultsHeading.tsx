import { StateProvider } from '@appbaseio/reactivesearch';
import { IconRss } from 'hds-react';

import SearchComponents from '../../enum/SearchComponents';
import RssFeedLink from './RssFeedLink';

export const ResultsHeading = () => {
  const { RESULTS, SUBMIT } = SearchComponents;

  return (
    <StateProvider
      includeKeys={['value', 'hits']}
      render={({ searchState }) => (
        <div className='news-archive__heading'>
          <h3 className='news-archive__title'>
            <span>
              {searchState[SUBMIT] && searchState[SUBMIT].value && searchState[RESULTS] && searchState[RESULTS].hits
                ? Drupal.t(
                    Drupal.t('News based on your choices', {}, { context: 'News archive heading' }) +
                      ` (${searchState[RESULTS].hits ? searchState[RESULTS].hits.total : 0})`
                  )
                : Drupal.t('All news items', {}, { context: 'News archive heading' })}
            </span>
          </h3>
          <span className='news-archive__feed-link'>
            <IconRss />
            <RssFeedLink />
          </span>
        </div>
      )}
    />
  );
};

export default ResultsHeading;
