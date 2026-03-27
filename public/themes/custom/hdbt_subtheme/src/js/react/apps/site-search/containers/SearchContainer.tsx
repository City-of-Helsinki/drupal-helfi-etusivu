import { Tabs } from 'hds-react';
import { useAtomValue, useSetAtom } from 'jotai';
import { activeTabAtom, committedBundlesAtom, setActiveTabAtom } from '../store';
import FormContainer from './FormContainer';
import ResultsContainer from './ResultsContainer';

const NEWS_BUNDLE = 'news_item,news_article';

const SearchContainer = () => {
  const activeTab = useAtomValue(activeTabAtom);
  const setActiveTab = useSetAtom(setActiveTabAtom);
  const committedBundles = useAtomValue(committedBundlesAtom);
  const allTabBundle = committedBundles.length > 0 ? committedBundles.join(',') : undefined;

  return (
    <Tabs initiallyActiveTab={activeTab}>
      <Tabs.TabList>
        <Tabs.Tab onClick={() => setActiveTab(0)}>
          {Drupal.t('All', {}, { context: 'Site search: all tab label' })}
        </Tabs.Tab>
        <Tabs.Tab onClick={() => setActiveTab(1)}>
          {Drupal.t('News', {}, { context: 'Site search: news tab label' })}
        </Tabs.Tab>
      </Tabs.TabList>
      <Tabs.TabPanel>
        <FormContainer withBundleFilters />
        <ResultsContainer bundle={allTabBundle} />
      </Tabs.TabPanel>
      <Tabs.TabPanel>
        <FormContainer />
        <ResultsContainer bundle={NEWS_BUNDLE} />
      </Tabs.TabPanel>
    </Tabs>
  );
};

export default SearchContainer;
