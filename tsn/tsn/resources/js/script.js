import { MDCTextField } from '@material/textfield/component';
import { MDCCheckbox } from '@material/checkbox/component';
import { MDCFormField } from '@material/form-field/component';
import tsnHeader from './tsn/tsnHeader';

$(document).ready(function () {
  tsnHeader.init();

  // Because autoinit doesn't work here
  $('.mdc-text-field').each(function (i, obj) {
    // eslint-disable-next-line no-new
    new MDCTextField(obj);
  });

  // $('.mdc-switch').each(function (i, obj) {
  //   // eslint-disable-next-line no-new
  //   new MDCSwitch(obj);
  // });

  $('.mdc-checkbox').each(function (i, obj) {
    // eslint-disable-next-line no-new
    new MDCCheckbox(obj);
    const $checkbox = $(obj);

    const $formField = $checkbox.parents('.mdc-form-field');
    if ($formField) {
      // eslint-disable-next-line no-new
      new MDCFormField($formField[0]);
    }
  });

  // TODO Hook up the Login form in its own class so the checkboxes/switches can be turned into form fields
  // const formField = new MDCFormField(document.querySelector('.mdc-form-field'));
  // formField.input = checkbox;
});
