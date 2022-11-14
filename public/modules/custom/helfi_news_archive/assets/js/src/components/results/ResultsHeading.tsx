import { StateProvider } from '@appbaseio/reactivesearch';

import SearchComponents from '../../enum/SearchComponents';

export const ResultsHeading = () => {
  const { RESULTS, SUBMIT } = SearchComponents;

  return (
    <StateProvider
      includeKeys={['value', 'hits']}
      render={({ searchState }) => (
        <h3 className='news-archive__heading'>
          {searchState[SUBMIT] && searchState[SUBMIT].value && searchState[RESULTS] && searchState[RESULTS].hits
            ? Drupal.t(
                Drupal.t('News based on your choices', {}, { context: 'News archive heading' }) +
                  ` (${searchState[RESULTS].hits ? searchState[RESULTS].hits.total : 0})`
              )
            : Drupal.t('All news items', {}, { context: 'News archive heading' })}
        </h3>
      )}
    />
  );
};

export default ResultsHeading;
