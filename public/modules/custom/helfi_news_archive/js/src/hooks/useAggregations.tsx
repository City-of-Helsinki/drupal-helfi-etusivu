import Aggregations from '../types/Aggregations';
import OptionType from '../types/OptionType';

export default function useAggregations(aggregations: Aggregations, key: string) {
  let options: OptionType[] = [];

  if (aggregations && aggregations[key] && aggregations[key].buckets && aggregations[key].buckets.length) {
    options = aggregations[key].buckets.map((element) => ({
      label: element.key,
      value: element.key,
    }));
  }

  return options;
}
