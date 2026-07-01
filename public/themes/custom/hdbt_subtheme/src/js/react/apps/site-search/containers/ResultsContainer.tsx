import { Notification } from 'hds-react';
import { useAtomValue, useSetAtom } from 'jotai';
import { createRef, Fragment, type SyntheticEvent, useEffect, useRef } from 'react';
import ExternalLink from '@/react/common/ExternalLink';
import { GhostList } from '@/react/common/GhostList';
import Pagination from '@/react/common/Pagination';
import ResultsEmpty from '@/react/common/ResultsEmpty';
import ResultsError from '@/react/common/ResultsError';
import ResultsHeader from '@/react/common/ResultsHeader';
import DebugBlock from '../components/DebugBlock';
import ResultCard from '../components/ResultCard';
import AppSettings from '../enum/AppSettings';
import useSearchQuery from '../hooks/useSearchQuery';
import { pageAtom, queryAtom, setPageAtom } from '../store';

type ResultsContainerProps = {
  bundle?: string;
};

const ResultsContainer = ({ bundle }: ResultsContainerProps) => {
  const query = useAtomValue(queryAtom);
  const page = useAtomValue(pageAtom);
  const setPage = useSetAtom(setPageAtom);
  const links = drupalSettings?.helfi_site_search?.external_links;
  const { data, error, isLoading, isValidating } = useSearchQuery(query, bundle, page);
  const scrollTarget = createRef<HTMLHeadingElement>();
  const lastSeenKeyRef = useRef<string | null>(null);
  const lastDataKeyRef = useRef<string | null>(null);

  const totalHits = data?.total_hits ?? 0;
  const promotedCount = page === 1 ? (data?.promoted?.length ?? 0) : 0;
  const resultsCount = data?.results?.length ?? 0;
  const totalPages = Math.ceil(totalHits / AppSettings.SIZE);
  const isValidQuery = query.length >= AppSettings.MIN_QUERY_LENGTH;
  const resultsClassName = 'hdbt-search--react__results hdbt-search--react__results--site-search';
  const currentSearchKey = isValidQuery ? `${query}::${page}` : null;
  const isLoadingNewSearch = isValidating && currentSearchKey !== lastDataKeyRef.current;

  useEffect(() => {
    if (data && currentSearchKey) {
      lastDataKeyRef.current = currentSearchKey;
    }
  }, [data, currentSearchKey]);

  useEffect(() => {
    if (!isValidQuery || !data) {
      return;
    }
    const currentKey = `${query}::${data.page}`;
    if (currentKey !== lastSeenKeyRef.current) {
      const node = scrollTarget.current;
      if (node) {
        lastSeenKeyRef.current = currentKey;
        node.setAttribute('tabindex', '-1');
        node.focus({ preventScroll: true });
        node.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    }
  }, [data, isValidQuery, query, scrollTarget]);

  useEffect(() => {
    if (!isValidQuery || isLoading || error) {
      return;
    }
    window._paq?.push(['trackSiteSearch', query, bundle || false, totalHits]);
  }, [data, isLoading, error, isValidQuery, query, bundle, totalHits]);

  if (!isValidQuery) {
    return null;
  }

  if ((isLoading && !data) || isLoadingNewSearch) {
    return <GhostList simple modifierClass={resultsClassName} count={Number(AppSettings.SIZE)} />;
  }

  if (error) {
    return <ResultsError error={error} className={resultsClassName} ref={scrollTarget} />;
  }

  const externalLinksNotification = links && (
    <Notification
      className='notification--site-search'
      label={Drupal.t('Looking for these search services?', {}, { context: 'Site search' })}
      type='info'
      headingLevel={3}
    >
      <ul>
        <li>
          <a href={links.jobs}>{Drupal.t('Open jobs', {}, { context: 'Site search' })}</a>
        </li>
        <li>
          <ExternalLink href={links.events} title={Drupal.t('Events', {}, { context: 'Site search' })} />
        </li>
        <li>
          <ExternalLink href={links.decisions} title={Drupal.t('Decisions', {}, { context: 'Site search' })} />
        </li>
        <li>
          <a href={links.contact}>{Drupal.t('Contact information', {}, { context: 'Site search' })}</a>
        </li>
        <li>
          <a href={links.helsinki_near_you}>{Drupal.t('Helsinki near you', {}, { context: 'Site search' })}</a>
        </li>
      </ul>
    </Notification>
  );

  if (!data || totalHits === 0) {
    return (
      <ResultsEmpty
        wrapperClass={`${resultsClassName} hdbt-search--react__results--no-results`}
        ref={scrollTarget}
        resultText={Drupal.t('No results', {}, { context: 'Site search' })}
        bodyText={Drupal.t(
          'Your search did not yield any results. Please use the separate search services below if you are searching for open jobs, events, decisions or contact information.',
          {},
          { context: 'Site search' },
        )}
      >
        {externalLinksNotification}
      </ResultsEmpty>
    );
  }

  const updatePage = (e: SyntheticEvent<HTMLButtonElement>, newPage: number) => {
    e.preventDefault();
    setPage(newPage);
  };

  return (
    <div className={resultsClassName}>
      {DEBUG_MODE && data?.debug && <DebugBlock label='DEBUG — backend response' data={data.debug} />}
      <ResultsHeader
        resultText={Drupal.formatPlural(totalHits, '@count result', '@count results', {}, { context: 'Site search' })}
        ref={scrollTarget}
      />
      {promotedCount > 0 &&
        data.promoted.map((item) => (
          <ResultCard
            key={item.url}
            url={item.url}
            title={item.title}
            description={item.description}
            cardModifierClass='card--site-search card--site-search--promoted'
          />
        ))}
      {resultsCount > 0 &&
        data.results.map((item, index) => (
          <Fragment key={item.url}>
            <ResultCard
              url={item.fragment ? `${item.url}#${item.fragment}` : item.url}
              title={item.metatag_title || item.title}
              description={item.content || undefined}
              bundle={item.bundle}
              publishDate={item.published_at}
              cardModifierClass='card--site-search'
            />
            {(index === 2 || (index === resultsCount - 1 && resultsCount < 3)) && externalLinksNotification}
          </Fragment>
        ))}
      {totalPages > 1 && <Pagination currentPage={page} pages={5} totalPages={totalPages} updatePage={updatePage} />}
    </div>
  );
};

export default ResultsContainer;
