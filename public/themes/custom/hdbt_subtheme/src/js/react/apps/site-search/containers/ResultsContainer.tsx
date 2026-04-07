import { useAtomValue } from 'jotai';
import { GhostList } from '@/react/common/GhostList';
import ResultsEmpty from '@/react/common/ResultsEmpty';
import ResultsError from '@/react/common/ResultsError';
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

  const promotedCount = data?.promoted?.length;
  const resultsCount = data?.results?.length;
  const isValidQuery = query.length >= AppSettings.MIN_QUERY_LENGTH;

  useEffect(() => {
    if (!isValidQuery || isLoading || error) {
      return;
    }

    window._paq?.push(['trackSiteSearch', query, bundle || false, Number(promotedCount) + Number(resultsCount)]);
  }, [data, isLoading, error, isValidQuery, query, bundle, promotedCount, resultsCount]);

  if (!isValidQuery) {
    return null;
  }

  if (isLoading) {
    return <GhostList count={5} />;
  }

  if (error) {
    return <ResultsError error={error} className='react-search__results' />;
  }

  if (!promotedCount && !resultsCount) {
    return <ResultsEmpty />;
  }

  return (
    <div className='react-search__results'>
      {promotedCount !== 0 &&
        data.promoted.map((item) => (
          <ResultCard
            key={item.url}
            url={item.url}
            title={item.title}
            description={item.description}
            cardModifierClass='result-card--promoted'
          />
        ))}
      {resultsCount !== 0 &&
        data.results.map((item) => <ResultCard key={item.url} url={item.url} title={item.title} />)}
    </div>
  );
};

export default ResultsContainer;
