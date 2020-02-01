import tsnPlugin from './tsnPlugin';
import tsnRequest from './tsnRequest';

/**
 * Handle the Infinite Scroll functionality throughout tsn
 */
export default class tsnInfiniteScroll extends tsnPlugin {
  constructor({
    container,
    options = {
      endpoint: '',
      initialLoad: (new Date()).getTime(),
      templateSelector: null,
      page: 1,
      resolveCallback: null
    },
    name = tsnInfiniteScroll.pluginName
  } = {}) {
    super({ container, options, name });
    this.constructListeners();
  }

  /**
   * Setup additional properties for the plugin
   */
  constructDynamicProperties() {
    super.constructDynamicProperties();

    this.$template = $(this.options.templateSelector);

    // Store the current request state, so we don't keep requesting while the user keeps scrolling
    this.isRequestActive = false;
    this.mightHaveMore = true;
  }

  /**
   * Setup the listeners for the plugin
   */
  constructListeners() {
    tsnInfiniteScroll.$main.scroll(() => {
      // Get the distance the container has remaining at the bottom...
      const bottomScrollDistance = tsnInfiniteScroll.$main.height() - this.$container.offset().top - 16;
      const invisibleBottomTrigger = this.$container.height() * 0.9;

      if (bottomScrollDistance >= invisibleBottomTrigger && this.mightHaveMore !== false) {
        this.doRequestNextPage();
      }
    });
  }

  /**
   * Handle the request for the next page's contents
   */
  doRequestNextPage() {
    if (!this.isRequestActive) {
      this.isRequestActive = true;
      // Fetch the next page...
      this.options.page++;

      // Make the request and handle the response...then resolve the promise
      tsnRequest.ajaxRequest({
        endpoint: this.options.endpoint,
        dataPacket: {
          p: this.options.page,
          t: this.options.initialLoad
        },
        successCallback: (response) => {
          if (response.status === tsnRequest.constants.SUCCESS) {
            // TODO Check if topic exists, if so, update first/last post & reply status;
            // TODO If not, Pull the Template, populate the data points, append to the list
            this.$container.append($(response.data.html));
            this.mightHaveMore = response.data.hasMore || false;

            // response.data.templateHtml
            // response.data.templateData
          } else {
            window.console.log(response.message);
          }
        },
        resolveCallback: () => {
          this.isRequestActive = false;
          if (typeof this.options.resolveCallback === 'function') {
            this.options.resolveCallback();
          }
        }
      });
    }
  }
}

if (!$.fn.tsnInfiniteScroll) {
  $.fn.tsnInfiniteScroll = function (options) {
    return this.each(function () {
      return tsnPlugin.getPluginObject({ pluginName: tsnInfiniteScroll.pluginName, $elem: $(this) }) || new tsnInfiniteScroll({ container: this, options });
    });
  };
}

tsnInfiniteScroll.pluginName = 'tsnInfiniteScroll';
