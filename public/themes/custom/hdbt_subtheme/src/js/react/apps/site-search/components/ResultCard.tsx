import CardItem from '@/react/common/Card';
import Icon from '@/react/common/Icon';

type ResultCardProps = {
  url: string;
  title: string;
  description?: string;
  bundle?: string;
  publishDate?: string;
  cardModifierClass?: string;
};

const DESCRIPTION_PLACEHOLDER =
  'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.';
const DATE_PLACEHOLDER = 'DD.MM.YYYY';
const PUBLISH_DATE_PLACEHOLDER = '2000-01-01';

const isOlderThanOneYear = (isoDate: string): boolean => {
  const oneYearAgo = new Date();
  oneYearAgo.setFullYear(oneYearAgo.getFullYear() - 1);
  return new Date(isoDate) < oneYearAgo;
};

const ResultCard = ({
  url,
  title,
  description = DESCRIPTION_PLACEHOLDER,
  bundle,
  publishDate = PUBLISH_DATE_PLACEHOLDER,
  cardModifierClass,
}: ResultCardProps) => {
  const isNewsItem = bundle === 'news_item';
  const isOutdated = isNewsItem && isOlderThanOneYear(publishDate);

  return (
    <CardItem
      cardTitle={title}
      cardUrl={url}
      cardDescription={description}
      cardModifierClass={cardModifierClass}
      cardTitleLevel={3}
      {...(isNewsItem && {
        date: DATE_PLACEHOLDER,
        dateLabel: Drupal.t('Published', {}, { context: 'Site search' }),
      })}
      {...(isOutdated && {
        cardTags: [
          {
            tag: Drupal.t(
              'Published over a year ago',
              {},
              { context: 'The helper text before the node published timestamp' },
            ),
            color: 'alert',
            iconStart: <Icon icon='alert-circle' />,
          },
        ],
      })}
    />
  );
};

export default ResultCard;
