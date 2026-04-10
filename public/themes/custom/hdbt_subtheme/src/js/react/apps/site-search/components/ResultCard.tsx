import CardItem from '@/react/common/Card';

type ResultCardProps = {
  url: string;
  title: string;
  description?: string;
};

const ResultCard = ({ url, title, description }: ResultCardProps) => (
  <CardItem
    cardTitle={title}
    cardUrl={url}
    cardDescription={description}
    cardModifierClass={'card--site-search'}
    cardTitleLevel={3}
  />
);

export default ResultCard;
