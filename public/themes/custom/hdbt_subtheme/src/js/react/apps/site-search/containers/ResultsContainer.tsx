import { useAtomValue } from 'jotai';
import { createRef, Fragment, useEffect } from 'react';
import useScrollToResults from '@/react/common/hooks/useScrollToResults';
import { Notification } from 'hds-react';
import { GhostList } from '@/react/common/GhostList';
import ExternalLink from '@/react/common/ExternalLink';
import ResultsEmpty from '@/react/common/ResultsEmpty';
import ResultsError from '@/react/common/ResultsError';
import ResultsHeader from '@/react/common/ResultsHeader';
import ResultCard from '../components/ResultCard';
import AppSettings from '../enum/AppSettings';
import useSearchQuery from '../hooks/useSearchQuery';
import { queryAtom } from '../store';

type ResultsContainerProps = {
  bundle?: string;
};

const ResultsContainer = ({ bundle }: ResultsContainerProps) => {
  const query = useAtomValue(queryAtom);
  const links = drupalSettings?.helfi_site_search?.external_links;
  const { data, error, isLoading } = useSearchQuery(query, bundle);
  const scrollTarget = createRef<HTMLHeadingElement>();

  const promotedCount = data?.promoted?.length ?? 0;
  const resultsCount = data?.results?.length ?? 0;
  const total = promotedCount + resultsCount;
  const isValidQuery = query.length >= AppSettings.MIN_QUERY_LENGTH;
  const resultsClassName = 'hdbt-search--react__results hdbt-search--react__results--site-search';

  useScrollToResults(scrollTarget, !isLoading && isValidQuery);

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
    return <GhostList simple modifierClass={resultsClassName} count={10} />;
  }

  if (error) {
    return <ResultsError error={error} className={resultsClassName} ref={scrollTarget} />;
  }

  if (!data || !total) {
    return <ResultsEmpty ref={scrollTarget} />;
  }

  return (
    <div className={resultsClassName}>
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
            cardModifierClass='card--site-search card--site-search--promoted'
          />
        ))}
      {resultsCount > 0 &&
        data.results.map((item, index) => (
          <Fragment key={item.url}>
            <ResultCard url={item.url} title={item.title} bundle={item.bundle} cardModifierClass='card--site-search' />
            {links && (index === 2 || (index === resultsCount - 1 && resultsCount < 3)) && (
              <Notification
                className='notification--site-search'
                label={Drupal.t('Go to external search services', {}, { context: 'Site search' })}
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
                    <ExternalLink
                      href={links.decisions}
                      title={Drupal.t('Decisions', {}, { context: 'Site search' })}
                    />
                  </li>
                  <li>
                    <a href={links.contact}>{Drupal.t('Contact', {}, { context: 'Site search' })}</a>
                  </li>
                </ul>
              </Notification>
            )}
          </Fragment>
        ))}
    </div>
  );
};

export default ResultsContainer;
