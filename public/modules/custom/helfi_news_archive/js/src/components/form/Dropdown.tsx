import { Combobox } from 'hds-react';
import type { ComboboxProps } from 'hds-react';
import { useEffect, useState } from 'react';

import useAggregations from '../../hooks/useAggregations';
import type { Aggregations } from '../../types/Aggregations';
import OptionType from '../../types/OptionType';

type DropdownProps = Omit<
  ComboboxProps<OptionType>,
  'options' | 'clearButtonAriaLabel' | 'selectedItemRemoveButtonAriaLabel' | 'toggleButtonAriaLabel'
> & {
  aggregations: Aggregations;
  componentId: string;
  initialValue: string[];
  label: string;
  weight?: number;
  indexKey: string;
  initialize: Function;
  searchState: any;
  setQuery: Function;
  clearButtonAriaLabel?: string;
  selectedItemRemoveButtonAriaLabel?: string;
  toggleButtonAriaLabel?: string;
};

export const Dropdown = ({
  aggregations,
  componentId,
  indexKey,
  initialize,
  initialValue,
  label,
  weight,
  placeholder,
  searchState,
  setQuery,
  clearButtonAriaLabel = Drupal.t('Clear selection', {}, { context: 'News archive clear button aria label' }),
  selectedItemRemoveButtonAriaLabel = Drupal.t('Remove item', {}, { context: 'News archive remove item aria label' }),
  toggleButtonAriaLabel = Drupal.t('Open the combobox', {}, { context: 'News archive open dropdown aria label' }),
}: DropdownProps) => {
  const options: OptionType[] = useAggregations(aggregations, indexKey);
  const [loading, setLoading] = useState<boolean>(true);

  useEffect(() => {
    if (loading && aggregations && options) {
      if (!initialValue.length) {
        initialize(componentId);
        setLoading(false);
        return;
      }

      const values: OptionType[] = [];

      initialValue.forEach((value: string) => {
        // Use for loop for performance
        for (const option of options) {
          if (option.value.toLowerCase() === value) {
            values.push(option);
          }
        }
      });

      setQuery({
        value: values,
      });
      initialize(componentId);
      setLoading(false);
    }
  }, [aggregations, componentId, initialize, initialValue, loading, options, setQuery]);

  const value: OptionType[] = searchState[componentId]?.value || [];

  return (
    <div className='news-form__filter'>
      <div
        className='news-form__filter-container'
        style={weight ? ({ '--menu-z-index': weight++ } as React.CSSProperties) : {}}
      >
        <Combobox
          className='news-form__combobox'
          clearButtonAriaLabel={clearButtonAriaLabel}
          disabled={loading}
          label={label}
          // @ts-ignore
          options={options}
          onChange={(value: OptionType[]) => {
            setQuery({
              value: value,
            });
          }}
          placeholder={placeholder}
          multiselect={true}
          selectedItemRemoveButtonAriaLabel={selectedItemRemoveButtonAriaLabel}
          toggleButtonAriaLabel={toggleButtonAriaLabel}
          theme={{
            '--focus-outline-color': 'var(--hdbt-color-black)',
            '--multiselect-checkbox-background-selected': 'var(--hdbt-color-black)',
            '--placeholder-color': 'var(--hdbt-color-black)',
          }}
          value={value}
        />
      </div>
    </div>
  );
};

export default Dropdown;
