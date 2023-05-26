import { ReactiveList } from '@appbaseio/reactivesearch';
import { useRef } from 'react';

import MostReadNews from '../components/results/MostReadNews';
import Pagination from '../components/results/Pagination';
import ResultCard from '../components/results/ResultCard';
import ResultsHeading from '../components/results/ResultsHeading';
import IndexFields from '../enum/IndexFields';
import SearchComponents from '../enum/SearchComponents';
import { setParams } from '../helpers/Params';
import useLanguageQuery from '../hooks/useLanguageQuery';
import useWindowDimensions from '../hooks/useWindowDimensions';
import InitialState from '../types/InitialState';
import Result from '../types/Result';
import SearchState from '../types/SearchState';

type ResultsContainerProps = {
  initialParams: InitialState;
  searchState: SearchState;
};

type ResultsData = {
  data: Result[];
};

const ResultsContainer = ({ initialParams, searchState }: ResultsContainerProps) => {
  const dimensions = useWindowDimensions();
  const languageFilter = useLanguageQuery();
  const resultsWrapper = useRef<HTMLDivElement | null>(null);
  const pages = dimensions.isMobile ? 3 : 5;

  const nodeFilter = {
    bool: {
      filter: [
        {
          term: {
            entity_type: 'node',
          },
        },
      ],
    },
  };

  return (
    <div ref={resultsWrapper} className='news-wrapper main-content'>
      <div className='layout-content'>
        <ResultsHeading />
        <ReactiveList
          className='news-container'
          componentId={SearchComponents.RESULTS}
          // Seems like a bug in ReactiveSearch.
          // Setting defaultPage prop does nothing.
          // currentPage props used in source but missing in props type declarations.
          // @ts-ignore
          currentPage={initialParams.page}
          dataField={IndexFields.PUBLISHED_AT}
          onPageChange={() => {
            setParams(searchState);

            if (!resultsWrapper.current) {
              return;
            }

            if (Math.abs(resultsWrapper.current.getBoundingClientRect().y) < window.scrollY) {
              resultsWrapper.current.scrollIntoView({ behavior: 'smooth' });
            }
          }}
          pages={pages}
          pagination={true}
          defaultQuery={() => ({
            query: {
              ...languageFilter,
              ...nodeFilter,
            },
          })}
          render={({ data }: ResultsData) => (
            <ul className='news-listing news-listing--teasers'>
              {data.map((item: Result) => (
                <ResultCard key={item._id} {...item} />
              ))}
            </ul>
          )}
          renderNoResults={() => (
            <div className='news-listing__no-results'>
              {Drupal.t('No results found', {}, { context: 'News archive no results' })}
            </div>
          )}
          renderPagination={(props) => <Pagination {...props} />}
          react={{
            and: [SearchComponents.SUBMIT],
          }}
          showResultStats={false}
          sortBy={'desc'}
          size={10}
        />
      </div>
      <MostReadNews />
    </div>
  );
};

export default ResultsContainer;
