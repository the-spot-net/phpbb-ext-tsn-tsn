import tsnHeader from './tsn/tsnHeader';
import tsnCommon from './tsn/tsnCommon';
import tsnMySpot from './tsn/tsnMySpot';

$(document).ready(function () {
  // Process all MDC interactions
  tsnCommon.initMaterialComponents();
  // Setup Header interactions
  tsnHeader.init();
  // Conditionally setup MySpot interactions
  tsnMySpot.init();
});
