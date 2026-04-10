import { useAtomValue } from 'jotai';
import { createRef } from 'react';
import { GhostList } from '@/react/common/GhostList';
import ResultsEmpty from '@/react/common/ResultsEmpty';
import ResultsError from '@/react/common/ResultsError';
import ResultsHeader from '@/react/common/ResultsHeader';
import ResultCard from '../components/ResultCard';
import AppSettings from '../enum/AppSettings';
import useSearchQuery from '../hooks/useSearchQuery';
import { queryAtom } from '../store';
import { useEffect } from 'react';

type ResultsContainerProps = {
  bundle?: string;
};

const ResultsContainer = ({ bundle }: ResultsContainerProps) => {
  const query = useAtomValue(queryAtom);
  const { data, error, isLoading } = useSearchQuery(query, bundle);
  const scrollTarget = createRef<HTMLHeadingElement>();

  const promotedCount = data?.promoted?.length ?? 0;
  const resultsCount = data?.results?.length ?? 0;
  const total = promotedCount + resultsCount;
  const isValidQuery = query.length >= AppSettings.MIN_QUERY_LENGTH;

  useEffect(() => {
    if (!isValidQuery || isLoading || error) {
      return;
    }
    window._paq?.push(['trackSiteSearch', query, bundle || false, total]);
  }, [data, isLoading, error, isValidQuery, query, bundle, total]);

  if (!isValidQuery) {
    return null;
  }

  if (isLoading) {
    return <GhostList count={5} />;
  }

  if (error) {
    return <ResultsError error={error} className='react-search__results' />;
  }

  if (!total) {
    return <ResultsEmpty />;
  }

  return (
    <div className='hdbt-search--react__results hdbt-search--react__results--site-search'>
      <ResultsHeader
        resultText={Drupal.formatPlural(
          total,
          '@count search result',
          '@count search results',
          {},
          { context: 'Site search' },
        )}
        ref={scrollTarget}
      />
      {promotedCount > 0 &&
        data.promoted.map((item) => (
          <ResultCard
            key={item.url}
            url={item.url}
            title={item.title}
            description={item.description}
            cardModifierClass='result-card--promoted'
          />
        ))}
      {resultsCount > 0 && data.results.map((item) => <ResultCard key={item.url} url={item.url} title={item.title} />)}
    </div>
  );
};

export default ResultsContainer;
