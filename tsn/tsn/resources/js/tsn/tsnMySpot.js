import tsnPlugin from './tsnPlugin';
import tsnCommon from './tsnCommon';
import tsnTopicCard from './tsnTopicCard';

/**
 * Handles the interactions for the MySpot page
 */
export default class tsnMySpot extends tsnPlugin {
  constructor({
    container,
    options = {
      initialLoad: (new Date()).getTime(),
      page: 1
    },
    name = tsnMySpot.pluginName
  } = {}) {
    super({ container, options, name });
    this.constructSubmodules();
  }

  /**
   * If the container is on the page, stand up the plugin for it, and read the settings from the window
   */
  static init() {
    const $container = tsnMySpot.$main.find(tsnMySpot.selectors.container);
    if ($container) {
      $container.tsnMySpot({
        initialLoad: tsnCommon.getSettings('myspot', 't'),
        page: tsnCommon.getSettings('myspot', 'p')
      });
    }
  }

  static processResponse() {
    // Reinit the topic cards that came through...
    tsnTopicCard.init();
  }

  /**
   * Setup the submodules of the page
   */
  constructSubmodules() {
    this.$elem(tsnMySpot.selectors.feed.list)
      .tsnInfiniteScroll({
        endpoint: tsnMySpot.endpoints.feed,
        initialLoad: this.options.initialLoad,
        page: this.options.page,
        resolveCallback: tsnMySpot.processResponse
      });
  }
}

if (!$.fn.tsnMySpot) {
  $.fn.tsnMySpot = function (options) {
    return this.each(function () {
      return tsnPlugin.getPluginObject({ pluginName: tsnMySpot.pluginName, $elem: $(this) }) || new tsnMySpot({ container: this, options });
    });
  };
}

tsnMySpot.endpoints = {
  feed: tsnCommon.route('ajax/myspot-feed')
};
tsnMySpot.events = {};
tsnMySpot.pluginName = 'tsnMySpot';
tsnMySpot.selectors = {
  container: tsnCommon.jsSelector('js--tsn-myspot'),
  feed: {
    container: tsnCommon.jsSelector('js--tsn-myspot-card-container'),
    list: tsnCommon.jsSelector('js--tsn-myspot-card-list')
  }
};
