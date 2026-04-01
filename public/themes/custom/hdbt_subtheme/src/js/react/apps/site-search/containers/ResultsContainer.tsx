import { useAtomValue } from 'jotai';
import { GhostList } from '@/react/common/GhostList';
import ResultsEmpty from '@/react/common/ResultsEmpty';
import ResultsError from '@/react/common/ResultsError';
import ResultCard from '../components/ResultCard';
import AppSettings from '../enum/AppSettings';
import useSearchQuery from '../hooks/useSearchQuery';
import { queryAtom } from '../store';

type ResultsContainerProps = {
  bundle?: string;
};

const ResultsContainer = ({ bundle }: ResultsContainerProps) => {
  const query = useAtomValue(queryAtom);
  const { data, error, isLoading } = useSearchQuery(query, bundle);

  if (query.length < AppSettings.MIN_QUERY_LENGTH) {
    return null;
  }

  if (isLoading) {
    return <GhostList count={5} />;
  }

  if (error) {
    return <ResultsError error={error} className='react-search__results' />;
  }

  const hasPromoted = data?.promoted?.length > 0;
  const hasResults = data?.results?.length > 0;

  if (!hasPromoted && !hasResults) {
    return <ResultsEmpty />;
  }

  return (
    <div className='react-search__results'>
      {hasPromoted &&
        data.promoted.map((item) => (
          <ResultCard
            key={item.url}
            url={item.url}
            title={item.title}
            description={item.description}
            cardModifierClass='result-card--promoted'
          />
        ))}
      {hasResults && data.results.map((item) => <ResultCard key={item.url} url={item.url} title={item.title} />)}
    </div>
  );
};

export default ResultsContainer;
