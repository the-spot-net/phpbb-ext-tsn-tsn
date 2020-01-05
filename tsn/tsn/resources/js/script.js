import { MDCTextField } from '@material/textfield/component';
import tsnHeader from './tsn/tsnHeader';

$(document).ready(function () {
  tsnHeader.init();

  // Because autoinit doesn't work here
  $('.mdc-text-field').each(function (i, obj) {
    // eslint-disable-next-line no-new
    new MDCTextField(obj);
  });
});
