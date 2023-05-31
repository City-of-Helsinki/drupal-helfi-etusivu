import Aggregations from '../types/Aggregations';
import OptionType from '../types/OptionType';

export default function useAggregations(aggregations: Aggregations, key: string) {
  const options: OptionType[] = [];

  if (aggregations && aggregations[key] && aggregations[key].buckets && aggregations[key].buckets.length) {
    aggregations[key].buckets.forEach((item) => {
      if (!Array.isArray(item.key)) {
        return;
      }

      const [tid, name] = item.key;

      options.push({
        label: name.toString(),
        value: tid.toString(),
      });
    });
  }

  return options;
}
