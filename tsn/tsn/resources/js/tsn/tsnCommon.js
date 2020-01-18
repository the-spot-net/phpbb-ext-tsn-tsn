import { MDCTextField } from '@material/textfield/component';
import { MDCCheckbox } from '@material/checkbox/component';
import { MDCFormField } from '@material/form-field/component';
import { MDCRipple } from '@material/ripple';
import { MDCChipSet } from '@material/chips';
import { MDCList } from '@material/list/component';

/**
 * Common static methods used throughout
 */
export default class tsnCommon {
  /**
   * Safe-read the tsnSettings window property for settings
   * @param section
   * @param property
   * @returns {*|null}
   */
  static getSettings(section, property) {
    return (((window.tsnSettings || {})[section] || {})[property] || null);
  }

  /**
   * Initialize the Material Design Components
   */
  static initMaterialComponents() {
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

    $('.mdc-button, .mdc-fab, .mdc-card__primary-action').each(function (i, obj) {
      // eslint-disable-next-line no-new
      new MDCRipple(obj);
    });

    $('.mdc-chip-set').each(function (i, obj) {
      // eslint-disable-next-line no-new
      new MDCChipSet(obj);
    });

    $('.mdc-list').each(function (i, obj) {
      // eslint-disable-next-line no-new
      new MDCList(obj).listElements.map((listItemEl) => new MDCRipple(listItemEl));
    });
  }

  /**
   * For easier data-js selector construction
   *
   * @param {string} selector - a string of comma-separated data-js values
   * @returns {string} - a constructed jQuery selector (ex: '[data-js~="valueOne"][data-js~="valueTwo"]')
   */
  static jsSelector(selector) {
    return selector
      .split(' ')
      .map((string) => `[data-js~="${string}"]`)
      .join('');
  }

  /**
   * TODO This probably needs to get its first value from config settings
   * @param slug
   * @returns {string}
   */
  static route(slug) {
    return `/phorums/tsn/${slug}`;
  }
}

tsnCommon.events = {
  ajaxBefore: 'common:ajax:before',
  ajaxComplete: 'common:ajax:complete'
};
