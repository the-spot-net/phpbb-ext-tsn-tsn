import tsnCommon from './tsn/tsnCommon';
import tsnHeader from './tsn/tsnHeader';
import tsnMySpot from './tsn/tsnMySpot';
import tsnTopicCard from './tsn/tsnTopicCard';

$(document).ready(function () {
  // Process all MDC interactions
  tsnCommon.initMaterialComponents();
  // Setup Header interactions
  tsnHeader.init();
  // Conditionally setup MySpot interactions
  tsnMySpot.init();
  tsnTopicCard.init();
});
