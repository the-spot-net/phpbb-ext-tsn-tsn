import tsnPlugin from './tsnPlugin';
import tsnCommon from './tsnCommon';

/**
 * Handles interactions with cards, and requesting their content
 */
export default class tsnTopicCard extends tsnPlugin {
  constructor({
    container,
    options = {},
    name = tsnTopicCard.pluginName
  } = {}) {
    super({ container, options, name });

    this.constructDynamicProperties();
    this.constructListeners();
  }

  /**
   * Scan the DOM for elements to initialize
   * @param $context
   */
  static init({ $context = tsnTopicCard.$document || $(document) } = {}) {
    $context.find(tsnTopicCard.selectors.container).tsnTopicCard();
  }

  constructDynamicProperties() {
    super.constructDynamicProperties();

    this.$mimicContainer = this.$elem(tsnTopicCard.selectors.mimic.container);
    this.$mimicCard = this.$elem(tsnTopicCard.selectors.mimic.card);

    this.originalCard = null;
    this.position = {};
    this.size = {};
  }

  constructListeners() {
    this.$container
      .on('click', (event) => {
        this.onClick({ event });
      });
    // $fsmActual.addEventListener("click", closeFSM);
  }

  doCloneSourceToMimic({ event } = {}) {
    // Extract the properties rom the current card being clicked...
    this.originalCard = event.currentTarget;
    this.position = this.originalCard.getBoundingClientRect();
    this.size = {
      width: tsnTopicCard.$window[0].getComputedStyle(this.originalCard).width,
      height: tsnTopicCard.$window[0].getComputedStyle(this.originalCard).height
    };

    // Update the mimic to match source card
    this.$mimicCard.css('top', `${this.position.top}px`);
    this.$mimicCard.css('left', `${this.position.left}px`);
    this.$mimicCard.css('height', this.size.height);
    this.$mimicCard.css('width', this.size.width);

    // this.$mimic.innerHTML = this.originalCard.innerHTML;
    Object.values(this.originalCard.classList.value.split(' ')).forEach((className) => {
      if (className) {
        this.$mimicCard.addClass(className);
      }
    });

    this.$mimicContainer.addClass('tsn-mimic-container__is-active');
  }

  /**
   * Update the Mimic with the styles & content of the card, then expand it.
   * @param event
   */
  onClick({ event } = {}) {
    this.doCloneSourceToMimic({ event });

    setTimeout(() => {
      this.$mimicCard.addClass('tsn-mimic-card__is-active');
      this.$mimicContainer.addClass('tsn-mimic-container__is-ready');
    }, 100);

    // tsnRequest.ajaxRequest({
    //   endpoint: '',
    //   dataPacket: {},
    //   successCallback: () => {},
    //   resolveCallback: ({ response }) => {
    //     // Place the ajax response
    //     this.$mimic.classList.remove('growing');
    //     this.$mimic.add('full-screen');
    //
    //     this.loadingHtml = this.$mimic.innerHTML;
    //     this.$mimic.innerHTML = response.data.html;
    //   }
    // });
  }

  /**
   * Clear out the styles and content of the mimic, and put it back to a style-less absolute div
   */
  // onClose() {
  // this.$mimic.style.height = this.size.height;
  // this.$mimic.style.width = this.size.width;
  // this.$mimic.style.top = `${this.position.top}px`;
  // this.$mimic.style.left = `${this.position.left}px`;
  // this.$mimic.style.margin = 0;
  // this.$mimic.classList.remove('full-screen');
  // this.$mimic.classList.add('shrinking');
  //
  // setTimeout(() => {
  //   // TODO Remove this if it's not useful for topics
  //   while (this.$mimic.firstChild) {
  //     this.$mimic.removeChild(this.$mimic.firstChild);
  //   }
  //   Object.values(this.$mimic.classList).forEach((className) => {
  //     if (className) {
  //       this.$mimic.classList.remove(className);
  //     }
  //   });
  //   this.$mimic.style = '';
  //   this.$mimic.innerHTML = this.loadingHtml;
  // }, 250);
  // }
}

if (!$.fn.tsnTopicCard) {
  $.fn.tsnTopicCard = function (options = {}) {
    return this.each(function () {
      return tsnPlugin.getPluginObject({ pluginName: tsnTopicCard.pluginName, $elem: $(this) }) || new tsnTopicCard({ container: this, options });
    });
  };
}

tsnTopicCard.pluginName = 'tsnTopicCard';
tsnTopicCard.selectors = {
  container: tsnCommon.jsSelector('js--tsn-topic-card'),
  // TODO Add this to a component somewhere
  closeButton: tsnCommon.jsSelector('js--tsn-topic-card-button-close'),
  mimic: {
    container: tsnCommon.jsSelector('js--tsn-mimic-container'),
    card: tsnCommon.jsSelector('js--tsn-mimic-card')
  }
};
