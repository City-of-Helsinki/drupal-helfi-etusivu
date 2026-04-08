import CardItem from '@/react/common/Card';

type ResultCardProps = {
  url: string;
  title: string;
  description?: string;
  cardModifierClass?: string;
};

const ResultCard = ({ url, title, description, cardModifierClass }: ResultCardProps) => (
  <CardItem
    cardTitle={title}
    cardUrl={url}
    cardDescription={description}
    cardModifierClass={cardModifierClass}
    cardTitleLevel={4}
  />
);

export default ResultCard;
