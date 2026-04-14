import { useAtomValue } from 'jotai';
import { createRef, Fragment, useEffect } from 'react';
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

const OTHER_SEARCHES: Record<string, { jobs: string; events: string; decisions: string; contact: string }> = {
  fi: {
    jobs: 'https://www.hel.fi/fi/avoimet-tyopaikat',
    events: 'https://tapahtumat.hel.fi/fi',
    decisions: 'https://paatokset.hel.fi/fi',
    contact: 'https://www.hel.fi/fi/paatoksenteko-ja-hallinto/ota-yhteytta-helsingin-kaupunkiin',
  },
  sv: {
    jobs: 'https://www.hel.fi/sv/lediga-jobb',
    events: 'https://tapahtumat.hel.fi/sv',
    decisions: 'https://paatokset.hel.fi/sv',
    contact: 'https://www.hel.fi/sv/beslutsfattande-och-forvaltning/kontakta-helsingfors-stad',
  },
  en: {
    jobs: 'https://www.hel.fi/en/open-jobs',
    events: 'https://tapahtumat.hel.fi/en',
    decisions: 'https://paatokset.hel.fi/en',
    contact: 'https://www.hel.fi/en/decision-making/contact-the-city-of-helsinki',
  },
};

const ResultsContainer = ({ bundle }: ResultsContainerProps) => {
  const query = useAtomValue(queryAtom);
  const lang = drupalSettings?.path?.currentLanguage ?? 'en';
  const links = OTHER_SEARCHES[lang] ?? OTHER_SEARCHES.en;
  const { data, error, isLoading } = useSearchQuery(query, bundle);
  const scrollTarget = createRef<HTMLHeadingElement>();

  const promotedCount = data?.promoted?.length ?? 0;
  const resultsCount = data?.results?.length ?? 0;
  const total = promotedCount + resultsCount;
  const isValidQuery = query.length >= AppSettings.MIN_QUERY_LENGTH;
  const resultsClassName = 'hdbt-search--react__results hdbt-search--react__results--site-search';

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
    return <ResultsError error={error} className={resultsClassName} />;
  }

  if (!data || !total) {
    return <ResultsEmpty />;
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
            {(index === 2 || (index === resultsCount - 1 && resultsCount < 3)) && (
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
