export interface Result {
  alt?: Array<string>;
  changed?: Array<number>;
  field_main_image_caption?: Array<string>;
  field_main_image?: Array<string>;
  field_news_groups?: Array<string>;
  field_news_item_tags?: Array<string>;
  field_news_neighbourhoods?: Array<string>;
  field_photographer?: Array<string>;
  highlight?: Array<string>;
  published_at?: Array<number>;
  title: Array<string>;
  url: Array<string>;
  uuid: Array<string>;
  _click_id: number;
  _id: string;
  _index: string;
  _language: string;
  _score: number;
  _type: string;
}

export default Result;
