import { ReactiveComponent } from '@appbaseio/reactivesearch';
import { useRef, useState } from 'react';

import Dropdown from '../components/form//Dropdown';
import SubmitButton from '../components/form/SubmitButton';
import IndexFields from '../enum/IndexFields';
import SearchComponents from '../enum/SearchComponents';
import stateToParams from '../helpers/Params';
import getQuery from '../helpers/Query';
import useLanguageQuery from '../hooks/useLanguageQuery';
import useSearchParams from '../hooks/useSearchParams';
import InitialState from '../types/InitialState';
import type OptionType from '../types/OptionType';
import type SearchState from '../types/SearchState';
import SelectionsContainer from './SelectionsContainer';

type InitializationMap = {
  groups: boolean;
  neighbourhoods: boolean;
  topic: boolean;
};

type InitialParam = Omit<InitialState, 'page'>;

type FormContainerProps = {
  searchState: SearchState;
  setSearchState: Function;
};

export const FormContainer = ({ searchState, setSearchState }: FormContainerProps) => {
  const [initialized, setIinitialized] = useState<InitializationMap>({
    groups: false,
    neighbourhoods: false,
    topic: false,
  });
  const languageFilter = useLanguageQuery();
  const submitButton = useRef<any>(null);
  const topicRef = useRef<any>(null);
  const neighbourhoodRef = useRef<any>(null);
  const groupRef = useRef<any>(null);
  const [initialParams, updateParams] = useSearchParams();

  const initialize = (key: string) => {
    setIinitialized((prev: InitializationMap) => ({ ...prev, [key]: true }));
  };

  const { topic, neighbourhoods, groups } = initialized;

  const clearSelection = (selection: OptionType, selectionType: string) => {
    const newValue = { ...searchState };
    let ref;

    switch (selectionType) {
      case SearchComponents.TOPIC:
        ref = topicRef;
        break;
      case SearchComponents.NEIGHBOURHOODS:
        ref = neighbourhoodRef;
        break;
      case SearchComponents.NEWS_GROUPS:
        ref = groupRef;
        break;
      default:
        break;
    }

    const index = newValue[selectionType].value?.findIndex((option: OptionType) => {
      return option.value === selection.value;
    });

    if (index !== undefined) {
      newValue[selectionType].value.splice(index, 1);
    }

    ref?.current.setQuery({ value: newValue[selectionType].value });
    submitButton?.current.setQuery(getQuery(newValue, languageFilter));
    updateParams(stateToParams(searchState));
  };

  return (
    <div className='news-form-wrapper'>
      <div className='news-form-container'>
        <h2>{Drupal.t('Filter news items', {}, { context: 'News archive filter results heading' })}</h2>
        <div className='news-form__filters-container'>
          <ReactiveComponent
            componentId={SearchComponents.TOPIC}
            defaultQuery={() => ({
              aggs: {
                [IndexFields.FIELD_NEWS_ITEM_TAGS]: {
                  terms: {
                    field: `${IndexFields.FIELD_NEWS_ITEM_TAGS}.keyword`,
                    order: { _key: 'asc' },
                    size: 100000,
                  },
                },
              },
              query: languageFilter,
            })}
            ref={topicRef}
            render={({ aggregations, setQuery }) => (
              <Dropdown
                aggregations={aggregations}
                componentId={SearchComponents.TOPIC}
                initialize={initialize}
                indexKey={`${IndexFields.FIELD_NEWS_ITEM_TAGS}`}
                initialValue={initialParams[SearchComponents.TOPIC as keyof InitialParam] ?? []}
                label={Drupal.t('Topics', {}, { context: 'News archive topics label' })}
                placeholder={Drupal.t('All topics', {}, { context: 'News archive topics placeholder' })}
                weight={3}
                searchState={searchState}
                setQuery={setQuery}
              />
            )}
          />
          <ReactiveComponent
            componentId={SearchComponents.NEIGHBOURHOODS}
            defaultQuery={() => ({
              aggs: {
                [IndexFields.FIELD_NEWS_NEIGHBOURHOODS]: {
                  terms: {
                    field: `${IndexFields.FIELD_NEWS_NEIGHBOURHOODS}.keyword`,
                    order: { _key: 'asc' },
                    size: 100000,
                  },
                },
              },
              query: languageFilter,
            })}
            ref={neighbourhoodRef}
            render={({ aggregations, setQuery }) => (
              <Dropdown
                aggregations={aggregations}
                componentId={SearchComponents.NEIGHBOURHOODS}
                indexKey={`${IndexFields.FIELD_NEWS_NEIGHBOURHOODS}`}
                initialize={initialize}
                initialValue={initialParams[SearchComponents.NEIGHBOURHOODS as keyof InitialParam] ?? []}
                label={Drupal.t('City disctricts', {}, { context: 'News archive neighbourhoods label' })}
                weight={2}
                searchState={searchState}
                setQuery={setQuery}
                placeholder={Drupal.t(
                  'All city disctricts',
                  {},
                  { context: 'News archive neighbourhoods placeholder' }
                )}
              />
            )}
          />
          <ReactiveComponent
            componentId={SearchComponents.NEWS_GROUPS}
            defaultQuery={() => ({
              aggs: {
                [IndexFields.FIELD_NEWS_GROUPS]: {
                  terms: {
                    field: `${IndexFields.FIELD_NEWS_GROUPS}.keyword`,
                    order: { _key: 'asc' },
                    size: 100000,
                  },
                },
              },
              query: languageFilter,
            })}
            ref={groupRef}
            render={({ aggregations, setQuery }) => (
              <Dropdown
                aggregations={aggregations}
                componentId={SearchComponents.NEWS_GROUPS}
                indexKey={`${IndexFields.FIELD_NEWS_GROUPS}`}
                initialize={initialize}
                initialValue={initialParams[SearchComponents.NEWS_GROUPS as keyof InitialParam] ?? []}
                label={Drupal.t('Target groups', {}, { context: 'News archive groups label' })}
                searchState={searchState}
                weight={1}
                placeholder={Drupal.t('All target groups', {}, { context: 'News archive groups placeholder' })}
                setQuery={setQuery}
              />
            )}
            URLParams={true}
          />
          <ReactiveComponent
            componentId={SearchComponents.SUBMIT}
            react={{ and: [SearchComponents.TOPIC, SearchComponents.NEIGHBOURHOODS, SearchComponents.NEWS_GROUPS] }}
            ref={submitButton}
            render={({ setQuery }) => (
              <div className='news-form__submit'>
                <SubmitButton
                  initialized={topic && neighbourhoods && groups}
                  searchState={searchState}
                  setQuery={setQuery}
                />
              </div>
            )}
          />
        </div>
        <SelectionsContainer
          clearSelection={clearSelection}
          searchState={searchState}
          setSearchState={setSearchState}
        />
      </div>
    </div>
  );
};

export default FormContainer;
