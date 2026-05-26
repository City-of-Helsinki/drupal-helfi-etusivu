import CardItem from '@/react/common/Card';
import Icon from '@/react/common/Icon';

type ResultCardProps = {
  url: string;
  title: string;
  description?: string;
  bundle?: string;
  publishDate?: number;
  cardModifierClass?: string;
};

const parsePublishDate = (value: number): Date | null => {
  return new Date(value * 1000);
};

const isOlderThanOneYear = (date: Date): boolean => {
  const oneYearAgo = new Date();
  oneYearAgo.setFullYear(oneYearAgo.getFullYear() - 1);
  return date < oneYearAgo;
};

const ResultCard = ({ url, title, description, bundle, publishDate, cardModifierClass }: ResultCardProps) => {
  const isNewsItem = bundle === 'news_item';
  const parsedDate = publishDate ? parsePublishDate(publishDate) : null;
  const isOutdated = isNewsItem && parsedDate ? isOlderThanOneYear(parsedDate) : false;
  const lang = drupalSettings?.path?.currentLanguage ?? 'fi';
  const formattedDate = parsedDate ? parsedDate.toLocaleDateString(lang) : undefined;

  const cardItem = (
    <CardItem
      cardTitle={title}
      cardUrl={url}
      cardDescription={description}
      cardModifierClass={cardModifierClass}
      cardTitleLevel={3}
      {...(isNewsItem &&
        formattedDate && {
          date: formattedDate,
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

  if (DEBUG_MODE) {
    return (
      <div>
        {cardItem}
        <pre style={{ fontSize: '12px', marginTop: '8px' }}>
          {JSON.stringify({ url, title, description, bundle, publishDate }, null, 2)}
        </pre>
      </div>
    );
  }

  return cardItem;
};

export default ResultCard;
